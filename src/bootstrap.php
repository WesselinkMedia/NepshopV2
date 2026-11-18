<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/api.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function request_json(): array
{
    $data = json_decode(file_get_contents('php://input'), true);

    return is_array($data) ? $data : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_valid(): bool
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    $expected = $_SESSION['csrf_token'] ?? '';

    return is_string($sent)
        && is_string($expected)
        && $expected !== ''
        && hash_equals($expected, $sent);
}

function require_csrf(): void
{
    if (!csrf_valid()) {
        json_response([
            'succes' => false,
            'error' => 'De sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.',
        ], 403);
    }
}

function is_logged_in(): bool
{
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

function is_admin(): bool
{
    return is_logged_in() && !empty($_SESSION['is_admin']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        json_response(['succes' => false, 'error' => 'Je bent niet ingelogd.'], 401);
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        json_response(['succes' => false, 'error' => 'Je hebt geen toegang tot deze actie.'], 403);
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response(['succes' => false, 'error' => 'Method not allowed'], 405);
    }
}

function money_to_cents(mixed $amount): ?int
{
    if (is_string($amount)) {
        $amount = trim($amount);
        if (!is_numeric($amount)) {
            return null;
        }
        $amount = (float) $amount;
    }

    if (is_int($amount)) {
        $amount = (float) $amount;
    }

    if (!is_float($amount) || !is_finite($amount) || $amount < 0) {
        return null;
    }

    return (int) round($amount * 100);
}

function cents_to_money(int $cents): string
{
    return number_format($cents / 100, 2, '.', '');
}

function shipping_cents(int $productCents): int
{
    return $productCents < 5000 ? 499 : 0;
}
