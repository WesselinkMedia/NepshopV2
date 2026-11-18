<main class="container py-5 flex-grow-1">
    <div class="col-md-8 col-lg-6 mx-auto">
        <form class="bg-body-tertiary border rounded-4 p-4" method="post" action="/producten/toevoegen">
            <p class="text-uppercase small text-primary mb-1">Beheer</p>
            <h1 class="h2">Nieuw product</h1>
            <?php if ($added) { ?>
                <div class="alert alert-success" role="alert">Product toegevoegd.</div>
            <?php } ?>
            <?php if ($error !== null) { ?>
                <div class="alert alert-warning" role="alert"><?php echo e($error); ?></div>
            <?php } ?>
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <div class="mb-3">
                <label for="productName" class="form-label">Naam product</label>
                <input type="text" class="form-control" name="productName" id="productName" maxlength="50" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Productbeschrijving</label>
                <textarea class="form-control" name="description" id="description" maxlength="500" required></textarea>
            </div>
            <div class="mb-3">
                <label for="imgUrl" class="form-label">Foto-URL</label>
                <input type="url" name="imgUrl" class="form-control" id="imgUrl" maxlength="2048" required>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Prijs</label>
                <input type="number" step="0.01" min="0.01" name="price" class="form-control" id="price" required>
            </div>
            <div class="mb-3">
                <label for="sizeAmount" class="form-label">Maten en voorraad (JSON)</label>
                <input type="text" name="sizeAmount" class="form-control" id="sizeAmount" value='{"xxs":0,"xs":0,"s":0,"m":0,"l":0,"xl":0,"xxl":0}' required>
            </div>
            <button type="submit" class="btn btn-primary">Toevoegen</button>
        </form>
    </div>
</main>
