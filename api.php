<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

function idPost(): int {
    $id = filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
    if (!$id || $id < 1) responderJson(['ok'=>false,'error'=>'ID inválido.'],422);
    return $id;
}

function guardarFoto(array $a): ?string
{
    if (($a['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($a['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) responderJson(['ok'=>false,'error'=>'Error al subir la foto.'],422);
    if (($a['size'] ?? 0) > 5*1024*1024) responderJson(['ok'=>false,'error'=>'La foto supera los 5 MB.'],422);

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($a['tmp_name']);
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png'][$mime] ?? null;
    if (!$ext || @getimagesize($a['tmp_name']) === false) responderJson(['ok'=>false,'error'=>'Solo se permiten JPG, JPEG y PNG válidos.'],422);

    $name = bin2hex(random_bytes(16)).'.'.$ext;
    $dir = __DIR__.'/uploads';
    if (!is_dir($dir)) mkdir($dir,0755,true);
    if (!move_uploaded_file($a['tmp_name'],$dir.'/'.$name)) responderJson(['ok'=>false,'error'=>'No se pudo guardar la foto.'],500);
    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($accion) {
        case 'obtener_mis_tickets':
            $u=exigirApiUsuarioComun();
            $s=conectarBD()->prepare('SELECT * FROM tickets WHERE pc_origen=? ORDER BY fecha DESC');
            $s->execute([$u['pc_identificador']]);
            responderJson(['ok'=>true,'tickets'=>$s->fetchAll()]);
        case 'obtener_todos_tickets':
            exigirApiSuperAdmin();
            responderJson(['ok'=>true,'tickets'=>conectarBD()->query('SELECT * FROM tickets ORDER BY fecha DESC')->fetchAll()]);
        case 'obtener_usuarios':
            exigirApiSuperAdmin();
            responderJson(['ok'=>true,'usuarios'=>conectarBD()->query('SELECT * FROM usuarios ORDER BY rol DESC,fecha_creacion ASC')->fetchAll()]);
        case 'obtener_estadisticas':
            exigirApiSuperAdmin();
            $pdo=conectarBD();
            $s=$pdo->query("SELECT
                COUNT(*) total,
                COALESCE(SUM(estado='pendiente'),0) pendientes,
                COALESCE(SUM(estado='aprobado'),0) aprobados,
                COALESCE(SUM(estado='rechazado'),0) rechazados,
                COALESCE(SUM(estado='resuelto'),0) resueltos
                FROM tickets")->fetch();
            $s['usuarios']=(int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
            foreach ($s as $k=>$v) $s[$k]=(int)$v;
            responderJson(['ok'=>true,'estadisticas'=>$s]);
        default: responderJson(['ok'=>false,'error'=>'Acción GET no válida.'],404);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($accion) {
        case 'crear_ticket':
            $u=exigirApiUsuarioComun();
            $titulo=trim((string)($_POST['titulo']??''));
            $desc=trim((string)($_POST['descripcion']??''));
            if ($titulo==='' || mb_strlen($titulo)>255) responderJson(['ok'=>false,'error'=>'Título inválido.'],422);
            if (mb_strlen($desc)<10) responderJson(['ok'=>false,'error'=>'La descripción debe tener al menos 10 caracteres.'],422);
            $foto=isset($_FILES['foto'])?guardarFoto($_FILES['foto']):null;
            $s=conectarBD()->prepare("INSERT INTO tickets(titulo,descripcion,foto,pc_origen,usuario_origen,fecha,estado) VALUES(?,?,?,?,?,NOW(),'pendiente')");
            $s->execute([$titulo,$desc,$foto,$u['pc_identificador'],$u['nombre_usuario']]);
            responderJson(['ok'=>true,'mensaje'=>'Ticket enviado correctamente.']);

        case 'aprobar_ticket':
            $a=exigirApiSuperAdmin(); $id=idPost();
            conectarBD()->prepare("UPDATE tickets SET estado='aprobado',aprobado_por=?,fecha_aprobacion=NOW(),rechazado_por=NULL,fecha_rechazo=NULL,motivo_rechazo=NULL WHERE id=?")->execute([$a['pc_identificador'],$id]);
            responderJson(['ok'=>true,'mensaje'=>'Ticket aprobado.']);

        case 'rechazar_ticket':
            $a=exigirApiSuperAdmin(); $id=idPost(); $motivo=trim((string)($_POST['motivo']??''));
            if ($motivo==='') responderJson(['ok'=>false,'error'=>'El motivo es obligatorio.'],422);
            conectarBD()->prepare("UPDATE tickets SET estado='rechazado',rechazado_por=?,fecha_rechazo=NOW(),motivo_rechazo=?,aprobado_por=NULL,fecha_aprobacion=NULL WHERE id=?")->execute([$a['pc_identificador'],$motivo,$id]);
            responderJson(['ok'=>true,'mensaje'=>'Ticket rechazado.']);

        case 'resolver_ticket':
            $a=exigirApiSuperAdmin(); $id=idPost();
            conectarBD()->prepare("UPDATE tickets SET estado='resuelto',resuelto_por=?,fecha_resolucion=NOW() WHERE id=?")->execute([$a['pc_identificador'],$id]);
            responderJson(['ok'=>true,'mensaje'=>'Ticket resuelto.']);

        case 'eliminar_ticket':
            exigirApiSuperAdmin(); $id=idPost(); $pdo=conectarBD();
            $s=$pdo->prepare('SELECT foto FROM tickets WHERE id=?'); $s->execute([$id]); $t=$s->fetch();
            $pdo->prepare('DELETE FROM tickets WHERE id=?')->execute([$id]);
            if ($t && $t['foto']) { $f=__DIR__.'/uploads/'.basename($t['foto']); if (is_file($f)) @unlink($f); }
            responderJson(['ok'=>true,'mensaje'=>'Ticket eliminado.']);

        case 'asignar_estado_pc':
            exigirApiSuperAdmin(); $id=idPost(); $estado=$_POST['estado']??'';
            if (!in_array($estado,['buena','lenta','fallando'],true)) responderJson(['ok'=>false,'error'=>'Estado inválido.'],422);
            conectarBD()->prepare('UPDATE usuarios SET estado_pc=? WHERE id=? AND rol="usuario"')->execute([$estado,$id]);
            responderJson(['ok'=>true,'mensaje'=>'Estado de PC actualizado.']);

        case 'desactivar_usuario':
            $a=exigirApiSuperAdmin(); $id=idPost();
            if ($id===(int)$a['id']) responderJson(['ok'=>false,'error'=>'No podés desactivar al SuperAdmin.'],422);
            conectarBD()->prepare('UPDATE usuarios SET activo=IF(activo=1,0,1) WHERE id=? AND rol="usuario"')->execute([$id]);
            responderJson(['ok'=>true,'mensaje'=>'Estado del usuario actualizado.']);

        default: responderJson(['ok'=>false,'error'=>'Acción POST no válida.'],404);
    }
}
responderJson(['ok'=>false,'error'=>'Método no permitido.'],405);
