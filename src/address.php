<?php

require_once __DIR__ . '/database.php';

function postcode_valid(string $postcode): bool
{
    return (bool) preg_match('/^[0-9]{4}[A-Za-z]{2}$/', $postcode);
}

function house_number_valid(string $houseNumber): bool
{
    return (bool) preg_match('/^[0-9]{1,5}[A-Za-z]{0,3}$/', $houseNumber);
}

function lookup_address(string $postcode, string $houseNumber): ?array
{
    if (!postcode_valid($postcode) || !house_number_valid($houseNumber)) {
        return null;
    }

    try {
        $apiKey = env_value('POSTCODE_APIKEY');
    } catch (Throwable $exception) {
        return null;
    }
    if ($apiKey === '') {
        return null;
    }

    $url = 'https://postcode.tech/api/v1/postcode?' . http_build_query([
        'postcode' => $postcode,
        'number' => $houseNumber,
    ]);
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Bearer {$apiKey}\r\nAccept: application/json\r\n",
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $address = is_string($response) ? json_decode($response, true) : null;
    if (!is_array($address)) {
        return null;
    }

    $street = clean_address_part((string) ($address['street'] ?? ''));
    $city = clean_address_part((string) ($address['city'] ?? ''));
    if ($street === '' || $city === '' || strlen($street) > 100 || strlen($city) > 100) {
        return null;
    }

    return ['street' => $street, 'city' => $city];
}

function clean_address_part(string $value): string
{
    $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';

    return trim($value);
}
