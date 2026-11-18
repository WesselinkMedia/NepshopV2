USE nepshop;

INSERT INTO products (product_name, product_description, img_url, size_amount, product_price)
VALUES
    (
        'Zwarte hoodie',
        'Een warme hoodie van zacht katoen met capuchon en kangoeroezak. Prettig voor dagelijks gebruik en makkelijk te combineren.',
        'https://images.unsplash.com/photo-1651761179569-4ba2aa054997?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        '{"xxs":8,"xs":14,"s":22,"m":30,"l":18,"xl":10,"xxl":4}',
        49.95
    ),
    (
        'Wit T-shirt',
        'Een basic T-shirt met een ronde hals. Licht van gewicht en geschikt als basisstuk onder een jas of sweater.',
        'https://images.unsplash.com/photo-1651761179569-4ba2aa054997?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        '{"xxs":12,"xs":20,"s":28,"m":35,"l":24,"xl":12,"xxl":6}',
        19.95
    ),
    (
        'Blauwe spijkerbroek',
        'Een rechte spijkerbroek van stevig denim. Valt op de taille en heeft ruimte bij de benen.',
        'https://images.unsplash.com/photo-1651761179569-4ba2aa054997?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        '{"xxs":0,"xs":6,"s":14,"m":20,"l":16,"xl":8,"xxl":3}',
        79.00
    ),
    (
        'Grijze sweater',
        'Een sweater met ronde hals en geribde boorden. Dik genoeg voor koelere dagen, zonder zwaar aan te voelen.',
        'https://images.unsplash.com/photo-1651761179569-4ba2aa054997?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        '{"xxs":5,"xs":11,"s":18,"m":26,"l":15,"xl":7,"xxl":2}',
        44.50
    ),
    (
        'Beige overshirt',
        'Een licht overshirt dat je open of dicht kunt dragen. Werkt als extra laag over een T-shirt.',
        'https://images.unsplash.com/photo-1651761179569-4ba2aa054997?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        '{"xxs":4,"xs":9,"s":16,"m":21,"l":13,"xl":6,"xxl":0}',
        59.95
    ),
    (
        'Zwarte broek',
        'Een rechte broek met een gladde stof. Geschikt voor doordeweekse dagen en iets nettere gelegenheden.',
        'https://images.unsplash.com/photo-1651761179569-4ba2aa054997?q=80&w=1470&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
        '{"xxs":3,"xs":8,"s":15,"m":19,"l":14,"xl":9,"xxl":5}',
        69.00
    );

-- Sample accounts. Every account uses the password Nepshop1!
INSERT INTO users (email_address, password_, postcode, house_number, street_name, city, is_admin)
VALUES
    (
        'admin@nepshop.test',
        '$2y$12$t0MAUlKpYLQS0BY/EjV4o.A1z3PK71QUfSRKlmt4ZRYzRTMoo8DA2',
        '3511EP',
        '1',
        'Domplein',
        'Utrecht',
        1
    ),
    (
        'lisa@nepshop.test',
        '$2y$12$c96Kad1BFecQMYZqLCM82.6MXAeLhGA8KyQitpJhQfVrsK8h70y0.',
        '1012JS',
        '20',
        'Damrak',
        'Amsterdam',
        0
    ),
    (
        'noah@nepshop.test',
        '$2y$12$MGwJyUbMYpK9hVw5fJZApO4YUFom8/BwQeKeE3nf4WFuFsT3PFzsy',
        '3011AD',
        '42',
        'Coolsingel',
        'Rotterdam',
        0
    );

-- Product totals follow the shop prices. Shipping is 4.99 under 50.00 and free from 50.00.
INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', p.id, 'name', p.product_name, 'size', 'm', 'amount', 1, 'unit_price', '49.95')
    ),
    49.95,
    4.99,
    'PalPay',
    'SUCCES'
FROM users AS u
INNER JOIN products AS p ON p.product_name = 'Zwarte hoodie'
WHERE u.email_address = 'lisa@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', jeans.id, 'name', jeans.product_name, 'size', 'l', 'amount', 1, 'unit_price', '79.00'),
        JSON_OBJECT('id', shirt.id, 'name', shirt.product_name, 'size', 'm', 'amount', 1, 'unit_price', '19.95')
    ),
    98.95,
    0.00,
    'Mydeal',
    'SUCCES'
FROM users AS u
INNER JOIN products AS jeans ON jeans.product_name = 'Blauwe spijkerbroek'
INNER JOIN products AS shirt ON shirt.product_name = 'Wit T-shirt'
WHERE u.email_address = 'lisa@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', p.id, 'name', p.product_name, 'size', 's', 'amount', 2, 'unit_price', '19.95')
    ),
    39.90,
    4.99,
    'Klorno',
    'SUCCES'
FROM users AS u
INNER JOIN products AS p ON p.product_name = 'Wit T-shirt'
WHERE u.email_address = 'noah@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', sweater.id, 'name', sweater.product_name, 'size', 'xl', 'amount', 1, 'unit_price', '44.50'),
        JSON_OBJECT('id', pants.id, 'name', pants.product_name, 'size', 'm', 'amount', 1, 'unit_price', '69.00')
    ),
    113.50,
    0.00,
    'PalPay',
    'SUCCES'
FROM users AS u
INNER JOIN products AS sweater ON sweater.product_name = 'Grijze sweater'
INNER JOIN products AS pants ON pants.product_name = 'Zwarte broek'
WHERE u.email_address = 'noah@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', p.id, 'name', p.product_name, 'size', 'm', 'amount', 1, 'unit_price', '59.95')
    ),
    59.95,
    0.00,
    'Mydeal',
    'SUCCES'
FROM users AS u
INNER JOIN products AS p ON p.product_name = 'Beige overshirt'
WHERE u.email_address = 'admin@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', pants.id, 'name', pants.product_name, 'size', 's', 'amount', 1, 'unit_price', '69.00'),
        JSON_OBJECT('id', hoodie.id, 'name', hoodie.product_name, 'size', 'l', 'amount', 1, 'unit_price', '49.95')
    ),
    118.95,
    0.00,
    'PalPay',
    'SUCCES'
FROM users AS u
INNER JOIN products AS pants ON pants.product_name = 'Zwarte broek'
INNER JOIN products AS hoodie ON hoodie.product_name = 'Zwarte hoodie'
WHERE u.email_address = 'admin@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', p.id, 'name', p.product_name, 'size', 'l', 'amount', 1, 'unit_price', '19.95')
    ),
    19.95,
    4.99,
    'Klorno',
    'SUCCES'
FROM users AS u
INNER JOIN products AS p ON p.product_name = 'Wit T-shirt'
WHERE u.email_address = 'admin@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', p.id, 'name', p.product_name, 'size', 's', 'amount', 1, 'unit_price', '44.50')
    ),
    44.50,
    4.99,
    'Klorno',
    'SUCCES'
FROM users AS u
INNER JOIN products AS p ON p.product_name = 'Grijze sweater'
WHERE u.email_address = 'lisa@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', shirt.id, 'name', shirt.product_name, 'size', 'l', 'amount', 1, 'unit_price', '59.95'),
        JSON_OBJECT('id', pants.id, 'name', pants.product_name, 'size', 'm', 'amount', 1, 'unit_price', '69.00')
    ),
    128.95,
    0.00,
    'Mydeal',
    'SUCCES'
FROM users AS u
INNER JOIN products AS shirt ON shirt.product_name = 'Beige overshirt'
INNER JOIN products AS pants ON pants.product_name = 'Zwarte broek'
WHERE u.email_address = 'lisa@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', shirt.id, 'name', shirt.product_name, 'size', 'xs', 'amount', 1, 'unit_price', '19.95'),
        JSON_OBJECT('id', sweater.id, 'name', sweater.product_name, 'size', 'm', 'amount', 1, 'unit_price', '44.50')
    ),
    64.45,
    0.00,
    'PalPay',
    'SUCCES'
FROM users AS u
INNER JOIN products AS shirt ON shirt.product_name = 'Wit T-shirt'
INNER JOIN products AS sweater ON sweater.product_name = 'Grijze sweater'
WHERE u.email_address = 'lisa@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', p.id, 'name', p.product_name, 'size', 's', 'amount', 2, 'unit_price', '49.95')
    ),
    99.90,
    0.00,
    'Mydeal',
    'SUCCES'
FROM users AS u
INNER JOIN products AS p ON p.product_name = 'Zwarte hoodie'
WHERE u.email_address = 'lisa@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', hoodie.id, 'name', hoodie.product_name, 'size', 'xl', 'amount', 1, 'unit_price', '49.95'),
        JSON_OBJECT('id', shirt.id, 'name', shirt.product_name, 'size', 's', 'amount', 1, 'unit_price', '59.95')
    ),
    109.90,
    0.00,
    'Klorno',
    'SUCCES'
FROM users AS u
INNER JOIN products AS hoodie ON hoodie.product_name = 'Zwarte hoodie'
INNER JOIN products AS shirt ON shirt.product_name = 'Beige overshirt'
WHERE u.email_address = 'noah@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', p.id, 'name', p.product_name, 'size', 'm', 'amount', 1, 'unit_price', '79.00')
    ),
    79.00,
    0.00,
    'Mydeal',
    'SUCCES'
FROM users AS u
INNER JOIN products AS p ON p.product_name = 'Blauwe spijkerbroek'
WHERE u.email_address = 'noah@nepshop.test';

INSERT INTO orders (customer_id, items, total_amount, shipping_costs, payment_method, status)
SELECT
    u.user_id_,
    JSON_ARRAY(
        JSON_OBJECT('id', p.id, 'name', p.product_name, 'size', 'm', 'amount', 1, 'unit_price', '19.95')
    ),
    19.95,
    4.99,
    'PalPay',
    'SUCCES'
FROM users AS u
INNER JOIN products AS p ON p.product_name = 'Wit T-shirt'
WHERE u.email_address = 'noah@nepshop.test';
