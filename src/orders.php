<?php

require_once __DIR__ . '/cart.php';

function request_cents(mixed $value): ?int
{
    if (is_int($value) && $value >= 0) {
        return $value;
    }
    if (is_string($value) && preg_match('/^[0-9]{1,9}$/', $value)) {
        return (int) $value;
    }

    return null;
}

function place_order(mysqli $db, int $userId, array $input): array
{
    $parsed = parse_cart(cart_lines());
    if (!$parsed['ok']) {
        return ['ok' => false, 'status' => 400, 'reason' => $parsed['reason']];
    }

    $paymentMethod = $input['paymentMethod'] ?? '';
    if (!is_string($paymentMethod) || !in_array($paymentMethod, ['Mydeal', 'PalPay', 'Klorno'], true)) {
        return ['ok' => false, 'status' => 400, 'reason' => 'Kies een geldige betaalmethode.'];
    }

    $clientTotal = request_cents($input['totalCents'] ?? null);
    $clientShipping = request_cents($input['shippingCents'] ?? null);
    if ($clientTotal === null || $clientShipping === null) {
        return ['ok' => false, 'status' => 400, 'reason' => 'Het totaalbedrag is ongeldig.'];
    }

    $lines = $parsed['lines'];
    usort($lines, static fn (array $a, array $b): int => $a['id'] <=> $b['id']);

    $locked = [];
    $validated = [];
    $productCents = 0;

    foreach ($lines as $line) {
        if (!isset($locked[$line['id']])) {
            $product = find_product($db, $line['id'], true);
            if ($product === null) {
                return ['ok' => false, 'status' => 400, 'reason' => 'Een product in de winkelwagen bestaat niet.'];
            }

            $stock = decode_stock($product['size_amount']);
            if ($stock === null) {
                return ['ok' => false, 'status' => 500, 'reason' => 'De voorraad van een product is ongeldig.'];
            }

            $unitCents = money_to_cents((string) $product['product_price']);
            if ($unitCents === null || $unitCents < 0) {
                return ['ok' => false, 'status' => 500, 'reason' => 'Een productprijs is ongeldig.'];
            }

            $locked[$line['id']] = [
                'name' => $product['product_name'],
                'stock' => $stock,
                'unit_cents' => $unitCents,
            ];
        }

        $stock = $locked[$line['id']]['stock'];
        if (!array_key_exists($line['size'], $stock)) {
            return ['ok' => false, 'status' => 400, 'reason' => 'Een gekozen maat bestaat niet.'];
        }
        if ($stock[$line['size']] < $line['amount']) {
            return ['ok' => false, 'status' => 409, 'reason' => 'Er is niet genoeg voorraad voor een product in je winkelwagen.'];
        }

        $locked[$line['id']]['stock'][$line['size']] -= $line['amount'];
        $unitCents = $locked[$line['id']]['unit_cents'];
        $productCents += $unitCents * $line['amount'];
        $validated[] = [
            'id' => $line['id'],
            'name' => $locked[$line['id']]['name'],
            'size' => $line['size'],
            'amount' => $line['amount'],
            'unit_price' => cents_to_money($unitCents),
        ];
    }

    $shippingCents = shipping_cents($productCents);
    if ($clientTotal !== $productCents || $clientShipping !== $shippingCents) {
        return [
            'ok' => false,
            'status' => 409,
            'reason' => 'Het totaalbedrag komt niet overeen met de prijzen in de winkel.',
        ];
    }

    foreach ($locked as $productId => $product) {
        save_stock($db, (int) $productId, $product['stock']);
    }

    db_exec(
        $db,
        'INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
         VALUES (?, ?, ?, ?, ?, ?)',
        'isssss',
        $userId,
        json_encode($validated, JSON_THROW_ON_ERROR),
        cents_to_money($productCents),
        cents_to_money($shippingCents),
        $paymentMethod,
        'SUCCES'
    );

    return ['ok' => true];
}
