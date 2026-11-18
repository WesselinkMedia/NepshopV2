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

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . trim($path, '/');

    return $path === '/' ? '/' : (rtrim($path, '/') ?: '/');
}

function absolute_url(string $path = '/'): string
{
    if (valid_http_url($path)) {
        return $path;
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!is_string($host) || !preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host)) {
        $host = 'localhost';
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }

    return ($secure ? 'https' : 'http') . '://' . $host . $path;
}

function meta_text(string $value, int $limit = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
    if ($text === '' || mb_strlen($text) <= $limit) {
        return $text;
    }

    $cut = mb_substr($text, 0, $limit - 1);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > (int) ($limit * 0.6)) {
        $cut = mb_substr($cut, 0, $space);
    }

    return rtrim($cut, " \t.,;:-") . '…';
}

function page_description(string $page): string
{
    return match ($page) {
        'products' => 'De collectie van nepshop: hoodies, shirts, broeken en meer.',
        'faqs' => 'Antwoorden op vragen over bestellen, betalen, verzenden en ruilen.',
        'about' => 'nepshop is een kledingwinkel voor iedere stijl, zonder poespas.',
        'not_found' => 'Deze pagina bestaat niet.',
        'product' => 'Dit product is niet beschikbaar.',
        default => 'Kleding voor iedere stijl, zonder poespas.',
    };
}

function open_graph(array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    if ($title === '') {
        $title = 'Nepshop';
    }

    $description = meta_text((string) ($input['description'] ?? ''));
    if ($description === '') {
        $description = page_description((string) ($input['page'] ?? ''));
    }

    $type = ($input['type'] ?? '') === 'product' ? 'product' : 'website';
    $image = (string) ($input['image'] ?? '');
    $defaultImage = !valid_http_url($image);
    if ($defaultImage) {
        $image = absolute_url('/assets/og/share.png');
    }

    $imageAlt = meta_text((string) ($input['image_alt'] ?? ''), 120);
    if ($imageAlt === '') {
        $imageAlt = $defaultImage ? 'nepshop, kleding voor iedere stijl' : $title;
    }

    $price = null;
    $availability = null;
    if ($type === 'product') {
        $rawPrice = (string) ($input['price'] ?? '');
        if (preg_match('/^\d+\.\d{2}$/', $rawPrice)) {
            $price = $rawPrice;
        }
        $availability = ($input['availability'] ?? '') === 'instock' ? 'instock' : 'oos';
    }

    return [
        'title' => $title,
        'description' => $description,
        'type' => $type,
        'url' => absolute_url(current_path()),
        'image' => $image,
        'image_alt' => $imageAlt,
        'image_width' => $defaultImage ? 1200 : null,
        'image_height' => $defaultImage ? 630 : null,
        'image_type' => $defaultImage ? 'image/png' : null,
        'secure_image' => str_starts_with($image, 'https://') ? $image : null,
        'price' => $price,
        'availability' => $availability,
    ];
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
