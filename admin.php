<?php
declare(strict_types=1);
require_once __DIR__.'/includes/auth.php';
$admin=exigirSuperAdmin();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(APP_NAME)?> — Administración</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="assets/css/estilo.css" rel="stylesheet"></head><body>
<nav class="navbar navbar-dark esg-navbar"><div class="container"><span class="navbar-brand fw-bold"><i class="fa-solid fa-shield-halved me-2"></i>ESG — SuperAdmin</span></div></nav>
<main class="container py-4"><div class="row g-3 mb-4">
<?php foreach([['total','Total'],['pendientes','Pendientes'],['aprobados','Aprobados'],['rechazados','Rechazados'],['resueltos','Resueltos'],['usuarios','Usuarios']] as $x): ?>
<div class="col-6 col-lg-2"><div class="stat-card"><span><?=$x[1]?></span><strong id="s-<?=$x[0]?>">0</strong></div></div>
<?php endforeach; ?></div>
<ul class="nav nav-tabs mb-3"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tickets">Tickets</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#users">Usuarios</button></li></ul>
<div class="tab-content"><div class="tab-pane fade show active" id="tickets"><div class="card shadow-sm border-0"><div class="card-body"><div class="table-responsive" id="todos-tickets">Cargando...</div></div></div></div>
<div class="tab-pane fade" id="users"><div class="card shadow-sm border-0"><div class="card-body"><div class="table-responsive" id="usuarios">Cargando...</div></div></div></div></div>
</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script src="assets/js/app.js"></script></body></html>
