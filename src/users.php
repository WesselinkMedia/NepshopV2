<?php

require_once __DIR__ . '/address.php';

function find_user_by_email(mysqli $db, string $email): ?array
{
    return db_row(
        $db,
        'SELECT user_id_, password_, is_admin FROM users WHERE email_address = ?',
        's',
        $email
    );
}

function find_user_profile(mysqli $db, int $userId): ?array
{
    return db_row(
        $db,
        'SELECT user_id_, email_address, postcode, house_number, street_name, city
         FROM users WHERE user_id_ = ?',
        'i',
        $userId
    );
}

function email_taken(mysqli $db, string $email): bool
{
    return db_row($db, 'SELECT user_id_ FROM users WHERE email_address = ?', 's', $email) !== null;
}

function password_error(mixed $password): ?string
{
    if (!is_string($password)) {
        return 'Vul een wachtwoord in.';
    }
    if (strlen($password) < 8) {
        return 'Het wachtwoord is te kort.';
    }
    if (strlen($password) > 30) {
        return 'Het wachtwoord is te lang.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Het wachtwoord heeft minstens 1 hoofdletter nodig.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Het wachtwoord heeft minstens 1 kleine letter nodig.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Het wachtwoord heeft minstens 1 cijfer nodig.';
    }
    if (!preg_match('/[!@#$%&*?.]/', $password)) {
        return 'Het wachtwoord heeft minstens 1 speciaal teken nodig (!@#$%&*?.).';
    }
    if (preg_match('/[^A-Za-z0-9!@#$%&*?.]/', $password)) {
        return 'Het wachtwoord bevat een teken dat niet is toegestaan.';
    }

    return null;
}

function create_user(mysqli $db, array $input): ?string
{
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = $input['password'] ?? null;
    $confirmation = $input['password_confirmation'] ?? null;
    $postcode = strtoupper(str_replace(' ', '', (string) ($input['postcode'] ?? '')));
    $houseNumber = trim((string) ($input['house_number'] ?? ''));
    $streetName = clean_address_part((string) ($input['street_name'] ?? ''));
    $city = clean_address_part((string) ($input['city'] ?? ''));

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254) {
        return 'Vul een geldig e-mailadres in.';
    }

    $passwordError = password_error($password);
    if ($passwordError !== null) {
        return $passwordError;
    }
    if (!is_string($confirmation) || !hash_equals($password, $confirmation)) {
        return 'Wachtwoorden zijn niet hetzelfde.';
    }

    if (!postcode_valid($postcode)) {
        return 'Vul een geldige postcode in, bijvoorbeeld 1234AB.';
    }
    if (!house_number_valid($houseNumber)) {
        return 'Vul een geldig huisnummer in.';
    }

    $resolved = lookup_address($postcode, $houseNumber);
    if ($resolved !== null) {
        $streetName = $resolved['street'];
        $city = $resolved['city'];
    }

    if ($streetName === '' || strlen($streetName) > 100 || $city === '' || strlen($city) > 100) {
        return 'Vul een straatnaam en plaats in.';
    }
    if (email_taken($db, $email)) {
        return 'Dit e-mailadres is al geregistreerd.';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        db_exec(
            $db,
            'INSERT INTO users (email_address, password_, postcode, house_number, street_name, city, is_admin)
             VALUES (?, ?, ?, ?, ?, ?, 0)',
            'ssssss',
            $email,
            $hash,
            $postcode,
            $houseNumber,
            $streetName,
            $city
        );
    } catch (mysqli_sql_exception $exception) {
        if ((int) $exception->getCode() === 1062) {
            return 'Dit e-mailadres is al geregistreerd.';
        }
        throw $exception;
    }

    return null;
}
