<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $db = mongo_db();

    // Estudantes: impede CPF e RA duplicados.
    $db->estudantes->createIndex(['cpf' => 1], ['unique' => true, 'name' => 'uk_estudantes_cpf']);
    $db->estudantes->createIndex(['ra' => 1], ['unique' => true, 'name' => 'uk_estudantes_ra']);

    // Endereco: um documento de endereco por estudante.
    $db->enderecos->createIndex(['estudante_id' => 1], ['unique' => true, 'name' => 'uk_enderecos_estudante']);

    // Cursos: codigo unico.
    $db->cursos->createIndex(['codigo' => 1], ['unique' => true, 'name' => 'uk_cursos_codigo']);

    // Matriculas: evita repetir a mesma matricula no mesmo periodo letivo.
    $db->matriculas->createIndex(
        ['estudante_id' => 1, 'curso_id' => 1, 'ano' => 1, 'semestre' => 1],
        ['unique' => true, 'name' => 'uk_matricula_periodo']
    );
    $db->matriculas->createIndex(['estudante_id' => 1], ['name' => 'idx_matriculas_estudante']);
    $db->matriculas->createIndex(['curso_id' => 1], ['name' => 'idx_matriculas_curso']);

    echo "Banco conectado e indices criados com sucesso.\n";
    echo "Banco: " . (getenv('MONGODB_DATABASE') ?: 'gestao_academica') . "\n";
    echo "Colecoes usadas: estudantes, enderecos, cursos e matriculas.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Erro ao preparar o banco: " . $exception->getMessage() . "\n");
    exit(1);
}
