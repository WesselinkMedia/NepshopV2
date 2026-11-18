# nepshop

**Work in progress.** A fictional webshop for practicing JavaScript, PHP and MariaDB.

PHP 8, MariaDB, JavaScript and Bootstrap. The web root is `public/`. `public/index.php` starts the app, `src/router.php` chooses the page and `src/api.php` handles the JSON endpoints. With Laravel Herd the site is at `http://nepshop-v2.test`.

## Demo data

Import `database/seed.sql` after the schema. Every account uses the password `Nepshop1!`.

| Email | Role | Address |
| --- | --- | --- |
| admin@nepshop.test | Administrator | Domplein 1, 3511EP Utrecht |
| lisa@nepshop.test | Customer | Damrak 20, 1012JS Amsterdam |
| noah@nepshop.test | Customer | Coolsingel 42, 3011AD Rotterdam |

The seed also adds six products and sample orders for these accounts. Accounts created on the site are never administrators. Set `is_admin` to `1` in `users` to change that.

## Installation

```bash
cp .env.example .env
```

## Temporary

Fill in the database user, password, database name and optional postcode API key in `.env`. Product images are URLs, so no upload folder is needed. Import the seed once, on empty `products`, `users` and `orders` tables.
