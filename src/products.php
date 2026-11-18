<?php

const PRODUCT_FIELDS = 'id, product_name, product_description, img_url, size_amount, product_price';

function find_product(mysqli $db, int $id, bool $forUpdate = false): ?array
{
    $sql = 'SELECT ' . PRODUCT_FIELDS . ' FROM products WHERE id = ?';
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    return db_row($db, $sql, 'i', $id);
}

function all_products(mysqli $db): array
{
    $result = $db->query('SELECT ' . PRODUCT_FIELDS . ' FROM products');

    return $result->fetch_all(MYSQLI_ASSOC);
}

function catalog_sorts(): array
{
    return [
        'sortOldNew' => 'Oudste - nieuwste',
        'sortNewOld' => 'Nieuwste - oudste',
        'sortPriceLowHigh' => 'Prijs: laag - hoog',
        'sortPriceHighLow' => 'Prijs: hoog - laag',
        'sortNameAZ' => 'Naam: A - Z',
        'sortNameZA' => 'Naam: Z - A',
    ];
}

function catalog_request(): array
{
    $sorts = catalog_sorts();
    $sort = $_GET['sort'] ?? 'sortNewOld';
    if (!is_string($sort) || !isset($sorts[$sort])) {
        $sort = 'sortNewOld';
    }

    $minInput = isset($_GET['min']) ? trim((string) $_GET['min']) : '';
    $maxInput = isset($_GET['max']) ? trim((string) $_GET['max']) : '';
    $error = null;
    $minCents = null;
    $maxCents = null;

    if ($minInput !== '') {
        $minCents = money_to_cents($minInput);
        if ($minCents === null) {
            $error = 'Vul een geldige minimum- of maximumprijs in.';
        }
    }
    if ($error === null && $maxInput !== '') {
        $maxCents = money_to_cents($maxInput);
        if ($maxCents === null) {
            $error = 'Vul een geldige minimum- of maximumprijs in.';
        }
    }
    if ($error !== null) {
        $minCents = null;
        $maxCents = null;
    } elseif ($minCents !== null && $maxCents !== null && $minCents > $maxCents) {
        [$minCents, $maxCents] = [$maxCents, $minCents];
        [$minInput, $maxInput] = [$maxInput, $minInput];
    }

    return [
        'sort' => $sort,
        'min' => $minInput,
        'max' => $maxInput,
        'min_cents' => $minCents,
        'max_cents' => $maxCents,
        'error' => $error,
    ];
}

function catalog_products(mysqli $db, ?int $minCents, ?int $maxCents, string $sort): array
{
    if (!isset(catalog_sorts()[$sort])) {
        $sort = 'sortNewOld';
    }

    $filtered = [];
    foreach (all_products($db) as $product) {
        $cents = money_to_cents((string) $product['product_price']);
        if ($cents === null) {
            continue;
        }
        if ($minCents !== null && $cents < $minCents) {
            continue;
        }
        if ($maxCents !== null && $cents > $maxCents) {
            continue;
        }
        if (!valid_http_url((string) $product['img_url'])) {
            $product['img_url'] = '';
        }
        $product['price_cents'] = $cents;
        $filtered[] = $product;
    }

    usort($filtered, static function (array $a, array $b) use ($sort): int {
        return match ($sort) {
            'sortOldNew' => ((int) $a['id']) <=> ((int) $b['id']),
            'sortPriceLowHigh' => $a['price_cents'] <=> $b['price_cents'],
            'sortPriceHighLow' => $b['price_cents'] <=> $a['price_cents'],
            'sortNameAZ' => strcasecmp((string) $a['product_name'], (string) $b['product_name']),
            'sortNameZA' => strcasecmp((string) $b['product_name'], (string) $a['product_name']),
            default => ((int) $b['id']) <=> ((int) $a['id']),
        };
    });

    return $filtered;
}

function decode_stock(mixed $json): ?array
{
    if (!is_string($json) || $json === '') {
        return null;
    }

    $stock = json_decode($json, true);
    if (!is_array($stock)) {
        return null;
    }

    $normalized = [];
    foreach ($stock as $size => $amount) {
        if (!is_string($size) || !valid_size($size)) {
            return null;
        }
        $available = filter_var($amount, FILTER_VALIDATE_INT);
        if ($available === false || $available < 0) {
            return null;
        }
        $normalized[$size] = (int) $available;
    }

    return $normalized;
}

function valid_size(string $size): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_-]{1,10}$/', $size);
}

function save_stock(mysqli $db, int $id, array $stock): void
{
    db_exec(
        $db,
        'UPDATE products SET size_amount = ? WHERE id = ?',
        'si',
        json_encode($stock, JSON_THROW_ON_ERROR),
        $id
    );
}

function valid_http_url(string $url): bool
{
    if (strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);

    return $scheme === 'http' || $scheme === 'https';
}

function validated_product_input(array $input): array
{
    $name = trim((string) ($input['productName'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $imgUrl = trim((string) ($input['imgUrl'] ?? ''));
    $stock = decode_stock((string) ($input['sizeAmount'] ?? ''));
    $priceCents = money_to_cents(trim((string) ($input['price'] ?? '')));

    if ($name === '' || strlen($name) > 50) {
        return ['ok' => false, 'error' => 'Vul een productnaam in van maximaal 50 tekens.'];
    }
    if ($description === '' || strlen($description) > 500) {
        return ['ok' => false, 'error' => 'Vul een beschrijving in van maximaal 500 tekens.'];
    }
    if (!valid_http_url($imgUrl)) {
        return ['ok' => false, 'error' => 'Vul een geldige http- of https-afbeeldings-URL in.'];
    }
    if ($stock === null || $stock === []) {
        return ['ok' => false, 'error' => 'Vul geldige maten en voorraad in als JSON.'];
    }
    if ($priceCents === null || $priceCents <= 0) {
        return ['ok' => false, 'error' => 'Vul een geldige prijs in.'];
    }

    return [
        'ok' => true,
        'name' => $name,
        'description' => $description,
        'imgUrl' => $imgUrl,
        'stock' => $stock,
        'price' => cents_to_money($priceCents),
    ];
}

function insert_product(mysqli $db, array $product): void
{
    db_exec(
        $db,
        'INSERT INTO products (product_name, product_description, img_url, size_amount, product_price)
         VALUES (?, ?, ?, ?, ?)',
        'sssss',
        $product['name'],
        $product['description'],
        $product['imgUrl'],
        json_encode($product['stock'], JSON_THROW_ON_ERROR),
        $product['price']
    );
}
