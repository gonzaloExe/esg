<?php
require_once __DIR__ . '/config.php';

function obtenerUsuarioActual(): ?array {
    $pdo = db(true); if (!$pdo) return null;
    $pc = getPcData();
    try {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE pc_identificador = ? LIMIT 1');
        $stmt->execute([$pc['pc_nombre']]); $u = $stmt->fetch();
        if (!$u) {
            $stmt = $pdo->prepare("INSERT INTO usuarios (pc_identificador,nombre_usuario,rol,activo,fecha_ultima_conexion) VALUES (?,?, 'usuario',1,NOW())");
            $stmt->execute([$pc['pc_nombre'],$pc['usuario']]);
            $id=(int)$pdo->lastInsertId(); $stmt=$pdo->prepare('SELECT * FROM usuarios WHERE id=?'); $stmt->execute([$id]); $u=$stmt->fetch();
        } else {
            $stmt=$pdo->prepare('UPDATE usuarios SET nombre_usuario=?, fecha_ultima_conexion=NOW() WHERE id=?'); $stmt->execute([$pc['usuario'],$u['id']]);
            $u['nombre_usuario']=$pc['usuario']; $u['fecha_ultima_conexion']=date('Y-m-d H:i:s');
        }
        return $u;
    } catch(Throwable $e) { return null; }
}
function requireInstalled(): void { if (!installationReady()) { header('Location: instalar.php'); exit; } }
function requireUserRole(): array {
    requireInstalled(); $u=obtenerUsuarioActual();
    if (!$u) { http_response_code(503); exit('Base de datos no disponible.'); }
    if ($u['rol']==='superadmin') { header('Location: admin.php'); exit; }
    if (!(int)$u['activo']) { http_response_code(403); exit('Este equipo está desactivado.'); }
    return $u;
}
function requireSuperAdmin(): array {
    requireInstalled(); $u=obtenerUsuarioActual();
    if (!$u || $u['rol']!=='superadmin') { header('Location: index.php'); exit; }
    return $u;
}
function apiSuperAdmin(): array {
    $u=obtenerUsuarioActual(); if (!$u || $u['rol']!=='superadmin') jsonResponse(['ok'=>false,'error'=>'Acceso denegado.'],403); return $u;
}
