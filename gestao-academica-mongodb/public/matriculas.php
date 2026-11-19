<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

use MongoDB\BSON\UTCDateTime;

$pageTitle = 'Matriculas';
$activePage = 'matriculas';
$db = mongo_db();
$studentCollection = $db->estudantes;
$courseCollection = $db->cursos;
$enrollmentCollection = $db->matriculas;
$errors = [];
$students = collection_documents($studentCollection, ['status' => 'Ativo'], ['sort' => ['nome' => 1]]);
$courses = collection_documents($courseCollection, ['status' => 'Ativo'], ['sort' => ['nome' => 1]]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if (post_string('action') === 'delete') {
            $enrollmentCollection->deleteOne(['_id' => object_id(post_string('id'))]);
            set_flash('success', 'Matricula excluida com sucesso.');
            redirect('matriculas.php');
        }

        if (post_string('action') === 'save') {
            $studentId = object_id(post_string('estudante_id'));
            $courseId = object_id(post_string('curso_id'));
            $semester = post_string('semestre');
            $year = filter_var(post_string('ano'), FILTER_VALIDATE_INT);
            $enrollmentDate = post_string('data_matricula');
            $status = post_string('status') ?: 'Ativa';

            if (!$studentCollection->findOne(['_id' => $studentId])) $errors[] = 'Estudante selecionado nao encontrado.';
            if (!$courseCollection->findOne(['_id' => $courseId])) $errors[] = 'Curso selecionado nao encontrado.';
            if (!in_array($semester, ['1', '2'], true)) $errors[] = 'Selecione o semestre.';
            if ($year === false || $year < 2000 || $year > 2100) $errors[] = 'Informe um ano valido.';
            if ($enrollmentDate === '' || strtotime($enrollmentDate) === false) $errors[] = 'Informe uma data de matricula valida.';
            if (!in_array($status, ['Ativa', 'Trancada', 'Concluida', 'Cancelada'], true)) $errors[] = 'Status de matricula invalido.';

            if (!$errors) {
                $duplicate = $enrollmentCollection->findOne([
                    'estudante_id' => $studentId,
                    'curso_id' => $courseId,
                    'semestre' => (int) $semester,
                    'ano' => $year,
                ]);
                if ($duplicate) $errors[] = 'Esse estudante ja possui matricula nesse curso, semestre e ano.';
            }

            if (!$errors) {
                $now = new UTCDateTime();
                $enrollmentCollection->insertOne([
                    'estudante_id' => $studentId,
                    'curso_id' => $courseId,
                    'semestre' => (int) $semester,
                    'ano' => $year,
                    'data_matricula' => $enrollmentDate,
                    'status' => $status,
                    'criado_em' => $now,
                    'atualizado_em' => $now,
                ]);
                set_flash('success', 'Matricula registrada com sucesso.');
                redirect('matriculas.php');
            }
        }
    } catch (Throwable $exception) {
        $errors[] = page_error($exception);
    }
}

$enrollments = collection_documents($enrollmentCollection, [], ['sort' => ['criado_em' => -1], 'limit' => 100]);
require dirname(__DIR__) . '/app/views/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><strong>Confira:</strong><ul class="mb-0 mt-1"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-3 align-items-start">
    <div class="col-xl-4">
        <section class="panel">
            <h2 class="panel-title">Nova matricula</h2>
            <?php if (!$students || !$courses): ?>
                <div class="alert alert-warning small mb-3">Para matricular, cadastre primeiro pelo menos um estudante ativo e um curso ativo.</div>
                <div class="d-grid gap-2"><a class="btn btn-outline-primary" href="estudantes.php">Cadastrar estudante</a><a class="btn btn-outline-primary" href="cursos.php">Cadastrar curso</a></div>
            <?php else: ?>
                <form method="post" action="matriculas.php">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save">
                    <div class="mb-3"><label class="form-label" for="estudante_id">Estudante *</label><select class="form-select" id="estudante_id" name="estudante_id" required><option value="">Selecione um estudante</option><?php foreach ($students as $student): ?><option value="<?= e($student['_id']) ?>"><?= e($student['nome']) ?> · RA <?= e($student['ra']) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label class="form-label" for="curso_id">Curso *</label><select class="form-select" id="curso_id" name="curso_id" required><option value="">Selecione um curso</option><?php foreach ($courses as $course): ?><option value="<?= e($course['_id']) ?>"><?= e($course['nome']) ?> (<?= e($course['codigo']) ?>)</option><?php endforeach; ?></select></div>
                    <div class="row g-2">
                        <div class="col-6 mb-3"><label class="form-label" for="semestre">Semestre *</label><select class="form-select" id="semestre" name="semestre" required><option value="1">1º semestre</option><option value="2">2º semestre</option></select></div>
                        <div class="col-6 mb-3"><label class="form-label" for="ano">Ano *</label><input class="form-control" type="number" min="2000" max="2100" id="ano" name="ano" value="<?= date('Y') ?>" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label" for="data_matricula">Data da matricula *</label><input class="form-control" type="date" id="data_matricula" name="data_matricula" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="Ativa">Ativa</option><option value="Trancada">Trancada</option><option value="Concluida">Concluida</option><option value="Cancelada">Cancelada</option></select></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-link-45deg me-1"></i>Registrar matricula</button>
                </form>
            <?php endif; ?>
        </section>
        <div class="alert alert-info small mt-3 mb-0"><strong>Como funciona?</strong><br>Cada matricula guarda dois identificadores do tipo <code>ObjectId</code>: <code>estudante_id</code> e <code>curso_id</code>. Assim, um estudante pode se matricular em varios cursos e cada curso pode ter varios estudantes.</div>
    </div>
    <div class="col-xl-8">
        <section class="panel">
            <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="panel-title mb-0">Matriculas registradas</h2><span class="badge text-bg-light"><?= count($enrollments) ?> registro(s) exibido(s)</span></div>
            <?php if (!$enrollments): ?><div class="empty-state"><i class="bi bi-card-checklist"></i>Nenhuma matricula registrada ainda.</div>
            <?php else: ?><div class="table-responsive"><table class="table"><thead><tr><th>Estudante</th><th>Curso</th><th>Periodo</th><th>Data</th><th>Status</th><th class="text-end">Acao</th></tr></thead><tbody>
                <?php foreach ($enrollments as $enrollment): ?>
                    <?php
                    $student = $studentCollection->findOne(['_id' => $enrollment['estudante_id']]);
                    $course = $courseCollection->findOne(['_id' => $enrollment['curso_id']]);
                    ?>
                    <tr><td><div class="fw-semibold"><?= e($student['nome'] ?? 'Estudante nao encontrado') ?></div><div class="muted-hint"><?= e($student['ra'] ?? '') ?></div></td><td><div class="fw-semibold"><?= e($course['nome'] ?? 'Curso nao encontrado') ?></div><div class="muted-hint"><?= e($course['codigo'] ?? '') ?></div></td><td><?= e($enrollment['semestre']) ?>º / <?= e($enrollment['ano']) ?></td><td><?= e(format_date($enrollment['data_matricula'] ?? '')) ?></td><td><?= status_badge((string) ($enrollment['status'] ?? 'Ativa')) ?></td><td><div class="action-group"><form method="post" onsubmit="return confirm('Excluir esta matricula?');"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($enrollment['_id']) ?>"><button class="action-btn" type="submit" title="Excluir matricula"><i class="bi bi-trash"></i></button></form></div></td></tr>
                <?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </section>
    </div>
</div>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
