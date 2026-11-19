<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

$pageTitle = 'Painel inicial';
$activePage = 'inicio';
$errorMessage = null;
$counts = ['estudantes' => 0, 'cursos' => 0, 'matriculas' => 0, 'enderecos' => 0];
$recentEnrollments = [];

try {
    $db = mongo_db();
    $counts['estudantes'] = $db->estudantes->countDocuments();
    $counts['cursos'] = $db->cursos->countDocuments();
    $counts['matriculas'] = $db->matriculas->countDocuments();
    $counts['enderecos'] = $db->enderecos->countDocuments();
    $recentEnrollments = collection_documents($db->matriculas, [], ['sort' => ['criado_em' => -1], 'limit' => 5]);
} catch (Throwable $exception) {
    $errorMessage = page_error($exception);
}

require dirname(__DIR__) . '/app/views/header.php';
?>
<?php if ($errorMessage): ?>
    <div class="alert alert-danger"><strong>Atencao:</strong> <?= e($errorMessage) ?> Abra o README.md e confira a extensao PHP, o Composer e a conexao do MongoDB.</div>
<?php endif; ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-people"></i></div><div class="stat-value"><?= number_format($counts['estudantes'], 0, ',', '.') ?></div><div class="stat-label">Estudantes cadastrados</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-journal-bookmark"></i></div><div class="stat-value"><?= number_format($counts['cursos'], 0, ',', '.') ?></div><div class="stat-label">Cursos cadastrados</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-card-checklist"></i></div><div class="stat-value"><?= number_format($counts['matriculas'], 0, ',', '.') ?></div><div class="stat-label">Matriculas registradas</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-geo-alt"></i></div><div class="stat-value"><?= number_format($counts['enderecos'], 0, ',', '.') ?></div><div class="stat-label">Enderecos em colecao separada</div></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <section class="panel h-100">
            <h2 class="panel-title">Comece por aqui</h2>
            <p class="muted-hint mb-4">Cadastre os estudantes e os cursos. Depois, crie as matriculas para relacionar os dois.</p>
            <div class="row g-2">
                <div class="col-md-4"><a class="btn btn-primary w-100 py-3" href="estudantes.php"><i class="bi bi-person-plus me-2"></i>Novo estudante</a></div>
                <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="cursos.php"><i class="bi bi-plus-square me-2"></i>Novo curso</a></div>
                <div class="col-md-4"><a class="btn btn-outline-primary w-100 py-3" href="matriculas.php"><i class="bi bi-link-45deg me-2"></i>Nova matricula</a></div>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <section class="panel h-100">
            <h2 class="panel-title">Modelo de relacionamento</h2>
            <div class="row g-2 align-items-center">
                <div class="col-5"><div class="relationship-card"><strong>ESTUDANTES</strong><span>Dados pessoais</span></div></div>
                <div class="col-2 text-center text-primary"><i class="bi bi-arrow-left-right fs-4"></i></div>
                <div class="col-5"><div class="relationship-card"><strong>CURSOS</strong><span>Dados dos cursos</span></div></div>
                <div class="col-12 text-center text-primary"><i class="bi bi-arrow-down fs-4"></i></div>
                <div class="col-12"><div class="relationship-card"><strong>MATRICULAS</strong><span>Contem estudante_id e curso_id (ObjectId)</span></div></div>
            </div>
            <p class="muted-hint mt-3 mb-0">O endereco tambem fica separado, ligado ao estudante por estudante_id.</p>
        </section>
    </div>
</div>
<div class="panel mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="panel-title mb-0">Matriculas recentes</h2><a href="matriculas.php" class="small text-decoration-none">Ver todas <i class="bi bi-arrow-right"></i></a></div>
    <?php if (!$recentEnrollments): ?>
        <div class="empty-state"><i class="bi bi-inbox"></i>Nenhuma matricula cadastrada ainda.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Estudante</th><th>Curso</th><th>Periodo</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($recentEnrollments as $enrollment): ?>
            <?php
            $student = $db->estudantes->findOne(['_id' => $enrollment['estudante_id']]);
            $course = $db->cursos->findOne(['_id' => $enrollment['curso_id']]);
            ?>
            <tr><td><?= e($student['nome'] ?? 'Estudante removido') ?></td><td><?= e($course['nome'] ?? 'Curso removido') ?></td><td><?= e(($enrollment['semestre'] ?? '-') . 'º semestre / ' . ($enrollment['ano'] ?? '-')) ?></td><td><?= status_badge((string) ($enrollment['status'] ?? 'Ativa')) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
