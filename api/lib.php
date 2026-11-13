<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header(
        "Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; " .
        "style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; " .
        "connect-src 'self'; worker-src 'self' blob:; object-src 'none'; " .
        "base-uri 'none'; form-action 'self'; frame-ancestors 'self'"
    );
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if (repdoc_uses_mysql()) {
        $pdo = repdoc_mysql_pdo();
        if (!$pdo instanceof PDO) {
            fail('The voter database is not reachable.', 503);
        }
        return $pdo;
    }
    if (!is_file(DB_PATH)) {
        fail('The voter index has not been built yet.', 503);
    }
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA query_only = ON');
    $pdo->exec('PRAGMA busy_timeout = 4000');
    return $pdo;
}

function secret_key(): string
{
    static $secret = null;
    if (is_string($secret)) {
        return $secret;
    }
    if (!is_file(SECRET_PATH)) {
        $dir = dirname(SECRET_PATH);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $handle = @fopen(SECRET_PATH, 'x');
        if ($handle !== false) {
            fwrite($handle, bin2hex(random_bytes(32)));
            fclose($handle);
            @chmod(SECRET_PATH, 0600);
        }
    }
    if (!is_file(SECRET_PATH)) {
        fail('Server secret is missing.', 503);
    }
    $secret = trim((string) file_get_contents(SECRET_PATH));
    if ($secret === '') {
        fail('Server secret is invalid.', 503);
    }
    return $secret;
}

function pdf_token(int $fileId): string
{
    return $fileId . '-' . substr(hash_hmac('sha256', (string) $fileId, secret_key()), 0, 24);
}

function parse_pdf_token(string $token): ?int
{
    if (!preg_match('/^(\d{1,9})-([a-f0-9]{24})$/', $token, $match)) {
        return null;
    }
    $fileId = (int) $match[1];
    $expected = substr(hash_hmac('sha256', (string) $fileId, secret_key()), 0, 24);
    return hash_equals($expected, $match[2]) ? $fileId : null;
}

function json_out(mixed $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400): never
{
    json_out(['ok' => false, 'error' => $message], $status);
}

function input_string(string $key, int $maxLength = 120): string
{
    $value = $_GET[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function input_int(string $key, int $default, int $min, int $max): int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    if ($value === false || $value === null) {
        return $default;
    }
    return max($min, min($max, (int) $value));
}

function bn_to_en(string $value): string
{
    return strtr($value, [
        '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
        '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
    ]);
}

function like_escape(string $value): string
{
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
}

/**
 * Cheap per-IP throttle for the search endpoint. Files stay under data/ so
 * they are unreachable over HTTP.
 */
function throttle(string $bucket, int $limit, int $windowSeconds): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
    $dir = DATA_DIR . '/throttle';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return;
    }
    $path = $dir . '/' . $bucket . '-' . hash('xxh128', $ip) . '.json';
    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        return;
    }
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return;
    }
    $now = time();
    $raw = stream_get_contents($handle);
    $data = json_decode((string) $raw, true);
    if (!is_array($data) || ($now - (int) ($data['start'] ?? 0)) >= $windowSeconds) {
        $data = ['start' => $now, 'count' => 0];
    }
    $data['count'] = (int) $data['count'] + 1;
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    if ($data['count'] > $limit) {
        header('Retry-After: ' . $windowSeconds);
        fail('Too many requests. Give it a moment.', 429);
    }
}

function build_where(array $input, array &$params): array
{
    $where = ["v.status = 'ok'"];
    $union = $input['union'];
    $area = $input['area'];
    $gender = $input['gender'];
    $status = $input['status'];

    if ($status === 'cancelled') {
        $where[0] = "v.status <> 'ok'";
    } elseif ($status === 'all') {
        array_shift($where);
    }
    if ($union !== '') {
        $where[] = 'f.union_key = :union';
        $params[':union'] = $union;
    }
    if ($area !== '') {
        $where[] = 'f.area_no = :area';
        $params[':area'] = $area;
    }
    if ($gender === 'male' || $gender === 'female') {
        $where[] = 'f.gender = :gender';
        $params[':gender'] = $gender;
    }
    return $where;
}

