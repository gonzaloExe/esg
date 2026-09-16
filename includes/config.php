<?php
declare(strict_types=1);

/**
 * ESG - Configuración principal.
 * Las credenciales pueden estar en includes/config.local.php.
 * Ese archivo no debe subirse a Git.
 */
define('APP_NAME', 'ESG — Entorno Seguro y Gestión');
define('APP_VERSION', '1.1.0');
date_default_timezone_set('America/Argentina/Buenos_Aires');

$local = __DIR__ . '/config.local.php';

if (is_file($local)) {
    $cfg = require $local;
    $cfg = is_array($cfg) ? $cfg : [];
} else {
    $cfg = [];
}

define('DB_HOST', (string)($cfg['host'] ?? 'localhost'));
define('DB_NAME', (string)($cfg['name'] ?? 'esg'));
define('DB_USER', (string)($cfg['user'] ?? 'esg_user'));
define('DB_PASS', (string)($cfg['pass'] ?? ''));
define('ADMIN_IP', (string)($cfg['admin_ip'] ?? ''));

function conectarBD(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    return $pdo;
}

function responderJson(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function e(?string $valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function ipCliente(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'desconocida';
}

function estaInstalado(): bool
{
    try {
        $pdo = conectarBD();
        $q = $pdo->query("SHOW TABLES LIKE 'usuarios'");
        if (!$q->fetch()) return false;
        return (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='superadmin'")->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}
