<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/**
 * Identificación del cliente:
 * PHP no puede obtener el hostname de Windows de un navegador.
 * ESG usa REMOTE_ADDR como identificador de cada PC de la red.
 */

function obtenerDatosPC(): array
{
    $ip = ipCliente();
    return [
        'pc_nombre' => 'PC-' . str_replace(['.', ':'], '-', $ip),
        'usuario' => 'Usuario de red',
        'ip' => $ip,
        'fecha' => date('Y-m-d H:i:s')
    ];
}

function obtenerUsuarioActual(): ?array
{
    static $cache = false;
    if ($cache !== false) return $cache;

    if (!estaInstalado()) {
        $cache = null;
        return null;
    }

    $datos = obtenerDatosPC();
    $stmt = conectarBD()->prepare('SELECT * FROM usuarios WHERE pc_identificador=? LIMIT 1');
    $stmt->execute([$datos['ip']]);
    $u = $stmt->fetch();

    if ($u) {
        conectarBD()->prepare('UPDATE usuarios SET nombre_usuario=?, fecha_ultima_conexion=NOW() WHERE id=?')
            ->execute([$datos['usuario'], $u['id']]);
        $u['nombre_usuario'] = $datos['usuario'];
    }

    $cache = $u ?: null;
    return $cache;
}

function registrarUsuarioActual(): array
{
    $datos = obtenerDatosPC();
    $pdo = conectarBD();

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE pc_identificador=? LIMIT 1');
    $stmt->execute([$datos['ip']]);
    $u = $stmt->fetch();

    if ($u) {
        $pdo->prepare('UPDATE usuarios SET nombre_usuario=?, fecha_ultima_conexion=NOW() WHERE id=?')
            ->execute([$datos['usuario'], $u['id']]);
        $u['nombre_usuario'] = $datos['usuario'];
        return $u;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO usuarios (pc_identificador,nombre_usuario,rol,activo,fecha_ultima_conexion,estado_pc)
         VALUES (?,?,'usuario',TRUE,NOW(),'buena')"
    );
    $stmt->execute([$datos['ip'], $datos['usuario']]);

    return [
        'id'=>(int)$pdo->lastInsertId(),
        'pc_identificador'=>$datos['ip'],
        'nombre_usuario'=>$datos['usuario'],
        'rol'=>'usuario',
        'activo'=>1,
        'estado_pc'=>'buena'
    ];
}

function esSuperAdmin(?array $u): bool
{
    if (!$u || ($u['rol'] ?? '') !== 'superadmin' || (int)($u['activo'] ?? 0) !== 1) return false;
    return ADMIN_IP === '' || ($u['pc_identificador'] ?? '') === ADMIN_IP;
}

function exigirInstalado(bool $api=false): void
{
    if (!estaInstalado()) {
        if ($api) responderJson(['ok'=>false,'error'=>'El sistema no está instalado.'],503);
        header('Location: instalar.php');
        exit;
    }
}

function exigirUsuarioComun(): array
{
    exigirInstalado();
    $u = obtenerUsuarioActual();
    if (!$u) $u = registrarUsuarioActual();
    if (esSuperAdmin($u)) { header('Location: admin.php'); exit; }
    if (!(int)$u['activo']) { http_response_code(403); exit('Esta PC está desactivada por el SuperAdmin.'); }
    return $u;
}

function exigirSuperAdmin(): array
{
    exigirInstalado();
    $u = obtenerUsuarioActual();
    if (!$u || !esSuperAdmin($u)) { header('Location: index.php'); exit; }
    return $u;
}

function exigirApiUsuarioComun(): array
{
    exigirInstalado(true);
    $u = obtenerUsuarioActual();
    if (!$u) $u = registrarUsuarioActual();
    if (esSuperAdmin($u)) responderJson(['ok'=>false,'error'=>'El SuperAdmin no puede utilizar este endpoint.'],403);
    if (!(int)$u['activo']) responderJson(['ok'=>false,'error'=>'Esta PC está desactivada.'],403);
    return $u;
}

function exigirApiSuperAdmin(): array
{
    exigirInstalado(true);
    $u = obtenerUsuarioActual();
    if (!$u || !esSuperAdmin($u)) responderJson(['ok'=>false,'error'=>'Acceso no autorizado.'],403);
    return $u;
}
