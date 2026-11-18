CREATE DATABASE IF NOT EXISTS nepshop
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nepshop;

CREATE TABLE IF NOT EXISTS users (
    user_id_ INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email_address VARCHAR(254) NOT NULL,
    password_ VARCHAR(255) NOT NULL,
    postcode CHAR(6) NOT NULL,
    house_number VARCHAR(10) NOT NULL,
    street_name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id_),
    UNIQUE KEY users_email_unique (email_address)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_name VARCHAR(50) NOT NULL,
    product_description VARCHAR(500) NOT NULL,
    img_url VARCHAR(2048) NOT NULL,
    size_amount JSON NOT NULL,
    product_price DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    order_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NOT NULL,
    items JSON NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_costs DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'PENDING',
    PRIMARY KEY (order_id),
    CONSTRAINT orders_customer_fk FOREIGN KEY (customer_id) REFERENCES users (user_id_)
) ENGINE=InnoDB;
