<?php

require_once __DIR__ . '/products.php';

function unwrap_cart_item(mixed $item): ?array
{
    if (is_string($item)) {
        $decoded = json_decode($item, true);

        return is_array($decoded) ? $decoded : null;
    }

    return is_array($item) ? $item : null;
}

function cart_int(mixed $value, int $max): ?int
{
    if (is_int($value)) {
        $int = $value;
    } elseif (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value)) {
        $int = (int) $value;
    } else {
        return null;
    }

    if ($int < 1 || $int > $max) {
        return null;
    }

    return $int;
}

function parse_cart(array $cart): array
{
    if (!array_is_list($cart)) {
        return ['ok' => false, 'reason' => 'De winkelwagen is ongeldig.'];
    }
    if (count($cart) > 50) {
        return ['ok' => false, 'reason' => 'De winkelwagen bevat te veel regels.'];
    }

    $lines = [];
    foreach ($cart as $rawItem) {
        $item = unwrap_cart_item($rawItem);
        if ($item === null) {
            return ['ok' => false, 'reason' => 'De winkelwagen is ongeldig.'];
        }

        $id = cart_int($item['id'] ?? null, 1000000000);
        $amount = cart_int($item['amount'] ?? null, 100);
        $size = $item['size'] ?? null;

        if ($id === null) {
            return ['ok' => false, 'reason' => 'Een product in de winkelwagen is ongeldig.'];
        }
        if ($amount === null) {
            return ['ok' => false, 'reason' => 'Een aantal in de winkelwagen is ongeldig.'];
        }
        if (!is_string($size) || !valid_size($size)) {
            return ['ok' => false, 'reason' => 'Een maat in de winkelwagen is ongeldig.'];
        }

        $key = $id . '|' . $size;
        if (!isset($lines[$key])) {
            $lines[$key] = ['id' => $id, 'size' => $size, 'amount' => 0];
        }
        $lines[$key]['amount'] += $amount;
        if ($lines[$key]['amount'] > 100) {
            return ['ok' => false, 'reason' => 'Een aantal in de winkelwagen is ongeldig.'];
        }
    }

    if ($lines === []) {
        return ['ok' => false, 'reason' => 'Je winkelwagen is leeg.'];
    }

    return ['ok' => true, 'lines' => array_values($lines)];
}

function cart_lines(): array
{
    $stored = $_SESSION['cart'] ?? [];
    if (!is_array($stored) || $stored === []) {
        return [];
    }

    $parsed = parse_cart($stored);
    if (!$parsed['ok']) {
        save_cart_lines([]);

        return [];
    }

    return $parsed['lines'];
}

function save_cart_lines(array $lines): void
{
    $_SESSION['cart'] = array_values($lines);
}

function clear_cart(): void
{
    save_cart_lines([]);
}

function present_cart(mysqli $db): array
{
    $changed = false;
    $display = [];
    $kept = [];
    $productCents = 0;

    foreach (cart_lines() as $line) {
        $product = find_product($db, $line['id']);
        if ($product === null) {
            $changed = true;
            continue;
        }

        $stock = decode_stock($product['size_amount']);
        if ($stock === null || !array_key_exists($line['size'], $stock) || $stock[$line['size']] < 1) {
            $changed = true;
            continue;
        }

        $amount = $line['amount'];
        if ($amount > $stock[$line['size']]) {
            $amount = $stock[$line['size']];
            $changed = true;
        }

        $unitCents = money_to_cents((string) $product['product_price']);
        if ($unitCents === null) {
            $changed = true;
            continue;
        }

        $kept[] = [
            'id' => $line['id'],
            'size' => $line['size'],
            'amount' => $amount,
        ];
        $productCents += $unitCents * $amount;
        $display[] = [
            'id' => $line['id'],
            'productName' => $product['product_name'],
            'description' => $product['product_description'],
            'size' => $line['size'],
            'amount' => $amount,
            'price' => $product['product_price'],
        ];
    }

    if ($changed) {
        save_cart_lines($kept);
    }

    return [
        'succes' => !$changed,
        'cart' => $display,
        'total_cents' => $productCents,
        'shipping_cents' => $kept === [] ? 0 : shipping_cents($productCents),
    ];
}

function add_cart_line(mysqli $db, int $id, string $size, int $amount): array
{
    if (!valid_size($size)) {
        return ['ok' => false, 'status' => 400, 'error' => 'Een maat in de winkelwagen is ongeldig.'];
    }
    if ($amount < 1 || $amount > 100) {
        return ['ok' => false, 'status' => 400, 'error' => 'Een aantal in de winkelwagen is ongeldig.'];
    }

    $product = find_product($db, $id);
    if ($product === null) {
        return ['ok' => false, 'status' => 400, 'error' => 'Dit product bestaat niet.'];
    }

    $stock = decode_stock($product['size_amount']);
    if ($stock === null || !array_key_exists($size, $stock)) {
        return ['ok' => false, 'status' => 400, 'error' => 'Deze maat bestaat niet.'];
    }

    $lines = cart_lines();
    $index = null;
    $current = 0;
    foreach ($lines as $i => $line) {
        if ($line['id'] === $id && $line['size'] === $size) {
            $index = $i;
            $current = $line['amount'];
        }
    }

    $next = $current + $amount;
    if ($next > 100 || $next > $stock[$size]) {
        return ['ok' => false, 'status' => 409, 'error' => 'Er is niet genoeg voorraad voor deze maat.'];
    }

    if ($index === null) {
        $lines[] = ['id' => $id, 'size' => $size, 'amount' => $next];
    } else {
        $lines[$index]['amount'] = $next;
    }
    save_cart_lines($lines);

    return ['ok' => true, 'status' => 200, 'presented' => present_cart($db)];
}

function remove_cart_line(mysqli $db, int $id, string $size, int $amount): array
{
    if (!valid_size($size) || $amount < 1 || $amount > 100) {
        return ['ok' => false, 'status' => 400, 'error' => 'De winkelwagen is ongeldig.'];
    }

    $lines = [];
    foreach (cart_lines() as $line) {
        if ($line['id'] === $id && $line['size'] === $size) {
            $left = $line['amount'] - $amount;
            if ($left > 0) {
                $line['amount'] = $left;
                $lines[] = $line;
            }
            continue;
        }
        $lines[] = $line;
    }

    save_cart_lines($lines);

    return ['ok' => true, 'status' => 200, 'presented' => present_cart($db)];
}
