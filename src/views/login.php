<main class="container py-5 flex-grow-1">
    <div class="col-md-8 col-lg-6 mx-auto">
        <div id="warnings"></div>
        <form id="loginForm" class="bg-body-tertiary border rounded-4 p-4">
            <p class="text-uppercase small text-primary mb-1">Account</p>
            <h1 class="h2 mb-3">Inloggen</h1>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="floatingInput" placeholder="name@example.com" autocomplete="username" required>
                <label for="floatingInput">E-mailadres</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingPassword" placeholder="Wachtwoord" autocomplete="current-password" required>
                <label for="floatingPassword">Wachtwoord</label>
            </div>
            <button id="logInBtn" class="btn btn-primary w-100 py-2 mt-3" type="submit">Inloggen</button>
            <p class="mt-3 mb-0 text-body-secondary">Nog geen account? <a href="/registreren">Registreren</a></p>
        </form>
    </div>
</main>
