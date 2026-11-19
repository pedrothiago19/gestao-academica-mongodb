<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$pageTitle = 'Cursos';
$activePage = 'cursos';
$db = mongo_db();
$courseCollection = $db->cursos;
$enrollmentCollection = $db->matriculas;
$errors = [];
$courseForm = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');
    try {
        if ($action === 'delete') {
            $id = object_id(post_string('id'));
            $courseCollection->deleteOne(['_id' => $id]);
            $enrollmentCollection->deleteMany(['curso_id' => $id]);
            set_flash('success', 'Curso e suas matriculas foram excluidos.');
            redirect('cursos.php');
        }

        if ($action === 'save') {
            $courseForm = $_POST;
            $id = post_string('id') !== '' ? object_id(post_string('id')) : null;
            $nome = post_string('nome');
            $codigo = strtoupper(post_string('codigo'));
            $descricao = post_string('descricao');
            $cargaHoraria = filter_var(post_string('carga_horaria'), FILTER_VALIDATE_INT);
            $modalidade = post_string('modalidade');
            $nivel = post_string('nivel');
            $periodo = post_string('periodo');
            $status = post_string('status') ?: 'Ativo';

            if ($nome === '') $errors[] = 'Informe o nome do curso.';
            if ($codigo === '') $errors[] = 'Informe o codigo do curso.';
            if ($descricao === '') $errors[] = 'Informe uma descricao para o curso.';
            if ($cargaHoraria === false || $cargaHoraria < 1) $errors[] = 'A carga horaria deve ser um numero maior que zero.';
            if (!in_array($modalidade, ['Presencial', 'EAD', 'Hibrido'], true)) $errors[] = 'Selecione uma modalidade valida.';
            if (!in_array($nivel, ['Tecnico', 'Graduacao', 'Pos-graduacao', 'Livre'], true)) $errors[] = 'Selecione um nivel valido.';
            if ($periodo === '') $errors[] = 'Informe o periodo ou turno do curso.';

            if (!$errors) {
                $codeFilter = ['codigo' => $codigo];
                if ($id instanceof ObjectId) $codeFilter['_id'] = ['$ne' => $id];
                if ($courseCollection->findOne($codeFilter)) $errors[] = 'Ja existe um curso cadastrado com esse codigo.';
            }

            if (!$errors) {
                $now = new UTCDateTime();
                $data = [
                    'nome' => $nome,
                    'codigo' => $codigo,
                    'descricao' => $descricao,
                    'carga_horaria' => $cargaHoraria,
                    'modalidade' => $modalidade,
                    'nivel' => $nivel,
                    'periodo' => $periodo,
                    'status' => $status,
                    'atualizado_em' => $now,
                ];
                if ($id instanceof ObjectId) {
                    $courseCollection->updateOne(['_id' => $id], ['$set' => $data]);
                    set_flash('success', 'Curso atualizado com sucesso.');
                } else {
                    $data['criado_em'] = $now;
                    $courseCollection->insertOne($data);
                    set_flash('success', 'Curso cadastrado com sucesso.');
                }
                redirect('cursos.php');
            }
        }
    } catch (Throwable $exception) {
        $errors[] = page_error($exception);
    }
}

if (isset($_GET['editar']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $editingId = object_id((string) $_GET['editar']);
        $courseForm = $courseCollection->findOne(['_id' => $editingId]) ?? [];
        if (!$courseForm) {
            set_flash('warning', 'Curso nao encontrado.');
            redirect('cursos.php');
        }
    } catch (Throwable $exception) {
        $errors[] = 'Nao foi possivel carregar o curso selecionado.';
    }
}

$courses = collection_documents($courseCollection, [], ['sort' => ['nome' => 1]]);
require dirname(__DIR__) . '/app/views/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><strong>Confira:</strong><ul class="mb-0 mt-1"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-3 align-items-start">
    <div class="col-xl-5">
        <section class="panel">
            <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="panel-title mb-0"><?= !empty($courseForm['_id']) || !empty($courseForm['id']) ? 'Editar curso' : 'Novo curso' ?></h2><?php if (!empty($courseForm['_id']) || !empty($courseForm['id'])): ?><a href="cursos.php" class="small text-decoration-none">Cancelar edicao</a><?php endif; ?></div>
            <form method="post" action="cursos.php">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($courseForm['_id'] ?? $courseForm['id'] ?? '') ?>">
                <div class="mb-3"><label class="form-label" for="nome">Nome do curso *</label><input class="form-control" id="nome" name="nome" value="<?= e($courseForm['nome'] ?? '') ?>" required></div>
                <div class="row g-2">
                    <div class="col-md-6 mb-3"><label class="form-label" for="codigo">Codigo *</label><input class="form-control" id="codigo" name="codigo" placeholder="ADS-001" value="<?= e($courseForm['codigo'] ?? '') ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="carga_horaria">Carga horaria (horas) *</label><input type="number" min="1" class="form-control" id="carga_horaria" name="carga_horaria" value="<?= e($courseForm['carga_horaria'] ?? '') ?>" required></div>
                </div>
                <div class="mb-3"><label class="form-label" for="descricao">Descricao *</label><textarea class="form-control" id="descricao" name="descricao" rows="3" required><?= e($courseForm['descricao'] ?? '') ?></textarea></div>
                <div class="row g-2">
                    <div class="col-md-6 mb-3"><label class="form-label" for="modalidade">Modalidade *</label><select class="form-select" id="modalidade" name="modalidade" required><option value="">Selecione</option><?php foreach (['Presencial','EAD','Hibrido'] as $option): ?><option value="<?= e($option) ?>"<?= selected($courseForm['modalidade'] ?? '', $option) ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="nivel">Nivel *</label><select class="form-select" id="nivel" name="nivel" required><option value="">Selecione</option><?php foreach (['Tecnico','Graduacao','Pos-graduacao','Livre'] as $option): ?><option value="<?= e($option) ?>"<?= selected($courseForm['nivel'] ?? '', $option) ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="row g-2">
                    <div class="col-md-7 mb-3"><label class="form-label" for="periodo">Periodo / turno *</label><input class="form-control" id="periodo" name="periodo" placeholder="Ex.: Noturno" value="<?= e($courseForm['periodo'] ?? '') ?>" required></div>
                    <div class="col-md-5 mb-3"><label class="form-label" for="status">Situacao</label><select class="form-select" id="status" name="status"><option value="Ativo"<?= selected($courseForm['status'] ?? 'Ativo', 'Ativo') ?>>Ativo</option><option value="Inativo"<?= selected($courseForm['status'] ?? '', 'Inativo') ?>>Inativo</option></select></div>
                </div>
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Salvar curso</button>
                <p class="muted-hint mt-2 mb-0">O curso e armazenado separadamente na colecao <code>cursos</code>.</p>
            </form>
        </section>
    </div>
    <div class="col-xl-7">
        <section class="panel">
            <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="panel-title mb-0">Cursos cadastrados</h2><span class="badge text-bg-light"><?= count($courses) ?> registro(s)</span></div>
            <?php if (!$courses): ?><div class="empty-state"><i class="bi bi-journal-plus"></i>Nenhum curso cadastrado. Adicione o primeiro pelo formulario.</div>
            <?php else: ?><div class="table-responsive"><table class="table"><thead><tr><th>Curso</th><th>Carga horaria</th><th>Modalidade</th><th>Estudantes</th><th>Status</th><th class="text-end">Acoes</th></tr></thead><tbody>
                <?php foreach ($courses as $course): ?>
                    <?php $studentCount = $enrollmentCollection->countDocuments(['curso_id' => $course['_id']]); ?>
                    <tr><td><div class="fw-semibold"><?= e($course['nome']) ?></div><div class="muted-hint"><?= e($course['codigo']) ?> · <?= e($course['nivel']) ?> · <?= e($course['periodo']) ?></div></td><td><?= e($course['carga_horaria']) ?>h</td><td><?= e($course['modalidade']) ?></td><td><span class="badge text-bg-light"><?= $studentCount ?></span></td><td><?= status_badge((string) ($course['status'] ?? 'Ativo')) ?></td><td><div class="action-group"><a class="action-btn" href="cursos.php?editar=<?= e($course['_id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a><form method="post" onsubmit="return confirm('Excluir este curso e suas matriculas?');"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($course['_id']) ?>"><button class="action-btn" type="submit" title="Excluir"><i class="bi bi-trash"></i></button></form></div></td></tr>
                <?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </section>
        <div class="alert alert-info mt-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>Um curso pode ter varios estudantes. Essa associacao e gravada na colecao <code>matriculas</code>, nao dentro do documento do curso.</div>
    </div>
</div>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
