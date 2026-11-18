<main class="container py-5 flex-grow-1">
    <header class="mb-4">
        <p class="text-uppercase small text-primary mb-1">Collectie</p>
        <h1>Producten</h1>
    </header>
    <div class="row g-4 align-items-start">
        <aside class="col-lg-3">
            <div class="bg-body-tertiary border rounded-4 p-4">
            <h2 class="h4">Filters</h2>
            <form method="get" action="/producten">
                <label class="form-label" for="sort">Sorteren</label>
                <select class="form-select mb-3" id="sort" name="sort">
                    <?php foreach ($sorts as $value => $label) { ?>
                        <option value="<?php echo e($value); ?>" <?php echo $sort === $value ? 'selected' : ''; ?>>
                            <?php echo e($label); ?>
                        </option>
                    <?php } ?>
                </select>
                <fieldset>
                    <legend class="form-label">Prijsbereik</legend>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label" for="minPrice">Minimum</label>
                            <input class="form-control" type="number" name="min" id="minPrice" min="0" step="0.01" inputmode="decimal" value="<?php echo e((string) $minPrice); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label" for="maxPrice">Maximum</label>
                            <input class="form-control" type="number" name="max" id="maxPrice" min="0" step="0.01" inputmode="decimal" value="<?php echo e((string) $maxPrice); ?>">
                        </div>
                    </div>
                </fieldset>
                <button class="btn btn-primary mt-3" type="submit">Toepassen</button>
            </form>
            </div>
        </aside>
        <div class="col-lg-9">
            <?php if ($catalogError) { ?>
                <div class="alert alert-warning" role="alert"><?php echo e((string) $catalogError); ?></div>
            <?php } elseif ($products === []) { ?>
                <p class="text-body-secondary">Geen producten binnen deze filters.</p>
            <?php } else { ?>
                <p class="text-body-secondary mb-3"><?php echo count($products); ?> producten</p>
                <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                    <?php foreach ($products as $product) { ?>
                        <div class="col">
                        <article class="card h-100 bg-body-tertiary">
                            <?php if ($product['img_url'] !== '') { ?>
                                <img class="card-img-top object-fit-cover ratio-portrait" src="<?php echo e((string) $product['img_url']); ?>" alt="<?php echo e((string) $product['product_name']); ?>">
                            <?php } ?>
                            <div class="card-body d-flex flex-column">
                                <h3 class="card-title h5 mb-0"><?php echo e((string) $product['product_name']); ?></h3>
                                <p class="card-text text-primary fw-medium mb-0">€ <?php echo e(number_format($product['price_cents'] / 100, 2, ',', '.')); ?></p>
                                <a class="btn btn-primary btn-sm mt-2 align-self-start" href="/product/<?php echo (int) $product['id']; ?>">Bekijk product</a>
                            </div>
                        </article>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</main>
