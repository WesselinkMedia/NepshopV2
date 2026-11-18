<main class="container py-5 flex-grow-1">
    <header class="mb-4">
        <p class="text-uppercase small text-primary mb-1">Bestellen</p>
        <h1>Controleer je bestelling</h1>
    </header>
    <div class="warnings"></div>
    <div class="row g-3">
        <div id="shippingInfo" class="col-lg bg-body-tertiary border rounded-4 p-4">
            <h2 class="h4">Adresgegevens</h2>
            <h3 class="h6 text-body-secondary">E-mailadres</h3>
            <p id="email"></p>
            <h3 class="h6 text-body-secondary">Klantnummer</h3>
            <p id="userId"><?php echo (int) $_SESSION['user_id']; ?></p>
            <h3 class="h6 text-body-secondary">Adres</h3>
            <p id="streetNameWithNum" class="mb-1"></p>
            <p id="postcodeAndCity"></p>
        </div>
        <div id="cart" class="col-lg bg-body-tertiary border rounded-4 p-4">
            <h2 class="h4">Winkelwagen</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Artikel</th>
                            <th scope="col">Maat</th>
                            <th scope="col">Hoeveelheid</th>
                            <th scope="col">Prijs</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th scope="row">Totaal</th>
                            <td id="totalDiffId"></td>
                            <td></td>
                            <td id="totalAmount"></td>
                            <td id="totalPrice"></td>
                        </tr>
                        <tr>
                            <th scope="row">Verzendkosten</th>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td id="shippingCost"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="bg-body-tertiary border rounded-4 p-4 mt-3">
        <h2 class="h4">Betaalmethode</h2>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="paymentMethod" id="paymentMethod1" value="Mydeal" checked>
            <label class="form-check-label" for="paymentMethod1">Mydeal</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="paymentMethod" id="paymentMethod2" value="PalPay">
            <label class="form-check-label" for="paymentMethod2">PalPay</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="paymentMethod" id="paymentMethod3" value="Klorno">
            <label class="form-check-label" for="paymentMethod3">Klorno</label>
        </div>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
        <h2 class="h3 mb-0">Totaal te betalen: €<span id="totalWithShipping"></span></h2>
        <button id="checkoutBtn" class="btn btn-primary btn-lg" type="button">Afrekenen</button>
    </div>
</main>
