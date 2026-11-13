<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_admin();
set_time_limit(0);

$mysql = repdoc_uses_mysql();

function vs_json_files(): array
{
    $dir = JSON_DIR;
    $files = [];
    foreach (scandir($dir) ?: [] as $name) {
        if (str_ends_with(strtolower($name), '.json')) {
            $files[] = $name;
        }
    }
    sort($files, SORT_STRING);
    return $files;
}

function vs_union_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = [];
    $path = README_PATH;
    if (!is_file($path)) {
        return $map;
    }
    $section = '';
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (preg_match('/^###\s+(.+)$/', $line, $m)) {
            $section = trim($m[1]);
            continue;
        }
        if ($section !== '' && preg_match('/^\|\s*\d+\s*\|\s*`(\d+)`\s*\|/', $line, $m)) {
            $map[$m[1]] = $section;
        }
    }
    return $map;
}

function vs_bn_digits(string $value): string
{
    return strtr($value, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
        '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
}

function vs_schema(PDO $db): void
{
    $db->exec('DROP TABLE IF EXISTS voters');
    $db->exec('DROP TABLE IF EXISTS files');
    $db->exec('CREATE TABLE files (
        id INT NOT NULL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        union_key VARCHAR(64) NULL,
        union_name VARCHAR(128) NULL,
        area_no VARCHAR(32) NULL,
        area_name TEXT NULL,
        gender VARCHAR(16) NULL,
        pages INT NULL,
        district TEXT NULL,
        upazila TEXT NULL,
        post_office TEXT NULL,
        postcode VARCHAR(32) NULL,
        publish_date VARCHAR(64) NULL,
        records INT NULL,
        active INT NULL,
        cancelled INT NULL,
        KEY idx_files_area (area_no),
        KEY idx_files_union (union_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $db->exec('CREATE TABLE voters (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        file_id INT NOT NULL,
        serial INT NULL,
        serial_bn VARCHAR(32) NULL,
        name TEXT NULL,
        voter_no VARCHAR(32) NULL,
        voter_no_digits VARCHAR(32) NULL,
        father TEXT NULL,
        mother TEXT NULL,
        occupation VARCHAR(255) NULL,
        dob VARCHAR(32) NULL,
        address TEXT NULL,
        status VARCHAR(16) NULL,
        page INT NULL,
        `col` INT NULL,
        x0 DOUBLE NULL,
        y0 DOUBLE NULL,
        x1 DOUBLE NULL,
        y1 DOUBLE NULL,
        pw DOUBLE NULL,
        ph DOUBLE NULL,
        KEY idx_voters_file (file_id, status),
        KEY idx_voters_no (voter_no_digits),
        KEY idx_voters_serial (serial),
        KEY idx_voters_page (page)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function vs_import_file(PDO $db, string $jsonName, int $fileId): array
{
    $started = microtime(true);
    $path = JSON_DIR . '/' . $jsonName;
    $doc = json_decode((string) file_get_contents($path), true);
    if (!is_array($doc)) {
        throw new RuntimeException('Cannot parse ' . $jsonName);
    }
    $meta = is_array($doc['meta'] ?? null) ? $doc['meta'] : [];
    $recs = is_array($doc['records'] ?? null) ? $doc['records'] : [];
    $stats = is_array($doc['stats'] ?? null) ? $doc['stats'] : [];

    preg_match('/^(\d+)_/', $jsonName, $match);
    $areaNo = $match[1] ?? vs_bn_digits((string) ($meta['area_no'] ?? ''));
    $unionKey = vs_union_map()[$areaNo] ?? '';
    if (str_starts_with($unionKey, 'Sonatola Pourashava')) {
        $unionKey = 'Sonatola Pourashava';
    }
    $unionNames = [
        'Balua' => 'বালুয়া', 'Digdair' => 'ডিগদাইর', 'Jorgachha' => 'জোড়গাছা',
        'Madhupur' => 'মধুপুর', 'Pakulla' => 'পাকুল্লা', 'Sonatola' => 'সোনাতলা',
        'Tekani Chukaina' => 'টেকানী চুকাইনগর', 'Sonatola Pourashava' => 'সোনাতলা পৌরসভা',
    ];
    $unionName = $unionNames[$unionKey] ?? ($unionKey !== '' ? $unionKey : 'সোনাতলা');
    $gender = str_contains($jsonName, '_female_') ? 'female'
        : (str_contains($jsonName, '_male_') ? 'male' : '');

    $base = substr($jsonName, 0, -5);
    $coords = null;
    $coordPath = COORD_DIR . '/' . $base . '.json';
    if (is_file($coordPath)) {
        $coords = json_decode((string) file_get_contents($coordPath), true);
    }
    $rects = is_array($coords['rects'] ?? null) ? $coords['rects'] : null;
    $pw = is_array($coords) ? ($coords['w'] ?? null) : null;
    $ph = is_array($coords) ? ($coords['h'] ?? null) : null;

    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM voters WHERE file_id = ?')->execute([$fileId]);
        $db->prepare('REPLACE INTO files (id, name, union_key, union_name, area_no, area_name,
            gender, pages, district, upazila, post_office, postcode, publish_date, records, active, cancelled)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
            $fileId,
            $base . '.pdf',
            $unionKey,
            $unionName,
            $areaNo,
            (string) ($meta['area_name'] ?? ''),
            $gender,
            (int) ($doc['pages'] ?? 0),
            (string) ($meta['district'] ?? ''),
            (string) ($meta['upazila'] ?? ''),
            (string) ($meta['post_office'] ?? ''),
            (string) ($meta['postcode'] ?? ''),
            (string) ($meta['publish_date'] ?? ''),
            (int) ($stats['records'] ?? count($recs)),
            (int) ($stats['active'] ?? 0),
            count($stats['cancelled'] ?? []),
        ]);

        $columns = 'file_id, serial, serial_bn, name, voter_no, voter_no_digits, father, mother, '
            . 'occupation, dob, address, status, page, `col`, x0, y0, x1, y1, pw, ph';
        $batch = [];
        $params = [];
        $flush = static function () use ($db, $columns, &$batch, &$params): void {
            if ($batch === []) {
                return;
            }
            $sql = 'INSERT INTO voters (' . $columns . ') VALUES '
                . implode(',', $batch);
            $db->prepare($sql)->execute($params);
            $batch = [];
            $params = [];
        };
        $rowSql = '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        foreach ($recs as $rec) {
            $key = ($rec['page'] ?? '') . ':' . ($rec['column'] ?? '') . ':' . ($rec['serial'] ?? '');
            $rect = $rects !== null ? ($rects[$key] ?? null) : null;
            $x0 = $y0 = $x1 = $y1 = null;
            if (is_array($rect) && count($rect) === 4) {
                [$x0, $y0, $x1, $y1] = $rect;
            }
            $batch[] = $rowSql;
            array_push(
                $params,
                $fileId,
                $rec['serial'] ?? null,
                $rec['serial_bn'] ?? null,
                $rec['name'] ?? null,
                $rec['voter_no'] ?? null,
                $rec['voter_no_digits'] ?? null,
                $rec['father'] ?? null,
                $rec['mother'] ?? null,
                $rec['occupation'] ?? null,
                $rec['dob'] ?? null,
                $rec['address'] ?? null,
                $rec['status'] ?? null,
                $rec['page'] ?? null,
                $rec['column'] ?? null,
                $x0, $y0, $x1, $y1, $pw, $ph
            );
            if (count($batch) >= 200) {
                $flush();
            }
        }
        $flush();
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    return [
        'ok' => true,
        'file' => $jsonName,
        'file_id' => $fileId,
        'records' => count($recs),
        'ms' => (int) round((microtime(true) - $started) * 1000),
    ];
}

$step = (string) ($_GET['step'] ?? '');
if ($step !== '') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if (!$mysql) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'This host is using SQLite — MySQL importer is not needed.']);
        exit;
    }
    $db = repdoc_mysql_pdo();
    if (!$db instanceof PDO) {
        http_response_code(503);
        echo json_encode(['ok' => false, 'error' => 'MySQL is not reachable.']);
        exit;
    }
    try {
        if ($step === 'status') {
            echo json_encode([
                'ok' => true,
                'files' => (int) $db->query('SELECT COUNT(*) FROM files')->fetchColumn(),
                'voters' => (int) $db->query('SELECT COUNT(*) FROM voters')->fetchColumn(),
                'total_files' => count(vs_json_files()),
            ]);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify_request()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'CSRF check failed. Reload the page.']);
            exit;
        }
        if ($step === 'init') {
            vs_schema($db);
            echo json_encode(['ok' => true]);
            exit;
        }
        if ($step === 'file') {
            $name = (string) ($_POST['file'] ?? '');
            if (!in_array($name, vs_json_files(), true)) {
                throw new RuntimeException('Unknown file.');
            }
            $fileId = array_search($name, vs_json_files(), true) + 1;
            echo json_encode(vs_import_file($db, $name, $fileId));
            exit;
        }
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Unknown step.']);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$files = vs_json_files();
$token = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Voter search — MySQL import</title>
<style>
body{font:15px/1.5 system-ui,sans-serif;margin:0;padding:28px;background:#0f1720;color:#e6edf3}
main{max-width:760px;margin:0 auto}
h1{font-size:20px;margin:0 0 6px}
p{color:#9fb0c0;margin:0 0 18px}
.card{background:#161f2b;border:1px solid #263345;border-radius:10px;padding:18px;margin-bottom:16px}
button{font:inherit;padding:10px 16px;border-radius:8px;border:1px solid #2e6fd0;background:#1c4f9c;color:#fff;cursor:pointer}
button.secondary{background:#233043;border-color:#33465f}
button:disabled{opacity:.45;cursor:default}
.bar{height:12px;background:#0b1119;border-radius:6px;overflow:hidden;margin:14px 0 8px}
.bar i{display:block;height:100%;width:0;background:#3b82f6;transition:width .2s}
pre{background:#0b1119;border-radius:8px;padding:12px;max-height:220px;overflow:auto;font-size:12px;color:#9fb0c0;white-space:pre-wrap}
.warn{color:#f0b45b}
</style>
</head>
<body>
<main>
<h1>Voter search — MySQL import</h1>
<p>Builds the MySQL tables from <code>data/json</code> + <code>data/coords</code> (no SQLite upload needed). Admin only.</p>
<?php if (!$mysql): ?>
<div class="card warn">This host is using SQLite (<code>data/voters.sqlite</code>). The MySQL importer is only for hosts running with MySQL, e.g. InfinityFree.</div>
<?php else: ?>
<div class="card">
  <button id="init" class="secondary">1. Rebuild tables</button>
  <button id="run">2. Import <?= count($files) ?> files</button>
  <div class="bar"><i id="fill"></i></div>
  <div id="state">Idle. <?= count($files) ?> JSON files found.</div>
  <pre id="log"></pre>
</div>
<div class="card warn">Delete <code>install.php</code> after the import finishes.</div>
<?php endif; ?>
</main>
<script>
const token = <?= json_encode($token) ?>;
const files = <?= json_encode($files, JSON_UNESCAPED_UNICODE) ?>;
const init = document.getElementById('init'), run = document.getElementById('run');
const fill = document.getElementById('fill'), state = document.getElementById('state'), log = document.getElementById('log');
const say = (msg) => { log.textContent += msg + '\n'; log.scrollTop = log.scrollHeight; };
const post = async (step, extra = {}) => {
  const body = new FormData(); body.append('csrf_token', token);
  for (const [k, v] of Object.entries(extra)) body.append(k, v);
  const res = await fetch('install.php?step=' + encodeURIComponent(step), {method: 'POST', body});
  const data = await res.json();
  if (!data.ok) throw new Error(data.error || 'Request failed');
  return data;
};
init.addEventListener('click', async () => {
  if (!confirm('Drop and recreate files/voters tables?')) return;
  init.disabled = true;
  try { await post('init'); say('Tables rebuilt.'); state.textContent = 'Tables ready.'; }
  catch (e) { say('Init failed: ' + e.message); }
  finally { init.disabled = false; }
});
run.addEventListener('click', async () => {
  if (!confirm('Import ' + files.length + ' files now?')) return;
  init.disabled = run.disabled = true;
  let voters = 0;
  for (let i = 0; i < files.length; i++) {
    state.textContent = 'Importing ' + (i + 1) + ' / ' + files.length + ': ' + files[i];
    try {
      const data = await post('file', {file: files[i]});
      voters += data.records;
      fill.style.width = (((i + 1) / files.length) * 100).toFixed(1) + '%';
      if ((i + 1) % 10 === 0 || i + 1 === files.length) {
        say('[' + (i + 1) + '/' + files.length + '] ' + data.records + ' rows, ' + data.ms + ' ms — ' + voters + ' total');
      }
    } catch (e) {
      say('FAILED ' + files[i] + ': ' + e.message + ' — fix, then press Import again (re-runs are safe).');
      init.disabled = run.disabled = false;
      return;
    }
  }
  state.textContent = 'Done. ' + voters + ' voter rows imported. Delete install.php now.';
  init.disabled = run.disabled = false;
});
</script>
</body>
</html>
