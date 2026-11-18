<?php

function redirect_to(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function render(string $view, array $data = []): void
{
    $page = $view;
    extract($data, EXTR_SKIP);
    $title = $title ?? 'Nepshop';
    require __DIR__ . '/views/layouts/header.php';
    require __DIR__ . '/views/' . $view . '.php';
    require __DIR__ . '/views/layouts/footer.php';
}

function logout(): void
{
    if (!csrf_valid()) {
        redirect_to('/');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirect_to('/');
}

function show_product(int $productId): void
{
    $product = null;
    $pageError = null;
    $sizes = [];

    try {
        $product = find_product(db(), $productId);
    } catch (Throwable $exception) {
        error_log('Product page failed');
        http_response_code(500);
        $pageError = 'Het product kon niet worden geladen.';
    }

    $notFound = false;
    if ($product === null && $pageError === null) {
        http_response_code(404);
        $notFound = true;
        $pageError = 'Dit product bestaat niet.';
    }

    if ($product !== null) {
        $sizes = decode_stock($product['size_amount']) ?? [];
        if ($sizes === []) {
            http_response_code(500);
            $pageError = 'De voorraad van dit product is ongeldig.';
            $product = null;
        }
    }

    if ($product !== null) {
        $title = (string) $product['product_name'];
    } elseif ($notFound) {
        $title = 'Product niet gevonden';
    } else {
        $title = 'Product niet beschikbaar';
    }
    $imgUrl = $product !== null && valid_http_url((string) $product['img_url']) ? (string) $product['img_url'] : '';
    $priceCents = $product === null ? null : money_to_cents((string) $product['product_price']);
    $inStock = false;
    foreach ($sizes as $amount) {
        if ((int) $amount > 0) {
            $inStock = true;
            break;
        }
    }

    render('product', [
        'title' => $title,
        'description' => $product === null ? (string) $pageError : (string) $product['product_description'],
        'ogType' => $product === null ? 'website' : 'product',
        'ogImage' => $imgUrl !== '' ? $imgUrl : null,
        'ogImageAlt' => $product === null ? null : (string) $product['product_name'],
        'ogPrice' => $priceCents === null ? null : cents_to_money($priceCents),
        'ogAvailability' => $product === null ? null : ($inStock ? 'instock' : 'oos'),
        'product' => $product,
        'pageError' => $pageError,
        'notFound' => $notFound,
        'sizes' => $sizes,
        'imgUrl' => $imgUrl,
    ]);
}

function show_add_product(): void
{
    if (!is_admin()) {
        redirect_to('/');
    }

    $error = null;
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $error = submitted_product_error();
        if ($error === null) {
            redirect_to('/producten/toevoegen?added=1');
        }
    }

    render('add_product', [
        'title' => 'Product toevoegen',
        'error' => $error,
        'added' => isset($_GET['added']),
    ]);
}

function submitted_product_error(): ?string
{
    if (!csrf_valid()) {
        return 'De sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.';
    }

    $product = validated_product_input($_POST);
    if (!$product['ok']) {
        return $product['error'];
    }

    try {
        insert_product(db(), $product);
    } catch (Throwable) {
        error_log('Add product failed');
        return 'Het product kon niet worden opgeslagen.';
    }

    return null;
}

function show_products(): void
{
    $filters = catalog_request();
    $products = [];
    $catalogError = $filters['error'];

    try {
        $products = catalog_products(db(), $filters['min_cents'], $filters['max_cents'], $filters['sort']);
    } catch (Throwable $exception) {
        error_log('Catalog failed');
        $catalogError = 'Producten zijn niet beschikbaar.';
    }

    render('products', [
        'title' => 'Producten',
        'products' => $products,
        'sort' => $filters['sort'],
        'sorts' => catalog_sorts(),
        'minPrice' => $filters['min'],
        'maxPrice' => $filters['max'],
        'catalogError' => $catalogError,
    ]);
}

function show_checkout(): void
{
    if (!is_logged_in()) {
        redirect_to('/inloggen');
    }

    render('checkout', ['title' => 'Afrekenen']);
}

function dispatch(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . trim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/') ?: '/';
    }

    if ($path === '/product.php') {
        $legacyId = filter_input(INPUT_GET, 'product', FILTER_VALIDATE_INT);
        redirect_to($legacyId ? '/product/' . $legacyId : '/producten');
    }

    $redirects = [
        '/products.php' => '/producten',
        '/cart.php' => '/winkelwagen',
        '/checkout.php' => '/afrekenen',
        '/log-in.php' => '/inloggen',
        '/register.php' => '/registreren',
        '/FAQs.php' => '/faqs',
        '/faqs.php' => '/faqs',
        '/about.php' => '/over-ons',
        '/add-product.php' => '/producten/toevoegen',
    ];
    if ($method === 'GET' && isset($redirects[$path])) {
        redirect_to($redirects[$path]);
    }

    if ($method === 'POST' && $path === '/uitloggen') {
        logout();
    }

    if ($method === 'GET' && preg_match('#^/product/([1-9][0-9]*)$#', $path, $matches)) {
        show_product((int) $matches[1]);
        return;
    }

    if ($method === 'GET' && preg_match('#^/api/products/([1-9][0-9]*)$#', $path, $matches)) {
        api_product((int) $matches[1]);
        return;
    }

    $routes = [
        'GET /' => static fn () => render('home', ['title' => 'Nepshop']),
        'GET /producten' => 'show_products',
        'GET /winkelwagen' => static fn () => render('cart', ['title' => 'Winkelwagen']),
        'GET /afrekenen' => 'show_checkout',
        'GET /inloggen' => static fn () => render('login', ['title' => 'Inloggen']),
        'GET /registreren' => static fn () => render('register', ['title' => 'Registreren']),
        'GET /faqs' => static fn () => render('faqs', ['title' => 'Veelgestelde vragen']),
        'GET /over-ons' => static fn () => render('about', ['title' => 'Over ons']),
        'GET /producten/toevoegen' => 'show_add_product',
        'POST /producten/toevoegen' => 'show_add_product',
        'POST /api/login' => 'api_log_in',
        'POST /api/users' => 'api_create_user',
        'POST /api/account' => 'api_account',
        'GET /api/products' => 'api_products',
        'POST /api/products' => 'api_add_product',
        'GET /api/cart' => 'api_cart_show',
        'POST /api/cart' => static fn () => api_cart_change('add'),
        'POST /api/cart/remove' => static fn () => api_cart_change('remove'),
        'POST /api/checkout' => 'api_checkout',
        'GET /api/address' => 'api_address',
    ];

    $route = $routes[$method . ' ' . $path] ?? null;
    if ($route === null) {
        http_response_code(404);
        render('not_found', ['title' => 'Niet gevonden']);
        return;
    }

    $route();
}
