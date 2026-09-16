<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
$instalado=false;$mensaje='';$error='';
try{$pdo=conectarBD();if($pdo->query("SHOW TABLES LIKE 'usuarios'")->fetch())$instalado=(int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='superadmin'")->fetchColumn()>0;}catch(Throwable){}
if($_SERVER['REQUEST_METHOD']==='POST'&&!$instalado){
$host=trim((string)($_POST['db_host']??'localhost'));$name=trim((string)($_POST['db_name']??'esg'));$user=trim((string)($_POST['db_user']??''));$pass=(string)($_POST['db_pass']??'');
if($host===''||$name===''||$user==='')$error='Completá los datos de MySQL.';
elseif(!preg_match('/^[A-Za-z0-9_]+$/',$name))$error='Nombre de BD inválido.';
else try{
$pdo=new PDO('mysql:host='.$host.';charset=utf8mb4',$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `'.str_replace('`','``',$name).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$pdo->exec('USE `'.str_replace('`','``',$name).'`');
$sql=file_get_contents(__DIR__.'/database.sql');$sql=preg_replace('/^\s*--.*$/m','',(string)$sql);
foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql) as $q){$q=trim($q);if($q!=='')$pdo->exec($q);}
$ip=ipCliente();$pdo->exec("UPDATE usuarios SET rol='usuario' WHERE rol='superadmin'");
$s=$pdo->prepare("INSERT INTO usuarios(pc_identificador,nombre_usuario,rol,activo,fecha_ultima_conexion,estado_pc) VALUES(?,?,'superadmin',TRUE,NOW(),'buena') ON DUPLICATE KEY UPDATE rol='superadmin',activo=TRUE,nombre_usuario=VALUES(nombre_usuario)");
$s->execute([$ip,'Administrador']);
$local=__DIR__.'/includes/config.local.php';
$data="<?php\nreturn ".var_export(['host'=>$host,'name'=>$name,'user'=>$user,'pass'=>$pass,'admin_ip'=>$ip],true).";\n";
if(file_put_contents($local,$data,LOCK_EX)===false)throw new RuntimeException('No se pudo crear includes/config.local.php.');
@chmod($local,0640);if(!is_dir(__DIR__.'/uploads'))mkdir(__DIR__.'/uploads',0755,true);
if(!is_writable(__DIR__.'/uploads'))throw new RuntimeException('uploads no tiene permisos de escritura.');
$mensaje='Instalación completada. Esta PC es el SuperAdmin.';$instalado=true;
}catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar ESG</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/css/estilo.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5" style="max-width:760px"><div class="card shadow border-0"><div class="card-body p-4 p-md-5"><h1 class="h3">Instalación ESG</h1>
<?php if($mensaje):?><div class="alert alert-success"><b><?=e($mensaje)?></b><br><a class="btn btn-success mt-3" href="admin.php">Ir al panel SuperAdmin</a></div>
<?php elseif($instalado):?><div class="alert alert-warning">El sistema ya está instalado. Eliminá instalar.php.</div>
<?php else: if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="alert alert-info">La IP de esta PC será SuperAdmin: <b><?=e(ipCliente())?></b></div><form method="post">
<div class="mb-3"><label class="form-label">Host MySQL</label><input class="form-control form-control-lg" name="db_host" value="localhost" required></div>
<div class="mb-3"><label class="form-label">Base de datos</label><input class="form-control form-control-lg" name="db_name" value="esg" required></div>
<div class="mb-3"><label class="form-label">Usuario MySQL</label><input class="form-control form-control-lg" name="db_user" value="esg_user" required></div>
<div class="mb-3"><label class="form-label">Contraseña</label><input class="form-control form-control-lg" type="password" name="db_pass"></div>
<button class="btn btn-primary btn-lg w-100">Instalar ESG</button></form><?php endif;?></div></div></div></body></html>
