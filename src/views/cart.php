<main class="container py-5 flex-grow-1">
    <header class="mb-4">
        <p class="text-uppercase small text-primary mb-1">Bestellen</p>
        <h1>Winkelwagen</h1>
    </header>
    <?php if (!is_logged_in()) { ?>
        <div class="alert alert-warning" role="alert">Log in om af te rekenen.</div>
    <?php } ?>
    <div class="warnings"></div>
    <div class="bg-body-tertiary border rounded-4 p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Artikel</th>
                        <th scope="col">Maat</th>
                        <th scope="col">Hoeveelheid</th>
                        <th scope="col">Prijs</th>
                        <th scope="col">Verwijderen</th>
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
                        <td>
                            <?php if (is_logged_in()) { ?>
                                <a id="checkout" class="btn btn-primary" href="/afrekenen">Afrekenen</a>
                            <?php } else { ?>
                                <button id="checkout" class="btn btn-primary" type="button" disabled>Afrekenen</button>
                            <?php } ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>
