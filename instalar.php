<?php
require_once __DIR__.'/includes/config.php';
$mensaje='';$error='';
if(installationReady()) $error='El sistema ya está instalado. Por seguridad, elimine instalar.php.';
if($_SERVER['REQUEST_METHOD']==='POST' && !$error){
 $host=trim($_POST['host']??'localhost');$user=trim($_POST['user']??'');$pass=$_POST['pass']??'';$name=trim($_POST['name']??'esg');
 try{
  $pdo=new PDO("mysql:host={$host};charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $safe=str_replace('`','',$name);$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$pdo->exec("USE `{$safe}`");
  $sql=file_get_contents(__DIR__.'/database.sql'); foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $stmt) $pdo->exec($stmt);
  $pc=getPcData();$s=$pdo->prepare("SELECT id FROM usuarios WHERE pc_identificador=?");$s->execute([$pc['pc_nombre']]);if(!$s->fetch()){$s=$pdo->prepare("INSERT INTO usuarios(pc_identificador,nombre_usuario,rol,activo) VALUES(?,?, 'superadmin',1)");$s->execute([$pc['pc_nombre'],$pc['usuario']]);}else{$s=$pdo->prepare("UPDATE usuarios SET rol='superadmin',activo=1 WHERE pc_identificador=?");$s->execute([$pc['pc_nombre']]);}
  if(!is_dir(__DIR__.'/uploads'))mkdir(__DIR__.'/uploads',0755,true);if(!is_writable(__DIR__.'/uploads'))throw new RuntimeException('La carpeta uploads no tiene permisos de escritura.');
  $mensaje='Instalación completada. Esta PC es el SuperAdmin.';
 }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar ESG</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/estilo.css"></head><body><main class="container py-5" style="max-width:700px"><div class="card p-4"><h1>ESG — Instalación</h1><p>Configure la conexión MySQL. La PC que ejecute esta instalación será el único SuperAdmin.</p><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><?php if($mensaje):?><div class="alert alert-success"><?=e($mensaje)?></div><a class="btn btn-primary" href="admin.php">Ir al panel SuperAdmin</a><?php elseif(!$error):?><form method="post"><label class="form-label">Host</label><input class="form-control mb-3" name="host" value="localhost" required><label class="form-label">Usuario MySQL</label><input class="form-control mb-3" name="user" required><label class="form-label">Contraseña</label><input class="form-control mb-3" type="password" name="pass"><label class="form-label">Base de datos</label><input class="form-control mb-3" name="name" value="esg" required><button class="btn btn-primary w-100">Instalar ESG</button></form><?php endif;?></div></main></body></html>
