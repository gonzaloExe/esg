<?php
/**
 * ESG - Configuración y funciones básicas.
 *
 * Las credenciales de MySQL se guardan en includes/config.local.php,
 * archivo generado automáticamente por instalar.php y que NO debe
 * subirse a GitHub.
 */
session_start();

define('ESG_VERSION', '1.0');

// Valores por defecto para una instalación nueva.
$dbConfig = [
    'host' => getenv('ESG_DB_HOST') ?: 'localhost',
    'name' => getenv('ESG_DB_NAME') ?: 'esg',
    'user' => getenv('ESG_DB_USER') ?: 'root',
    'pass' => getenv('ESG_DB_PASS') ?: '',
];

// El instalador genera este archivo con las credenciales reales.
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    $local = require $localConfig;
    if (is_array($local)) {
        $dbConfig = array_merge($dbConfig, $local);
    }
}

define('DB_HOST', $dbConfig['host']);
define('DB_NAME', $dbConfig['name']);
define('DB_USER', $dbConfig['user']);
define('DB_PASS', $dbConfig['pass']);

function db(bool $allowMissing = false): ?PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (Throwable $e) {
        if ($allowMissing) return null;
        throw $e;
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requestJson(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(?string $token): void {
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        jsonResponse(['ok' => false, 'error' => 'Token de seguridad inválido.'], 419);
    }
}

function getPcData(): array {
    $host = gethostname() ?: 'PC-DESCONOCIDA';
    $user = function_exists('get_current_user') ? get_current_user() : '';
    if (!$user) $user = 'usuario_' . substr(md5($host), 0, 6);

    return [
        'pc_nombre' => $host,
        'usuario' => $user,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'No detectada',
        'fecha' => date('Y-m-d H:i:s')
    ];
}

function installationReady(): bool {
    try {
        $pdo = db(true);
        if (!$pdo) return false;

        $q = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='superadmin'");
        return (int)$q->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}
