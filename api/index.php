<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

security_headers();
header('X-Robots-Tag: noindex, nofollow');

$action = input_string('action', 20) ?: 'stats';

try {
    switch ($action) {
        case 'search':
            handle_search();
            break;
        case 'voter':
            handle_voter();
            break;
        case 'pdf':
            handle_pdf();
            break;
        case 'filters':
            handle_filters();
            break;
        case 'stats':
            handle_stats();
            break;
        default:
            fail('Unknown action.', 404);
    }
} catch (Throwable $error) {
    error_log('voter-api: ' . $error->getMessage());
    fail('The index could not answer that request.', 500);
}

function handle_search(): never
{
    throttle('search', 180, 60);
    $started = microtime(true);
    $q = input_string('q', 120);
    $input = [
        'union' => input_string('union', 40),
        'area' => preg_match('/^\d{0,12}$/', input_string('area', 12)) ? input_string('area', 12) : '',
        'gender' => in_array(input_string('gender', 8), ['male', 'female'], true) ? input_string('gender', 8) : '',
        'status' => in_array(input_string('status', 10), ['active', 'cancelled', 'all'], true)
            ? input_string('status', 10) : 'active',
    ];
    $page = input_int('page', 1, 1, 500);
    $limit = input_int('limit', 20, 1, 50);

    $where = [];
    $params = [];
    if ($input['status'] === 'active') {
        $where[] = "v.status = 'ok'";
    } elseif ($input['status'] === 'cancelled') {
        $where[] = "v.status <> 'ok'";
    }
    if ($input['union'] !== '') {
        $where[] = 'f.union_key = ?';
        $params[] = $input['union'];
    }
    if ($input['area'] !== '') {
        $where[] = 'f.area_no = ?';
        $params[] = $input['area'];
    }
    if ($input['gender'] !== '') {
        $where[] = 'f.gender = ?';
        $params[] = $input['gender'];
    }
    if ($where === []) {
        $where[] = '1 = 1';
    }

    $isMysql = repdoc_uses_mysql();
    $order = $isMysql ? 'v.name, v.id' : 'v.name COLLATE NOCASE, v.id';
    $whereParams = $params;
    $orderParams = [];
    $matchField = 'other';

    if ($q !== '') {
        $digits = bn_to_en($q);
        $onlyDigits = preg_replace('/\D/', '', $digits);
        if ($onlyDigits !== '' && preg_match('/^[\d\s\-]+$/', $digits)) {
            if (strlen($onlyDigits) >= 3) {
                if ($isMysql) {
                    $where[] = 'v.voter_no_digits LIKE ?';
                    $whereParams[] = '%' . $onlyDigits . '%';
                } else {
                    $where[] = 'v.id IN (SELECT rowid FROM voters_fts WHERE voters_fts MATCH ? LIMIT 5000)';
                    $whereParams[] = '"' . $onlyDigits . '"';
                }
                $order = 'CASE WHEN v.voter_no_digits = ? THEN 0 ELSE 1 END, v.voter_no_digits, v.id';
                $orderParams[] = $onlyDigits;
            } else {
                $where[] = 'v.serial = ?';
                $whereParams[] = ctype_digit($onlyDigits) ? (int) $onlyDigits : 0;
                $order = 'v.serial, v.id';
            }
            $matchField = 'voter_no';
        } else {
            $tokens = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokens = array_slice($tokens, 0, 6);
            $fts = [];
            $short = [];
            foreach ($tokens as $token) {
                if ($isMysql) {
                    $short[] = $token;
                } elseif (mb_strlen($token, 'UTF-8') >= 3) {
                    $fts[] = '"' . str_replace('"', '""', $token) . '"';
                } else {
                    $short[] = $token;
                }
            }
            if ($fts !== []) {
                $where[] = 'v.id IN (SELECT rowid FROM voters_fts WHERE voters_fts MATCH ? LIMIT 5000)';
                $whereParams[] = implode(' AND ', $fts);
                $matchField = 'name';
            }
            foreach ($short as $index => $token) {
                $like = '%' . like_escape($token) . '%';
                $where[] = '(v.name LIKE ? ESCAPE \'!\' OR v.father LIKE ? ESCAPE \'!\' '
                    . 'OR v.mother LIKE ? ESCAPE \'!\' OR v.address LIKE ? ESCAPE \'!\' '
                    . 'OR v.voter_no_digits LIKE ? ESCAPE \'!\')';
                array_push($whereParams, $like, $like, $like, $like, $like);
            }
            if ($fts === [] && $short === []) {
                $like = '%' . like_escape($q) . '%';
                $where[] = '(v.name LIKE ? ESCAPE \'!\' OR v.voter_no_digits LIKE ? ESCAPE \'!\')';
                $whereParams[] = $like;
                $whereParams[] = $like;
            }
            $prefix = like_escape($q) . '%';
            $prefixSql = 'CASE WHEN v.name LIKE ? ESCAPE \'!\' THEN 0 '
                . 'WHEN v.father LIKE ? ESCAPE \'!\' OR v.mother LIKE ? ESCAPE \'!\' THEN 1 ELSE 2 END';
            $order = $prefixSql . ($isMysql ? ', v.name, v.id' : ', v.name COLLATE NOCASE, v.id');
            array_push($orderParams, $prefix, $prefix, $prefix);
        }
    }

    $adv = [
        'name' => input_string('name', 80),
        'father' => input_string('father', 80),
        'mother' => input_string('mother', 80),
        'voter_no' => input_string('voter_no', 40),
        'address' => input_string('address', 160),
        'occupation' => input_string('occupation', 80),
        'serial_min' => input_string('serial_min', 12),
        'serial_max' => input_string('serial_max', 12),
        'page' => input_string('page_no', 12),
    ];
    $advCols = [
        'name' => 'v.name',
        'father' => 'v.father',
        'mother' => 'v.mother',
        'address' => 'v.address',
        'occupation' => 'v.occupation',
    ];
    foreach ($advCols as $field => $column) {
        $value = $adv[$field];
        if ($value === '') continue;
        if (!$isMysql && mb_strlen($value, 'UTF-8') >= 3) {
            $where[] = 'v.id IN (SELECT rowid FROM voters_fts WHERE voters_fts MATCH ? LIMIT 5000)';
            $whereParams[] = '"' . str_replace('"', '""', $value) . '"';
        }
        $where[] = $column . " LIKE ? ESCAPE '!'";
        $whereParams[] = '%' . like_escape($value) . '%';
    }
    if ($adv['voter_no'] !== '') {
        $digits = preg_replace('/\D/', '', bn_to_en($adv['voter_no']));
        if ($digits !== '') {
            if (!$isMysql && strlen($digits) >= 3) {
                $where[] = 'v.id IN (SELECT rowid FROM voters_fts WHERE voters_fts MATCH ? LIMIT 5000)';
                $whereParams[] = '"' . $digits . '"';
            }
            $where[] = 'v.voter_no_digits LIKE ?';
            $whereParams[] = '%' . $digits . '%';
        }
    }
    foreach (['serial_min' => '>=', 'serial_max' => '<='] as $field => $operator) {
        $number = preg_replace('/\D/', '', bn_to_en($adv[$field]));
        if ($number !== '') {
            $where[] = 'v.serial ' . $operator . ' ?';
            $whereParams[] = (int) $number;
        }
    }
    $pageNo = preg_replace('/\D/', '', bn_to_en($adv['page']));
    if ($pageNo !== '') {
        $where[] = 'v.page = ?';
        $whereParams[] = (int) $pageNo;
    }

    $whereSql = implode(' AND ', $where);
    $from = 'FROM voters v JOIN files f ON f.id = v.file_id';
    $countStmt = db()->prepare(
        'SELECT COUNT(*) FROM (SELECT 1 ' . $from . ' WHERE ' . $whereSql . ' LIMIT 5001) capped'
    );
    $countStmt->execute($whereParams);
    $total = (int) $countStmt->fetchColumn();
    $capped = $total > 2000;
    if ($capped) {
        $total = 2000;
    }

    $offset = ($page - 1) * $limit;
    $sql = 'SELECT v.id, v.name, v.voter_no, v.voter_no_digits, v.serial, v.serial_bn, '
        . 'v.father, v.mother, v.status, v.page, v.col, '
        . 'f.id AS file_id, f.area_no, f.area_name, f.union_key, f.union_name, f.gender '
        . $from . ' WHERE ' . $whereSql
        . ' ORDER BY ' . $order . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute(array_merge($whereParams, $orderParams));
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $row) {
        $field = $matchField;
        if ($q !== '' && $field === 'other') {
            if (mb_stripos((string) $row['name'], $q) !== false) {
                $field = 'name';
            } elseif (mb_stripos((string) $row['father'], $q) !== false) {
                $field = 'father';
            } elseif (mb_stripos((string) $row['mother'], $q) !== false) {
                $field = 'mother';
            } elseif (mb_stripos((string) $row['address'], $q) !== false) {
                $field = 'address';
            }
        }
        $items[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'voter_no' => $row['voter_no'],
            'voter_no_digits' => $row['voter_no_digits'],
            'serial' => (int) $row['serial'],
            'serial_bn' => $row['serial_bn'],
            'father' => $row['father'],
            'mother' => $row['mother'],
            'status' => $row['status'],
            'page' => (int) $row['page'],
            'column' => (int) $row['col'],
            'area' => $row['area_name'],
            'area_no' => $row['area_no'],
            'union' => $row['union_name'],
            'gender' => $row['gender'],
            'match' => $field,
            'pdf' => pdf_token((int) $row['file_id']),
        ];
    }

    json_out([
        'ok' => true,
        'q' => $q,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'has_more' => $capped || $offset + count($items) < $total,
        'capped' => $capped,
        'took_ms' => (int) round((microtime(true) - $started) * 1000),
        'items' => $items,
    ]);
}

function handle_voter(): never
{
    $id = input_int('id', 0, 1, 999999999);
    if ($id === 0) {
        fail('A voter id is required.', 400);
    }
    $stmt = db()->prepare(
        'SELECT v.*, f.name AS file_name, f.pages, f.area_no AS f_area_no, f.area_name AS f_area_name, '
        . 'f.union_key, f.union_name, f.gender AS f_gender, f.district, f.upazila, f.post_office, '
        . 'f.postcode, f.publish_date FROM voters v JOIN files f ON f.id = v.file_id WHERE v.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        fail('No voter matches that id.', 404);
    }
    $hasRect = $row['x0'] !== null && $row['y0'] !== null && $row['x1'] !== null && $row['y1'] !== null;
    json_out([
        'ok' => true,
        'voter' => [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'voter_no' => $row['voter_no'],
            'voter_no_digits' => $row['voter_no_digits'],
            'serial' => (int) $row['serial'],
            'serial_bn' => $row['serial_bn'],
            'father' => $row['father'],
            'mother' => $row['mother'],
            'occupation' => $row['occupation'],
            'dob' => $row['dob'],
            'address' => $row['address'],
            'status' => $row['status'],
            'page' => (int) $row['page'],
            'column' => (int) $row['col'],
            'area' => $row['f_area_name'],
            'area_no' => $row['f_area_no'],
            'union' => $row['union_name'],
            'gender' => $row['f_gender'],
            'district' => $row['district'],
            'upazila' => $row['upazila'],
            'post_office' => $row['post_office'],
            'postcode' => $row['postcode'],
            'publish_date' => $row['publish_date'],
        ],
        'pdf' => [
            'token' => pdf_token((int) $row['file_id']),
            'pages' => (int) $row['pages'],
        ],
        'rect' => $hasRect ? [
            'x0' => (float) $row['x0'],
            'y0' => (float) $row['y0'],
            'x1' => (float) $row['x1'],
            'y1' => (float) $row['y1'],
            'w' => (float) $row['pw'],
            'h' => (float) $row['ph'],
        ] : null,
    ]);
}

function handle_pdf(): never
{
    throttle('pdf', 600, 60);
    $fileId = parse_pdf_token(input_string('t', 40));
    if ($fileId === null) {
        fail('That PDF link is not valid.', 403);
    }
    $stmt = db()->prepare('SELECT name, pages FROM files WHERE id = ?');
    $stmt->execute([$fileId]);
    $row = $stmt->fetch();
    if (!$row) {
        fail('PDF not found.', 404);
    }
    $name = basename((string) $row['name']);
    $path = realpath(PDF_DIR . '/' . $name);
    $base = realpath(PDF_DIR);
    if ($path === false || $base === false || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) {
        fail('PDF not found.', 404);
    }

    $size = (int) filesize($path);
    $start = 0;
    $end = $size - 1;
    $partial = false;
    $range = $_SERVER['HTTP_RANGE'] ?? '';
    if (is_string($range) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $match)) {
        $from = $match[1];
        $to = $match[2];
        if ($from === '' && $to !== '') {
            $length = (int) $to;
            $start = max(0, $size - $length);
        } elseif ($from !== '') {
            $start = (int) $from;
            if ($to !== '') {
                $end = min((int) $to, $size - 1);
            }
        }
        if ($start >= $size || $start > $end) {
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            exit;
        }
        $partial = true;
    }

    $length = $end - $start + 1;
    http_response_code($partial ? 206 : 200);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="voter-list.pdf"');
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $length);
    header('Cache-Control: private, max-age=1800');
    header('X-Content-Type-Options: nosniff');
    if ($partial) {
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        exit;
    }
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        exit;
    }
    fseek($handle, $start);
    $remaining = $length;
    while ($remaining > 0 && !feof($handle)) {
        $chunk = fread($handle, min(65536, $remaining));
        if ($chunk === false || $chunk === '') {
            break;
        }
        echo $chunk;
        $remaining -= strlen($chunk);
    }
    fclose($handle);
    exit;
}

function handle_filters(): never
{
    $unions = db()->query(
        'SELECT union_key, union_name, SUM(records) AS records, COUNT(*) AS files '
        . 'FROM files GROUP BY union_key, union_name ORDER BY union_name'
    )->fetchAll();
    $areas = db()->query(
        'SELECT area_no, area_name, union_key, union_name, SUM(records) AS records, '
        . 'SUM(active) AS active FROM files GROUP BY area_no, area_name, union_key, union_name ORDER BY area_name'
    )->fetchAll();
    $nested = [];
    foreach ($unions as $union) {
        $nested[$union['union_key']] = [
            'key' => $union['union_key'],
            'name' => $union['union_name'],
            'records' => (int) $union['records'],
            'files' => (int) $union['files'],
            'areas' => [],
        ];
    }
    foreach ($areas as $area) {
        $key = $area['union_key'];
        if (!isset($nested[$key])) {
            continue;
        }
        $nested[$key]['areas'][] = [
            'area_no' => $area['area_no'],
            'name' => $area['area_name'],
            'records' => (int) $area['records'],
            'active' => (int) $area['active'],
        ];
    }
    json_out(['ok' => true, 'unions' => array_values($nested)]);
}

function handle_stats(): never
{
    $build = [];
    $buildFile = DATA_DIR . '/build.json';
    if (!is_file($buildFile)) {
        $buildFile = APP_ROOT . '/build.json';
    }
    if (is_file($buildFile)) {
        $build = json_decode((string) file_get_contents($buildFile), true) ?: [];
    }
    $row = db()->query(
        'SELECT COUNT(*) AS files, COALESCE(SUM(pages),0) AS pages, COALESCE(SUM(records),0) AS records, '
        . 'COALESCE(SUM(active),0) AS active FROM files'
    )->fetch();
    $facets = db()->query(
        'SELECT COUNT(DISTINCT area_no) AS areas, COUNT(DISTINCT union_key) AS unions FROM files'
    )->fetch();
    json_out([
        'ok' => true,
        'built_at' => $build['built_at'] ?? null,
        'files' => (int) $row['files'],
        'pages' => (int) $row['pages'],
        'records' => (int) $row['records'],
        'active' => (int) $row['active'],
        'areas' => (int) ($build['areas'] ?? $facets['areas'] ?? 0),
        'unions' => (int) ($build['unions'] ?? $facets['unions'] ?? 0),
    ]);
}
