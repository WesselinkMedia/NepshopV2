<main class="container py-5 flex-grow-1">
    <?php if ($product === null) { ?>
        <div class="py-4">
            <p class="text-uppercase small text-primary mb-1"><?php echo $notFound ? '404' : 'Fout'; ?></p>
            <h1><?php echo $notFound ? 'Product niet gevonden' : 'Product niet beschikbaar'; ?></h1>
            <p class="text-body-secondary"><?php echo e((string) $pageError); ?></p>
            <a class="btn btn-primary" href="/producten">Terug naar producten</a>
        </div>
    <?php } else { ?>
        <div id="warnings"></div>
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <div class="overflow-hidden rounded-4">
                <div id="carouselExample" class="carousel slide">
                    <div class="carousel-inner">
                        <?php for ($image = 0; $image < 3; $image++) { ?>
                            <div class="carousel-item<?php echo $image === 0 ? ' active' : ''; ?>">
                                <?php if ($imgUrl !== '') { ?>
                                    <img src="<?php echo e($imgUrl); ?>" class="d-block w-100 object-fit-cover ratio-portrait" alt="<?php echo e((string) $product['product_name']); ?>">
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExample" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Vorige</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExample" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Volgende</span>
                    </button>
                </div>
                </div>
            </div>
            <div class="col-lg-7">
                <p class="text-uppercase small text-primary mb-1">Artikel</p>
                <h1 id="productName"><?php echo e((string) $product['product_name']); ?></h1>
                <p class="text-primary fw-medium fs-2">€<span id="price"><?php echo e((string) $product['product_price']); ?></span></p>
                <span id="productId" class="visually-hidden"><?php echo (int) $product['id']; ?></span>
                <p class="text-body-secondary" id="productDescription"><?php echo e((string) $product['product_description']); ?></p>
                <div id="radioSizes" class="d-flex flex-wrap gap-2" role="group" aria-label="Maat">
                    <?php foreach ($sizes as $size => $amount) { ?>
                        <input type="radio" name="sizes" class="btn-check" id="<?php echo e((string) $size); ?>" data-stock="<?php echo (int) $amount; ?>" <?php echo $amount > 0 ? '' : 'disabled'; ?>>
                        <label class="btn btn-outline-primary" for="<?php echo e((string) $size); ?>"><?php echo e((string) $size); ?></label>
                    <?php } ?>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-3 mt-3">
                    <label class="visually-hidden" for="amountSelect">Aantal</label>
                    <select id="amountSelect" class="form-select w-100 w-sm-auto">
                        <option value="" selected>Kies een maat</option>
                    </select>
                    <button id="addToCart" class="btn btn-primary w-100 w-sm-auto" type="button">Voeg toe aan winkelwagen</button>
                </div>
            </div>
        </div>
    <?php } ?>
</main>
