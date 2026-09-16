<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
$usuario=exigirUsuarioComun(); $datos=obtenerDatosPC();
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(APP_NAME)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="assets/css/estilo.css" rel="stylesheet"></head><body>
<nav class="navbar navbar-dark esg-navbar"><div class="container"><span class="navbar-brand fw-bold"><i class="fa-solid fa-shield-halved me-2"></i>ESG</span><span class="text-white small">Gestión de incidencias</span></div></nav>
<main class="container py-4"><div class="row g-4">
<div class="col-12"><div class="card shadow-sm border-0"><div class="card-body">
<div class="d-flex flex-wrap justify-content-between gap-3"><div><h1 class="h3">Solicitud de asistencia</h1><p class="text-muted mb-0">Enviá una incidencia al SuperAdmin.</p></div>
<div class="pc-info"><div><b>PC:</b> <?=e($datos['pc_nombre'])?></div><div><b>IP:</b> <?=e($datos['ip'])?></div><div><b>Usuario:</b> <?=e($datos['usuario'])?></div></div></div><hr>
<div class="alert alert-<?=$usuario['estado_pc']==='fallando'?'danger':($usuario['estado_pc']==='lenta'?'warning':'success')?>"><b>Estado:</b> <?=$usuario['estado_pc']==='buena'?'🟢 Buena':($usuario['estado_pc']==='lenta'?'🟡 Lenta':'🔴 Fallando')?></div>
</div></div></div>
<div class="col-lg-5"><div class="card shadow-sm border-0 h-100"><div class="card-body">
<h2 class="h5"> <i class="fa-solid fa-ticket me-2"></i>Nuevo ticket</h2>
<form id="form-ticket" enctype="multipart/form-data"><div class="mb-3"><label class="form-label">Título</label><input class="form-control form-control-lg" name="titulo" maxlength="255" required></div>
<div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="6" minlength="10" required></textarea></div>
<div class="mb-3"><label class="form-label">Foto (opcional)</label><input class="form-control" type="file" name="foto" accept=".jpg,.jpeg,.png,image/jpeg,image/png"><div class="form-text">JPG/JPEG/PNG, máximo 5 MB.</div></div>
<button class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-paper-plane me-2"></i>Enviar ticket</button></form>
</div></div></div>
<div class="col-lg-7"><div class="card shadow-sm border-0"><div class="card-body"><h2 class="h5"><i class="fa-solid fa-list-check me-2"></i>Mis tickets</h2><div id="mis-tickets">Cargando...</div></div></div></div>
</div></main><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script src="assets/js/app.js"></script></body></html>
