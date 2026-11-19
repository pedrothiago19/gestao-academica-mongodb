<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$pageTitle = 'Estudantes';
$activePage = 'estudantes';
$db = mongo_db();
$studentCollection = $db->estudantes;
$addressCollection = $db->enderecos;
$enrollmentCollection = $db->matriculas;
$errors = [];
$studentForm = [];
$addressForm = [];
$editingId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    try {
        if ($action === 'delete') {
            $id = object_id(post_string('id'));
            $studentCollection->deleteOne(['_id' => $id]);
            $addressCollection->deleteMany(['estudante_id' => $id]);
            $enrollmentCollection->deleteMany(['estudante_id' => $id]);
            set_flash('success', 'Estudante e seus relacionamentos foram excluidos.');
            redirect('estudantes.php');
        }

        if ($action === 'save') {
            $studentForm = $_POST;
            $addressForm = $_POST;
            $id = post_string('id') !== '' ? object_id(post_string('id')) : null;
            $nome = post_string('nome');
            $cpf = digits_only(post_string('cpf'));
            $email = post_string('email');
            $telefone = post_string('telefone');
            $dataNascimento = post_string('data_nascimento');
            $sexo = post_string('sexo');
            $ra = strtoupper(post_string('ra'));
            $status = post_string('status') ?: 'Ativo';

            $cep = digits_only(post_string('cep'));
            $logradouro = post_string('logradouro');
            $numero = post_string('numero');
            $complemento = post_string('complemento');
            $bairro = post_string('bairro');
            $cidade = post_string('cidade');
            $estado = strtoupper(post_string('estado'));
            $pais = post_string('pais') ?: 'Brasil';

            if ($nome === '') $errors[] = 'Informe o nome completo.';
            if (strlen($cpf) !== 11) $errors[] = 'O CPF deve conter 11 numeros.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Informe um e-mail valido.';
            if ($telefone === '') $errors[] = 'Informe o telefone.';
            if ($dataNascimento === '' || strtotime($dataNascimento) === false) $errors[] = 'Informe uma data de nascimento valida.';
            if ($ra === '') $errors[] = 'Informe o registro academico (RA).';
            if ($logradouro === '' || $numero === '' || $bairro === '' || $cidade === '' || $estado === '' || $pais === '') $errors[] = 'Preencha os campos obrigatorios do endereco.';
            if (strlen($cep) !== 8) $errors[] = 'O CEP deve conter 8 numeros.';
            if (strlen($estado) !== 2) $errors[] = 'Informe a UF com 2 letras.';

            if (!$errors) {
                $cpfFilter = ['cpf' => $cpf];
                $raFilter = ['ra' => $ra];
                if ($id instanceof ObjectId) {
                    $cpfFilter['_id'] = ['$ne' => $id];
                    $raFilter['_id'] = ['$ne' => $id];
                }
                if ($studentCollection->findOne($cpfFilter)) $errors[] = 'Ja existe um estudante cadastrado com esse CPF.';
                if ($studentCollection->findOne($raFilter)) $errors[] = 'Ja existe um estudante cadastrado com esse RA.';
            }

            if (!$errors) {
                $now = new UTCDateTime();
                $studentData = [
                    'nome' => $nome,
                    'cpf' => $cpf,
                    'email' => $email,
                    'telefone' => $telefone,
                    'data_nascimento' => $dataNascimento,
                    'sexo' => $sexo,
                    'ra' => $ra,
                    'status' => $status,
                    'atualizado_em' => $now,
                ];
                $addressData = [
                    'cep' => $cep,
                    'logradouro' => $logradouro,
                    'numero' => $numero,
                    'complemento' => $complemento,
                    'bairro' => $bairro,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'pais' => $pais,
                    'atualizado_em' => $now,
                ];

                if ($id instanceof ObjectId) {
                    $studentCollection->updateOne(['_id' => $id], ['$set' => $studentData]);
                    $addressCollection->updateOne(
                        ['estudante_id' => $id],
                        ['$set' => $addressData, '$setOnInsert' => ['estudante_id' => $id, 'criado_em' => $now]],
                        ['upsert' => true]
                    );
                    set_flash('success', 'Cadastro do estudante atualizado com sucesso.');
                } else {
                    $studentData['criado_em'] = $now;
                    $insert = $studentCollection->insertOne($studentData);
                    $studentId = $insert->getInsertedId();
                    try {
                        $addressData['estudante_id'] = $studentId;
                        $addressData['criado_em'] = $now;
                        $addressCollection->insertOne($addressData);
                    } catch (Throwable $addressException) {
                        // Evita deixar estudante sem endereco se a segunda gravacao falhar.
                        $studentCollection->deleteOne(['_id' => $studentId]);
                        throw $addressException;
                    }
                    set_flash('success', 'Estudante e endereco cadastrados com sucesso.');
                }
                redirect('estudantes.php');
            }
        }
    } catch (Throwable $exception) {
        $errors[] = page_error($exception);
    }
}

if (isset($_GET['editar']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $editingId = object_id((string) $_GET['editar']);
        $studentForm = $studentCollection->findOne(['_id' => $editingId]) ?? [];
        if (!$studentForm) {
            set_flash('warning', 'Estudante nao encontrado.');
            redirect('estudantes.php');
        }
        $addressForm = $addressCollection->findOne(['estudante_id' => $editingId]) ?? [];
    } catch (Throwable $exception) {
        $errors[] = 'Nao foi possivel carregar o cadastro selecionado.';
    }
}

$students = collection_documents($studentCollection, [], ['sort' => ['nome' => 1]]);
require dirname(__DIR__) . '/app/views/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><strong>Confira:</strong><ul class="mb-0 mt-1"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-3 align-items-start">
    <div class="col-xl-5">
        <section class="panel">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="panel-title mb-0"><?= !empty($studentForm['_id']) || $editingId || !empty($studentForm['id']) ? 'Editar estudante' : 'Novo estudante' ?></h2>
                <?php if (!empty($studentForm['_id']) || $editingId || !empty($studentForm['id'])): ?><a href="estudantes.php" class="small text-decoration-none">Cancelar edicao</a><?php endif; ?>
            </div>
            <form method="post" action="estudantes.php" novalidate>
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= e($studentForm['_id'] ?? $studentForm['id'] ?? $editingId ?? '') ?>">
                <h3 class="h6 fw-bold mb-3">Dados pessoais</h3>
                <div class="mb-3"><label class="form-label" for="nome">Nome completo *</label><input class="form-control" id="nome" name="nome" value="<?= e($studentForm['nome'] ?? '') ?>" required></div>
                <div class="row g-2">
                    <div class="col-md-6 mb-3"><label class="form-label" for="cpf">CPF *</label><input class="form-control" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" value="<?= e($studentForm['cpf'] ?? '') ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="ra">Registro academico (RA) *</label><input class="form-control" id="ra" name="ra" value="<?= e($studentForm['ra'] ?? '') ?>" required></div>
                </div>
                <div class="mb-3"><label class="form-label" for="email">E-mail *</label><input type="email" class="form-control" id="email" name="email" value="<?= e($studentForm['email'] ?? '') ?>" required></div>
                <div class="row g-2">
                    <div class="col-md-6 mb-3"><label class="form-label" for="telefone">Telefone *</label><input class="form-control" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?= e($studentForm['telefone'] ?? '') ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="data_nascimento">Data de nascimento *</label><input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?= e($studentForm['data_nascimento'] ?? '') ?>" required></div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6 mb-3"><label class="form-label" for="sexo">Sexo</label><select class="form-select" id="sexo" name="sexo"><option value="">Selecione</option><?php foreach (['Feminino','Masculino','Outro','Prefiro nao informar'] as $option): ?><option value="<?= e($option) ?>"<?= selected($studentForm['sexo'] ?? '', $option) ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="status">Situacao</label><select class="form-select" id="status" name="status"><option value="Ativo"<?= selected($studentForm['status'] ?? 'Ativo', 'Ativo') ?>>Ativo</option><option value="Inativo"<?= selected($studentForm['status'] ?? '', 'Inativo') ?>>Inativo</option></select></div>
                </div>
                <hr class="my-3">
                <h3 class="h6 fw-bold mb-3">Endereco (colecao separada)</h3>
                <div class="row g-2">
                    <div class="col-sm-4 mb-3"><label class="form-label" for="cep">CEP *</label><input class="form-control" id="cep" name="cep" maxlength="9" value="<?= e($addressForm['cep'] ?? '') ?>" required></div>
                    <div class="col-sm-8 mb-3"><label class="form-label" for="logradouro">Logradouro *</label><input class="form-control" id="logradouro" name="logradouro" value="<?= e($addressForm['logradouro'] ?? '') ?>" required></div>
                    <div class="col-sm-4 mb-3"><label class="form-label" for="numero">Numero *</label><input class="form-control" id="numero" name="numero" value="<?= e($addressForm['numero'] ?? '') ?>" required></div>
                    <div class="col-sm-8 mb-3"><label class="form-label" for="complemento">Complemento</label><input class="form-control" id="complemento" name="complemento" value="<?= e($addressForm['complemento'] ?? '') ?>"></div>
                    <div class="col-sm-6 mb-3"><label class="form-label" for="bairro">Bairro *</label><input class="form-control" id="bairro" name="bairro" value="<?= e($addressForm['bairro'] ?? '') ?>" required></div>
                    <div class="col-sm-6 mb-3"><label class="form-label" for="cidade">Cidade *</label><input class="form-control" id="cidade" name="cidade" value="<?= e($addressForm['cidade'] ?? '') ?>" required></div>
                    <div class="col-sm-4 mb-3"><label class="form-label" for="estado">Estado (UF) *</label><input class="form-control" id="estado" name="estado" maxlength="2" value="<?= e($addressForm['estado'] ?? '') ?>" required></div>
                    <div class="col-sm-8 mb-3"><label class="form-label" for="pais">Pais *</label><input class="form-control" id="pais" name="pais" value="<?= e($addressForm['pais'] ?? 'Brasil') ?>" required></div>
                </div>
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2-circle me-1"></i>Salvar estudante</button>
                <p class="muted-hint mt-2 mb-0">* Campos obrigatorios. O endereco e salvo na colecao <code>enderecos</code>.</p>
            </form>
        </section>
    </div>
    <div class="col-xl-7">
        <section class="panel">
            <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="panel-title mb-0">Estudantes cadastrados</h2><span class="badge text-bg-light"><?= count($students) ?> registro(s)</span></div>
            <?php if (!$students): ?><div class="empty-state"><i class="bi bi-person-plus"></i>Nenhum estudante cadastrado. Use o formulario ao lado para comecar.</div>
            <?php else: ?>
            <div class="table-responsive"><table class="table"><thead><tr><th>Estudante</th><th>RA / CPF</th><th>Cursos</th><th>Status</th><th class="text-end">Acoes</th></tr></thead><tbody>
            <?php foreach ($students as $student): ?>
                <?php $courseCount = $enrollmentCollection->countDocuments(['estudante_id' => $student['_id']]); ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($student['nome']) ?></div><div class="muted-hint"><?= e($student['email']) ?></div></td>
                    <td><div><?= e($student['ra']) ?></div><div class="muted-hint"><?= e(format_cpf($student['cpf'] ?? '')) ?></div></td>
                    <td><span class="badge text-bg-light"><?= $courseCount ?></span></td>
                    <td><?= status_badge((string) ($student['status'] ?? 'Ativo')) ?></td>
                    <td><div class="action-group"><a class="action-btn" href="estudantes.php?editar=<?= e($student['_id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a><form method="post" onsubmit="return confirm('Excluir este estudante e suas matriculas?');"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($student['_id']) ?>"><button class="action-btn" type="submit" title="Excluir"><i class="bi bi-trash"></i></button></form></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>
        </section>
        <div class="alert alert-info mt-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>Ao excluir um estudante, o sistema tambem remove seu endereco e suas matriculas para evitar referencias sem registro correspondente.</div>
    </div>
</div>
<?php require dirname(__DIR__) . '/app/views/footer.php'; ?>
