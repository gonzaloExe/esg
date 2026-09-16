<?php
require_once __DIR__.'/includes/auth.php';
header('Cache-Control: no-store');
$pdo=db(); $method=$_SERVER['REQUEST_METHOD']; $action=$_GET['accion'] ?? ($_POST['accion'] ?? '');

if ($method==='GET') {
 if ($action==='obtener_mis_tickets') { $u=obtenerUsuarioActual(); if(!$u) jsonResponse(['ok'=>false,'error'=>'Usuario no disponible'],503); if($u['rol']==='superadmin') jsonResponse(['ok'=>false,'error'=>'Acción no disponible para SuperAdmin'],403); $s=$pdo->prepare('SELECT * FROM tickets WHERE pc_origen=? ORDER BY fecha DESC'); $s->execute([$u['pc_identificador']]); jsonResponse(['ok'=>true,'tickets'=>$s->fetchAll()]); }
 $u=apiSuperAdmin();
 if($action==='obtener_todos_tickets'){ $s=$pdo->query('SELECT * FROM tickets ORDER BY fecha DESC'); jsonResponse(['ok'=>true,'tickets'=>$s->fetchAll()]); }
 if($action==='obtener_usuarios'){ $s=$pdo->query('SELECT id,pc_identificador,nombre_usuario,rol,activo,fecha_creacion,fecha_ultima_conexion,estado_pc FROM usuarios ORDER BY fecha_ultima_conexion DESC'); jsonResponse(['ok'=>true,'usuarios'=>$s->fetchAll()]); }
 if($action==='obtener_estadisticas'){ $r=$pdo->query("SELECT COUNT(*) total, SUM(estado='pendiente') pendientes, SUM(estado='aprobado') aprobados, SUM(estado='rechazado') rechazados, SUM(estado='resuelto') resueltos FROM tickets")->fetch(); $r['usuarios']=(int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='usuario'")->fetchColumn(); jsonResponse(['ok'=>true,'estadisticas'=>$r]); }
 jsonResponse(['ok'=>false,'error'=>'Acción GET no válida'],400);
}

$data=array_merge($_POST, requestJson()); verifyCsrf($data['csrf'] ?? null);
if($action==='crear_ticket') {
 $u=obtenerUsuarioActual(); if(!$u || $u['rol']==='superadmin') jsonResponse(['ok'=>false,'error'=>'No autorizado'],403); if(!(int)$u['activo']) jsonResponse(['ok'=>false,'error'=>'PC desactivada'],403);
 $titulo=trim($data['titulo']??''); $desc=trim($data['descripcion']??''); if($titulo===''||mb_strlen($titulo)>255||mb_strlen($desc)<10) jsonResponse(['ok'=>false,'error'=>'Título obligatorio y descripción mínima de 10 caracteres.'],422);
 $foto=null;
 if(isset($_FILES['foto']) && $_FILES['foto']['error']!==UPLOAD_ERR_NO_FILE){ $f=$_FILES['foto']; if($f['error']!==UPLOAD_ERR_OK||$f['size']>5*1024*1024) jsonResponse(['ok'=>false,'error'=>'Foto inválida o mayor a 5 MB.'],422); $allowed=['image/jpeg'=>'jpg','image/png'=>'png']; $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); if(!isset($allowed[$mime])) jsonResponse(['ok'=>false,'error'=>'Solo JPG, JPEG y PNG.'],422); $foto=bin2hex(random_bytes(16)).'.'.$allowed[$mime]; if(!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads',0755,true); if(!move_uploaded_file($f['tmp_name'],__DIR__.'/uploads/'.$foto)) jsonResponse(['ok'=>false,'error'=>'No se pudo guardar la foto.'],500); }
 $s=$pdo->prepare('INSERT INTO tickets(titulo,descripcion,foto,pc_origen,usuario_origen) VALUES(?,?,?,?,?)'); $s->execute([$titulo,$desc,$foto,$u['pc_identificador'],$u['nombre_usuario']]); jsonResponse(['ok'=>true,'message'=>'Ticket creado.']);
}
$u=apiSuperAdmin();
$id=(int)($data['id']??0); if($id<1) jsonResponse(['ok'=>false,'error'=>'ID inválido'],422);
if($action==='aprobar_ticket'){ $s=$pdo->prepare("UPDATE tickets SET estado='aprobado', aprobado_por=?, fecha_aprobacion=NOW(), rechazado_por=NULL, motivo_rechazo=NULL WHERE id=?"); $s->execute([$u['pc_identificador'],$id]); jsonResponse(['ok'=>true]); }
if($action==='rechazar_ticket'){ $motivo=trim($data['motivo']??''); if($motivo==='') jsonResponse(['ok'=>false,'error'=>'Indique el motivo.'],422); $s=$pdo->prepare("UPDATE tickets SET estado='rechazado', rechazado_por=?, fecha_rechazo=NOW(), motivo_rechazo=? WHERE id=?"); $s->execute([$u['pc_identificador'],$motivo,$id]); jsonResponse(['ok'=>true]); }
if($action==='resolver_ticket'){ $s=$pdo->prepare("UPDATE tickets SET estado='resuelto', resuelto_por=?, fecha_resolucion=NOW() WHERE id=?"); $s->execute([$u['pc_identificador'],$id]); jsonResponse(['ok'=>true]); }
if($action==='eliminar_ticket'){ $s=$pdo->prepare('SELECT foto FROM tickets WHERE id=?'); $s->execute([$id]); $foto=$s->fetchColumn(); $s=$pdo->prepare('DELETE FROM tickets WHERE id=?'); $s->execute([$id]); if($foto) @unlink(__DIR__.'/uploads/'.$foto); jsonResponse(['ok'=>true]); }
if($action==='asignar_estado_pc'){ $estado=$data['estado']??''; if(!in_array($estado,['buena','lenta','fallando'],true)) jsonResponse(['ok'=>false,'error'=>'Estado inválido'],422); $uid=(int)($data['usuario_id']??0); $s=$pdo->prepare('UPDATE usuarios SET estado_pc=? WHERE id=? AND rol="usuario"'); $s->execute([$estado,$uid]); jsonResponse(['ok'=>true]); }
if($action==='desactivar_usuario'){ $uid=(int)($data['usuario_id']??0); $s=$pdo->prepare('UPDATE usuarios SET activo=NOT activo WHERE id=? AND rol="usuario"'); $s->execute([$uid]); jsonResponse(['ok'=>true]); }
jsonResponse(['ok'=>false,'error'=>'Acción no válida'],400);
