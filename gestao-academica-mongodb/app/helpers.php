<?php
declare(strict_types=1);

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

function e(mixed $value): string
{
    if ($value instanceof UTCDateTime) {
        $value = $value->toDateTime()->format('Y-m-d H:i:s');
    }
    if ($value instanceof ObjectId) {
        $value = (string) $value;
    }
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(): void
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Formulario expirado ou token de seguranca invalido. Volte e tente novamente.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return is_array($flash) ? $flash : null;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function object_id(string $id): ObjectId
{
    if (!ObjectId::isValid($id)) {
        throw new InvalidArgumentException('Identificador invalido.');
    }
    return new ObjectId($id);
}

function digits_only(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function format_cpf(?string $cpf): string
{
    $cpf = digits_only((string) $cpf);
    if (strlen($cpf) !== 11) {
        return (string) $cpf;
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y', $timestamp) : (string) $date;
}

function collection_documents($collection, array $filter = [], array $options = []): array
{
    return iterator_to_array($collection->find($filter, $options), false);
}

function selected(mixed $current, mixed $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

function status_badge(string $status): string
{
    $class = match (strtolower($status)) {
        'ativo', 'ativa', 'matriculado' => 'success',
        'inativo', 'inativa', 'cancelada', 'trancada' => 'secondary',
        'concluida', 'concluído', 'concluido' => 'primary',
        default => 'warning',
    };
    return '<span class="badge text-bg-' . $class . '">' . e(ucfirst($status)) . '</span>';
}

function post_string(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function page_error(Throwable $exception): string
{
    // Mensagem amigavel: detalhes tecnicos ficam nos logs do servidor.
    error_log($exception->__toString());
    return 'Nao foi possivel concluir a operacao. Verifique a conexao com o MongoDB e os dados informados.';
}
