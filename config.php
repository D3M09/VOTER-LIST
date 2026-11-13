<?php
declare(strict_types=1);

/**
 * VoterSearch — access guard + shared paths.
 *
 * Login-only: any authenticated Repdoc user (admin or approved UDC) may use
 * this tool. No per-application grant and no credit charge.
 */

if (!defined('REPDOC_ROOT')) {
    $root = getenv('REPDOC_ROOT');
    if (!is_string($root) || $root === '' || !is_file($root . '/src/bootstrap.php')) {
        $root = dirname(__DIR__, 2); // tools/votersearch → project root
    }
    define('REPDOC_ROOT', $root);
}
require_once REPDOC_ROOT . '/src/bootstrap.php';

if (!defined('REPDOC_SKIP_AUTH') && !is_logged_in()) {
    $isApi = strpos((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/api/') !== false;
    if ($isApi) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => false, 'error' => 'Login required.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    require_login();
}

if (!defined('APP_ROOT'))    define('APP_ROOT', __DIR__);
if (!defined('DATA_DIR'))    define('DATA_DIR', APP_ROOT . '/data');
if (!defined('JSON_DIR'))    define('JSON_DIR', is_dir(APP_ROOT . '/json') ? APP_ROOT . '/json' : DATA_DIR . '/json');
if (!defined('COORD_DIR'))   define('COORD_DIR', is_dir(APP_ROOT . '/coords') ? APP_ROOT . '/coords' : DATA_DIR . '/coords');
if (!defined('PDF_DIR'))     define('PDF_DIR', is_dir(APP_ROOT . '/pdf') ? APP_ROOT . '/pdf' : DATA_DIR . '/pdfs');
if (!defined('README_PATH')) define('README_PATH', is_file(APP_ROOT . '/README.md') ? APP_ROOT . '/README.md' : DATA_DIR . '/SOURCE-README.md');
if (!defined('DB_PATH'))     define('DB_PATH', DATA_DIR . '/voters.sqlite');
if (!defined('SECRET_PATH')) define('SECRET_PATH', DATA_DIR . '/secret.key');

if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0755, true);
}
if (is_dir(DATA_DIR) && !is_file(DATA_DIR . '/.htaccess')) {
    @file_put_contents(
        DATA_DIR . '/.htaccess',
        "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n"
    );
}
