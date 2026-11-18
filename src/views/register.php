<main class="container py-5 flex-grow-1">
    <div class="col-md-8 col-lg-6 mx-auto">
        <div id="warnings"></div>
        <form id="registerForm" class="bg-body-tertiary border rounded-4 p-4">
            <p class="text-uppercase small text-primary mb-1">Account</p>
            <h1 class="h2">Account aanmaken</h1>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingInput" placeholder="name@example.com" autocomplete="username" required>
                <label for="floatingInput">E-mailadres</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingPassword0" placeholder="Wachtwoord" autocomplete="new-password" required>
                <label for="floatingPassword0">Wachtwoord</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingPassword1" placeholder="Wachtwoord" autocomplete="new-password" required>
                <label for="floatingPassword1">Herhaal je wachtwoord</label>
            </div>
            <h2 class="h5 mt-4">Adresgegevens</h2>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="postcode" placeholder="Postcode" minlength="6" maxlength="6" required>
                <label for="postcode">Postcode</label>
            </div>
            <div class="row mt-2 g-2">
                <div class="col-md-8">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="houseNumber" placeholder="Huisnummer" required>
                        <label for="houseNumber">Huisnummer</label>
                    </div>
                </div>
                <div class="col d-flex align-items-end">
                    <button id="fillAddress" class="btn btn-outline-primary w-100" type="button">Vul adres in</button>
                </div>
            </div>
            <div class="form-floating mt-2">
                <input type="text" class="form-control" id="streetName" placeholder="Straatnaam" required>
                <label for="streetName">Straatnaam</label>
            </div>
            <div class="form-floating mt-2">
                <input type="text" class="form-control" id="city" placeholder="Plaats" required>
                <label for="city">Plaats</label>
            </div>
            <button id="registerBtn" class="btn btn-primary w-100 py-2 mt-3" type="submit">Registreren</button>
            <p class="mt-3 mb-0 text-body-secondary">Al een account? <a href="/inloggen">Inloggen</a></p>
        </form>
    </div>
</main>
