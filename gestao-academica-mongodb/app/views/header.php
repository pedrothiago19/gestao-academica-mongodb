<?php
$pageTitle = $pageTitle ?? 'Gestao Academica';
$activePage = $activePage ?? 'inicio';
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Gestao Academica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand fw-semibold" href="index.php"><i class="bi bi-mortarboard-fill me-2"></i>Gestao Academica</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir navegacao"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <div class="navbar-nav ms-auto gap-lg-2">
                <a class="nav-link <?= $activePage === 'inicio' ? 'active' : '' ?>" href="index.php"><i class="bi bi-grid me-1"></i>Inicio</a>
                <a class="nav-link <?= $activePage === 'estudantes' ? 'active' : '' ?>" href="estudantes.php"><i class="bi bi-people me-1"></i>Estudantes</a>
                <a class="nav-link <?= $activePage === 'cursos' ? 'active' : '' ?>" href="cursos.php"><i class="bi bi-journal-bookmark me-1"></i>Cursos</a>
                <a class="nav-link <?= $activePage === 'matriculas' ? 'active' : '' ?>" href="matriculas.php"><i class="bi bi-card-checklist me-1"></i>Matriculas</a>
            </div>
        </div>
    </div>
</nav>
<main class="container-fluid px-3 px-lg-4 py-4 app-main">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <div class="eyebrow">SISTEMA ACADEMICO</div>
            <h1 class="page-title mb-0"><?= e($pageTitle) ?></h1>
        </div>
        <span class="connection-label"><span class="connection-dot"></span>MongoDB</span>
    </div>
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
