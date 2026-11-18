<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navItems = [
    '/' => 'Home',
    '/producten' => 'Producten',
    '/faqs' => 'FAQs',
    '/over-ons' => 'Over ons',
];
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.8/bootstrap.min.css">
    <script src="/assets/vendor/bootstrap/5.3.8/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="/assets/css/styles.css?v=9">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
</head>

<body data-bs-theme="dark" data-page="<?php echo e($page ?? ''); ?>" class="d-flex flex-column min-vh-100">
<header class="site-header sticky-top border-bottom">
    <div class="container d-flex align-items-center flex-wrap flex-lg-nowrap gap-3 py-2">
        <a href="/" class="link-body-emphasis text-decoration-none fw-bold fs-3">nepshop</a>
        <nav class="nav nav-pills flex-nowrap overflow-auto order-3 order-lg-0 w-100 w-lg-auto" aria-label="Hoofdnavigatie">
            <?php foreach ($navItems as $href => $label) {
                $active = $href === '/' ? $currentPath === '/' : str_starts_with($currentPath, $href);
                ?>
                <a href="<?php echo e($href); ?>" class="nav-link<?php echo $active ? ' active' : ''; ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>><?php echo e($label); ?></a>
            <?php } ?>
        </nav>
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if (is_admin()) { ?>
                <a href="/producten/toevoegen" class="link-body-emphasis text-body-secondary text-decoration-none px-2">Toevoegen</a>
            <?php } ?>
            <?php if (is_logged_in()) { ?>
                <form method="post" action="/uitloggen" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <button type="submit" class="btn btn-link text-body-secondary text-decoration-none px-2">Uitloggen</button>
                </form>
            <?php } else { ?>
                <a href="/inloggen" class="link-body-emphasis text-body-secondary text-decoration-none px-2">Inloggen</a>
                <a href="/registreren" class="btn btn-primary btn-sm">Registreren</a>
            <?php } ?>
            <a href="/winkelwagen" id="cartBtn" class="btn btn-outline-primary d-inline-flex align-items-center gap-2" aria-label="Winkelwagen">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
                </svg>
                <span class="d-none d-sm-inline">Winkelwagen</span>
            </a>
        </div>
    </div>
</header>
