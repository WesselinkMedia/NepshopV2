function csrfHeaders() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    return {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token,
    };
}

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) {
        node.className = className;
    }
    if (text !== undefined) {
        node.textContent = text;
    }
    return node;
}

function warning(message) {
    const node = el('div', 'alert alert-warning', message);
    node.role = 'alert';
    return node;
}

function passwordError(password) {
    if (password.length < 8) {
        return 'Het wachtwoord is te kort.';
    }
    if (password.length > 30) {
        return 'Het wachtwoord is te lang.';
    }
    if (!/[A-Z]/.test(password)) {
        return 'Het wachtwoord heeft minstens 1 hoofdletter nodig.';
    }
    if (!/[a-z]/.test(password)) {
        return 'Het wachtwoord heeft minstens 1 kleine letter nodig.';
    }
    if (!/[0-9]/.test(password)) {
        return 'Het wachtwoord heeft minstens 1 cijfer nodig.';
    }
    if (!/[!@#$%&*?.]/.test(password)) {
        return 'Het wachtwoord heeft minstens 1 speciaal teken nodig (!@#$%&*?.).';
    }
    if (/[^A-Za-z0-9!@#$%&*?.]/.test(password)) {
        return 'Het wachtwoord bevat een teken dat niet is toegestaan.';
    }
    return null;
}

function postcodeValid(postcode) {
    return /^[0-9]{4}[A-Za-z]{2}$/.test(postcode);
}

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: csrfHeaders(),
        body: JSON.stringify(body),
    });
    const result = await response.json();
    return { response, result };
}

class ShoppingCart {
    constructor() {
        this.items = { shoppingCart: [] };
        this.totalCents = 0;
        this.shippingCents = 0;
        this.changed = false;
        this.error = null;
    }

    apply(result) {
        this.items.shoppingCart = Array.isArray(result.cart) ? result.cart : [];
        this.totalCents = Number.isInteger(result.total_cents) ? result.total_cents : 0;
        this.shippingCents = Number.isInteger(result.shipping_cents) ? result.shipping_cents : 0;
        this.changed = result.succes === false;
        this.error = null;
        window.localStorage.removeItem('cart');
    }

    async refresh() {
        try {
            const response = await fetch('/api/cart');
            const result = await response.json();
            if (!response.ok) {
                this.items.shoppingCart = [];
                this.totalCents = 0;
                this.shippingCents = 0;
                this.changed = false;
                this.error = result.error || 'De winkelwagen kon niet worden geladen.';
                return result;
            }
            this.apply(result);
            return result;
        } catch {
            this.error = 'De winkelwagen kon niet worden geladen.';
            return { succes: false };
        }
    }

    async add(id, size, amount) {
        const { response, result } = await postJson('/api/cart', { id, size, amount });
        if (!response.ok) {
            throw new Error(result.error || 'Het product kon niet worden toegevoegd.');
        }
        this.apply(result);
        return result;
    }

    async remove(product, amount) {
        const { response, result } = await postJson('/api/cart/remove', {
            id: product.id,
            size: product.size,
            amount,
        });
        if (!response.ok) {
            throw new Error(result.error || 'Het product kon niet worden verwijderd.');
        }
        this.apply(result);
        return result;
    }

    async checkout(paymentMethod) {
        try {
            const { response, result } = await postJson('/api/checkout', {
                paymentMethod,
                totalCents: this.totalCents,
                shippingCents: this.shippingCents,
            });
            if (!response.ok || !result.succes) {
                return {
                    ok: false,
                    reason: result.reason || result.error || 'De bestelling kon niet worden geplaatst.',
                };
            }
            this.items = { shoppingCart: [] };
            this.totalCents = 0;
            this.shippingCents = 0;
            return { ok: true };
        } catch {
            return { ok: false, reason: 'De bestelling kon niet worden geplaatst.' };
        }
    }
}

function formatEuros(cents) {
    return `€ ${(cents / 100).toFixed(2)}`;
}

function renderCartTable(cart, { removable = false, onRemoved = () => {} } = {}) {
    const tbody = document.querySelector('tbody');
    tbody.replaceChildren();

    let totalAmount = 0;
    let totalCents = 0;

    for (const product of cart.items.shoppingCart) {
        const lineCents = Math.round(Number(product.price) * 100) * Number(product.amount);
        totalAmount += Number(product.amount);
        totalCents += lineCents;

        const row = el('tr', 'text-content-center');
        const idCell = document.createElement('th');
        idCell.scope = 'row';
        idCell.textContent = product.id;
        row.append(
            idCell,
            el('td', '', product.productName),
            el('td', '', product.size),
            el('td', '', String(product.amount)),
            el('td', '', formatEuros(lineCents)),
        );

        if (removable) {
            const select = el('select', 'form-select');
            select.setAttribute('aria-label', 'Aantal verwijderen');
            for (let i = 1; i <= Number(product.amount); i++) {
                select.appendChild(el('option', '', String(i)));
                select.lastChild.value = String(i);
            }

            const button = el('button', 'btn-close');
            button.type = 'button';
            button.setAttribute('aria-label', 'Verwijder uit winkelwagen');
            button.addEventListener('click', async function () {
                button.disabled = true;
                try {
                    await onRemoved(product, Number(select.value));
                } catch (error) {
                    button.disabled = false;
                    const warnings = document.querySelector('.warnings');
                    if (warnings) {
                        warnings.replaceChildren(warning(error.message || 'Het product kon niet worden verwijderd.'));
                    }
                }
            });

            const controls = el('div', 'row');
            const selectCol = el('div', 'col');
            const buttonCol = el('div', 'col');
            selectCol.appendChild(select);
            buttonCol.appendChild(button);
            controls.append(selectCol, buttonCol);
            const cell = document.createElement('td');
            cell.appendChild(controls);
            row.appendChild(cell);
        }

        tbody.appendChild(row);
    }

    document.querySelector('#totalDiffId').textContent = String(cart.items.shoppingCart.length);
    document.querySelector('#totalAmount').textContent = String(totalAmount);
    document.querySelector('#totalPrice').textContent = formatEuros(totalCents);
}

function showCartNotice(cart, warnings) {
    if (!warnings) {
        return;
    }
    warnings.replaceChildren();
    if (cart.error) {
        warnings.appendChild(warning(cart.error));
    } else if (cart.changed) {
        warnings.appendChild(warning('Winkelwagen is bijgewerkt omdat niet alles op voorraad is.'));
    }
}

function initLogin() {
    const form = document.querySelector('#loginForm');
    const warnings = document.querySelector('#warnings');
    if (!form || !warnings) {
        return;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        warnings.replaceChildren();

        const email = document.querySelector('#floatingInput').value;
        const password = document.querySelector('#floatingPassword').value;
        if (!email || !password) {
            warnings.appendChild(warning('Vul alle velden in.'));
            return;
        }

        try {
            const { response, result } = await postJson('/api/login', { email, password });
            if (!response.ok || !result.succes) {
                warnings.appendChild(warning(result.error || 'E-mail of wachtwoord incorrect.'));
                return;
            }
            document.location.href = '/';
        } catch {
            warnings.appendChild(warning('Inloggen is tijdelijk niet mogelijk.'));
        }
    });
}

function initRegister() {
    const form = document.querySelector('#registerForm');
    const warnings = document.querySelector('#warnings');
    if (!form || !warnings) {
        return;
    }

    const fields = {
        email: document.querySelector('#floatingInput'),
        password: document.querySelector('#floatingPassword0'),
        confirmation: document.querySelector('#floatingPassword1'),
        postcode: document.querySelector('#postcode'),
        houseNumber: document.querySelector('#houseNumber'),
        street: document.querySelector('#streetName'),
        city: document.querySelector('#city'),
    };

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        warnings.replaceChildren();
        const messages = [];
        const missing = Object.values(fields).some((field) => !field.value);
        if (missing) {
            messages.push('Vul alle velden in.');
        }
        if (fields.password.value) {
            const error = passwordError(fields.password.value);
            if (error) {
                messages.push(error);
            }
        }
        if (fields.password.value !== fields.confirmation.value) {
            messages.push('Wachtwoorden zijn niet hetzelfde.');
        }
        const postcode = fields.postcode.value.replace(/\s/g, '');
        if (fields.postcode.value && !postcodeValid(postcode)) {
            messages.push('Vul een geldige postcode in, bijvoorbeeld 1234AB.');
        }
        if (messages.length > 0) {
            warnings.append(...messages.map(warning));
            return;
        }

        try {
            const { response, result } = await postJson('/api/users', {
                email: fields.email.value,
                password: fields.password.value,
                password_confirmation: fields.confirmation.value,
                postcode,
                house_number: fields.houseNumber.value,
                street_name: fields.street.value,
                city: fields.city.value,
            });
            if (!response.ok || !result.succes) {
                warnings.appendChild(warning(result.error || 'Het account kon niet worden aangemaakt.'));
                return;
            }
            window.location.href = '/inloggen';
        } catch {
            warnings.appendChild(warning('Het account kon niet worden aangemaakt.'));
        }
    });

    document.querySelector('#fillAddress').addEventListener('click', async function () {
        warnings.replaceChildren();
        const postcode = fields.postcode.value.replace(/\s/g, '');
        const houseNumber = fields.houseNumber.value.trim();
        if (!postcodeValid(postcode) || !houseNumber) {
            warnings.appendChild(warning('Controleer postcode en huisnummer.'));
            return;
        }

        try {
            const params = new URLSearchParams({ postcode, house_number: houseNumber });
            const response = await fetch(`/api/address?${params}`);
            const result = await response.json();
            if (!response.ok || !result.street || !result.city) {
                warnings.appendChild(warning('Adres niet gevonden. Vul het handmatig in.'));
                return;
            }
            fields.street.value = result.street;
            fields.city.value = result.city;
        } catch (error) {
            console.error(error.message);
        }
    });
}

function initProduct() {
    const addButton = document.querySelector('#addToCart');
    const warnings = document.querySelector('#warnings');
    const amountSelect = document.querySelector('#amountSelect');
    const productIdNode = document.querySelector('#productId');
    if (!addButton || !warnings || !amountSelect || !productIdNode) {
        return;
    }

    const cart = new ShoppingCart();
    const productId = Number(productIdNode.textContent);

    function fillAmounts(available) {
        amountSelect.replaceChildren();
        const count = Number.isInteger(available) && available > 0 ? Math.min(available, 100) : 0;
        if (count === 0) {
            amountSelect.appendChild(el('option', '', 'Niet op voorraad'));
            amountSelect.lastChild.value = '';
            return;
        }
        for (let i = 1; i <= count; i++) {
            amountSelect.appendChild(el('option', '', String(i)));
            amountSelect.lastChild.value = String(i);
        }
    }

    for (const size of document.getElementsByName('sizes')) {
        size.addEventListener('change', function () {
            fillAmounts(Number(size.dataset.stock));
        });
    }

    addButton.addEventListener('click', async function () {
        warnings.replaceChildren();
        const selected = document.querySelector('input[name="sizes"]:checked');
        const amount = Number(amountSelect.value);
        if (!selected) {
            warnings.appendChild(warning('Kies een maat.'));
            return;
        }
        if (!Number.isInteger(amount) || amount < 1) {
            warnings.appendChild(warning('Kies een geldig aantal.'));
            return;
        }

        addButton.disabled = true;
        try {
            const result = await cart.add(productId, selected.id, amount);
            const notice = el('div', 'alert alert-success', 'Product toegevoegd aan de winkelwagen.');
            notice.role = 'alert';
            warnings.appendChild(notice);
            if (result.succes === false) {
                warnings.appendChild(warning('Winkelwagen is bijgewerkt omdat niet alles op voorraad is.'));
            }
        } catch (error) {
            warnings.appendChild(warning(error.message || 'Het product kon niet worden toegevoegd.'));
        } finally {
            addButton.disabled = false;
        }
    });
}

function initCart() {
    const cart = new ShoppingCart();
    const warnings = document.querySelector('.warnings');
    const checkoutLink = document.querySelector('#checkout');

    function paint() {
        showCartNotice(cart, warnings);
        if (checkoutLink) {
            const empty = cart.items.shoppingCart.length === 0;
            checkoutLink.classList.toggle('disabled', empty);
            checkoutLink.setAttribute('aria-disabled', empty ? 'true' : 'false');
        }
        renderCartTable(cart, {
            removable: true,
            onRemoved: async (product, amount) => {
                await cart.remove(product, amount);
                paint();
            },
        });
    }

    if (checkoutLink) {
        checkoutLink.addEventListener('click', function (event) {
            if (cart.items.shoppingCart.length === 0) {
                event.preventDefault();
            }
        });
    }

    cart.refresh().then(paint);
}

function initCheckout() {
    const cart = new ShoppingCart();
    const warnings = document.querySelector('.warnings');
    const checkoutButton = document.querySelector('#checkoutBtn');
    if (!checkoutButton) {
        return;
    }

    checkoutButton.disabled = true;

    async function paint() {
        if (warnings) {
            warnings.replaceChildren();
        }
        await cart.refresh();
        showCartNotice(cart, warnings);
        renderCartTable(cart);
        document.querySelector('#shippingCost').textContent = formatEuros(cart.shippingCents);
        document.querySelector('#totalWithShipping').textContent = (
            (cart.totalCents + cart.shippingCents) / 100
        ).toFixed(2);
        checkoutButton.disabled = cart.items.shoppingCart.length === 0 || cart.error !== null;
    }

    fetch('/api/account', { method: 'POST' })
        .then(async (response) => {
            if (!response.ok) {
                throw new Error(`Response status: ${response.status}`);
            }
            const result = await response.json();
            document.querySelector('#email').textContent = result.email_address;
            document.querySelector('#streetNameWithNum').textContent =
                `${result.street_name} ${result.house_number}`;
            document.querySelector('#postcodeAndCity').textContent =
                `${result.postcode}, ${result.city}`;
        })
        .catch((error) => console.error(error.message));

    checkoutButton.addEventListener('click', async function () {
        if (cart.items.shoppingCart.length === 0) {
            alert('Je winkelwagen is leeg.');
            return;
        }
        const selectedPayment = document.querySelector('input[name="paymentMethod"]:checked');
        if (!selectedPayment) {
            alert('Kies een betaalmethode.');
            return;
        }

        checkoutButton.disabled = true;
        const result = await cart.checkout(selectedPayment.value);
        if (result.ok) {
            alert('Bestelling geplaatst.');
            window.location.href = '/';
            return;
        }
        checkoutButton.disabled = false;
        alert(result.reason);
        paint();
    });

    paint();
}

function productCard(product) {
    const card = el('div', 'card col h-100 bg-body-tertiary');
    if (typeof product.img_url === 'string' && /^https?:\/\//i.test(product.img_url)) {
        const image = el('img', 'card-img-top object-fit-cover ratio-portrait');
        image.src = product.img_url;
        card.appendChild(image);
    }
    const body = el('div', 'card-body d-flex flex-column');
    const link = el('a', 'btn btn-primary btn-sm mt-2 align-self-start', 'Bekijk product');
    link.href = `/product/${product.id}`;
    body.append(
        el('h5', 'card-title mb-0', product.product_name),
        el('p', 'card-text text-primary fw-medium mb-0', `€${product.product_price}`),
        link,
    );
    card.append(body);
    return card;
}

function initHome() {
    const carousel = document.querySelector('#carouselProducts');
    if (!carousel) {
        return;
    }

    function showEmptyCatalog() {
        document
            .querySelectorAll('#carouselProductsSlide .carousel-control-prev, #carouselProductsSlide .carousel-control-next')
            .forEach((control) => control.remove());
        carousel.outerHTML = '<p class="text-body-secondary mb-0">De collectie is even niet beschikbaar.</p>';
    }

    fetch('/api/products')
        .then(async (response) => {
            if (!response.ok) {
                throw new Error(`Response status: ${response.status}`);
            }
            const results = await response.json();
            if (!Array.isArray(results) || results.length === 0) {
                showEmptyCatalog();
                return;
            }

            let slideCount = 0;
            let slide = null;
            let row = null;
            for (let i = 0; i < 12; i++) {
                if (slideCount === 0) {
                    slide = el('div', i === 0 ? 'carousel-item active' : 'carousel-item');
                    row = el('div', 'row row-cols-2 row-cols-lg-4 g-3');
                    slide.appendChild(row);
                    carousel.appendChild(slide);
                }
                row.appendChild(productCard(results[Math.floor(Math.random() * results.length)]));
                slideCount = slideCount >= 3 ? 0 : slideCount + 1;
            }
        })
        .catch((error) => {
            showEmptyCatalog();
            console.error(error.message);
        });
}

const pages = {
    home: initHome,
    login: initLogin,
    register: initRegister,
    product: initProduct,
    cart: initCart,
    checkout: initCheckout,
};

pages[document.body.dataset.page]?.();
