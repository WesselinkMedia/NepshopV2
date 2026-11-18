<?php

function api_error(string $log, string $message, int $status = 500, string $field = 'error'): never
{
    error_log($log);
    json_response(['succes' => false, $field => $message], $status);
}

function api_log_in(): void
{
    require_post();
    require_csrf();

    $input = request_json();
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = $input['password'] ?? '';
    $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    if (!is_string($password) || $password === '' || strlen($password) > 72) {
        password_verify('invalid-password', $dummyHash);
        json_response(['succes' => false, 'error' => 'Ongeldige inloggegevens.'], 401);
    }

    try {
        $connection = db();
    } catch (Throwable) {
        api_error('Login failed: database unavailable', 'Inloggen is tijdelijk niet mogelijk.');
    }

    $user = null;
    if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
        $user = find_user_by_email($connection, $email);
    }

    $valid = password_verify($password, $user['password_'] ?? $dummyHash);

    if ($user === null || !$valid) {
        json_response(['succes' => false, 'error' => 'Ongeldige inloggegevens.'], 401);
    }

    $cart = $_SESSION['cart'] ?? [];
    session_regenerate_id(true);
    $_SESSION = [
        'logged_in' => true,
        'user_id' => (int) $user['user_id_'],
        'is_admin' => (int) $user['is_admin'] === 1,
        'csrf_token' => bin2hex(random_bytes(32)),
        'cart' => is_array($cart) ? $cart : [],
    ];

    json_response(['succes' => true]);
}

function api_create_user(): void
{
    require_post();
    require_csrf();

    try {
        $error = create_user(db(), request_json());
    } catch (Throwable) {
        api_error('Create user failed', 'Het account kon niet worden aangemaakt.');
    }

    if ($error !== null) {
        $status = $error === 'Dit e-mailadres is al geregistreerd.' ? 409 : 400;
        json_response(['succes' => false, 'error' => $error], $status);
    }

    json_response(['succes' => true], 201);
}

function api_account(): void
{
    require_post();
    require_login();

    try {
        $user = find_user_profile(db(), (int) $_SESSION['user_id']);
    } catch (Throwable) {
        api_error('Get user failed', 'Gebruikersgegevens zijn niet beschikbaar.');
    }

    if ($user === null) {
        json_response(['succes' => false, 'error' => 'Gebruiker niet gevonden.'], 404);
    }

    json_response([
        'user_id' => (int) $user['user_id_'],
        'email_address' => $user['email_address'],
        'postcode' => $user['postcode'],
        'house_number' => $user['house_number'],
        'street_name' => $user['street_name'],
        'city' => $user['city'],
    ]);
}

function api_products(): void
{
    $filters = catalog_request();
    if ($filters['error'] !== null) {
        json_response(['succes' => false, 'error' => $filters['error']], 400);
    }

    try {
        json_response(catalog_products(
            db(),
            $filters['min_cents'],
            $filters['max_cents'],
            $filters['sort']
        ));
    } catch (Throwable) {
        api_error('Get products failed', 'Producten zijn niet beschikbaar.');
    }
}

function api_product(int $productId): void
{
    try {
        $product = find_product(db(), $productId);
    } catch (Throwable) {
        api_error('Get product info failed', 'Product is niet beschikbaar.');
    }

    if ($product === null) {
        json_response(['succes' => false, 'error' => 'Product niet gevonden.'], 404);
    }

    json_response([
        'productId' => (int) $product['id'],
        'productName' => $product['product_name'],
        'description' => $product['product_description'],
        'imgUrl' => $product['img_url'],
        'sizeAmount' => $product['size_amount'],
        'price' => $product['product_price'],
    ]);
}

function api_add_product(): void
{
    require_post();
    require_csrf();
    require_admin();

    $product = validated_product_input($_POST);
    if (!$product['ok']) {
        json_response(['succes' => false, 'error' => $product['error']], 400);
    }

    try {
        insert_product(db(), $product);
    } catch (Throwable) {
        api_error('Add product failed', 'Het product kon niet worden opgeslagen.');
    }

    json_response(['succes' => true], 201);
}

function api_cart_show(): void
{
    if (cart_lines() === []) {
        json_response([
            'succes' => true,
            'cart' => [],
            'total_cents' => 0,
            'shipping_cents' => 0,
        ]);
    }

    try {
        json_response(present_cart(db()));
    } catch (Throwable) {
        api_error('Cart failed', 'De winkelwagen kon niet worden geladen.');
    }
}

function api_cart_change(string $action): void
{
    require_post();
    require_csrf();

    $input = request_json();
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    $amount = filter_var($input['amount'] ?? null, FILTER_VALIDATE_INT);
    $size = $input['size'] ?? '';

    if ($id === false || $id < 1 || $amount === false || !is_string($size)) {
        json_response(['succes' => false, 'error' => 'De winkelwagen is ongeldig.'], 400);
    }

    try {
        $result = $action === 'add'
            ? add_cart_line(db(), $id, $size, $amount)
            : remove_cart_line(db(), $id, $size, $amount);
    } catch (Throwable) {
        api_error(
            $action === 'add' ? 'Add to cart failed' : 'Remove from cart failed',
            $action === 'add'
                ? 'Het product kon niet worden toegevoegd.'
                : 'Het product kon niet worden verwijderd.'
        );
    }

    if (!$result['ok']) {
        json_response(['succes' => false, 'error' => $result['error']], $result['status']);
    }

    json_response($result['presented']);
}

function api_checkout(): void
{
    require_post();
    require_csrf();
    require_login();

    try {
        $connection = db();
    } catch (Throwable) {
        api_error('Checkout failed: database unavailable', 'De bestelling kon niet worden opgeslagen.', 500, 'reason');
    }

    $connection->begin_transaction();

    try {
        $result = place_order($connection, (int) $_SESSION['user_id'], request_json());
        if (!$result['ok']) {
            $connection->rollback();
            json_response(['succes' => false, 'reason' => $result['reason']], $result['status']);
        }

        $connection->commit();
        clear_cart();
    } catch (Throwable) {
        $connection->rollback();
        api_error('Checkout failed', 'De bestelling kon niet worden opgeslagen.', 500, 'reason');
    }

    json_response(['succes' => true]);
}

function api_address(): void
{
    $postcode = strtoupper(str_replace(' ', '', (string) ($_GET['postcode'] ?? '')));
    $houseNumber = trim((string) ($_GET['house_number'] ?? ($_GET['houseNumber'] ?? '')));
    $address = lookup_address($postcode, $houseNumber);

    json_response([
        'street' => $address['street'] ?? false,
        'city' => $address['city'] ?? false,
    ]);
}
