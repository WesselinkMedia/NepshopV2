# nepshop

**Work in progress.** A fictional webshop for practicing JavaScript, PHP and MariaDB.

PHP 8, MariaDB, JavaScript and Bootstrap. `public/index.php` starts the app, `src/router.php` chooses the page and `src/api.php` handles the JSON endpoints.

## Demo data

Import `database/seed.sql` after the schema. Every account uses the password `Nepshop1!`.

| Email | Role | Address |
| --- | --- | --- |
| admin@nepshop.test | Administrator | Domplein 1, 3511EP Utrecht |
| lisa@nepshop.test | Customer | Damrak 20, 1012JS Amsterdam |
| noah@nepshop.test | Customer | Coolsingel 42, 3011AD Rotterdam |

The seed also adds six products and sample orders for these accounts. Accounts created on the site are never administrators. Set `is_admin` to `1` in `users` to change that.

## Installation

PHP 8 and MariaDB. The web root is `public/`. With Laravel Herd the site is at `http://nepshop-v2.test`.

1. Copy `.env.example` to `.env`. Set `MARIADB_USERNAME`, `MARIADB_PASSWORD` and `DB_NAME`. The app connects to MariaDB on `127.0.0.1`. `POSTCODE_APIKEY` is optional and only used for the address lookup.
2. Import `database/schema.sql`, then `database/seed.sql`. Run the seed once, on empty `products`, `users` and `orders` tables.
3. Open the site and sign in with an account from the table above.

Product images are URLs, so no upload folder is needed.
