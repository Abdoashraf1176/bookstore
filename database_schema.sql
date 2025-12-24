-- Create Database
CREATE DATABASE IF NOT EXISTS bookstore_system;
USE bookstore_system;

-- Publishers Table
CREATE TABLE publishers (
    publisher_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    address TEXT,
    telephone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books Table
CREATE TABLE books (
    isbn VARCHAR(13) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    publisher_id INT,
    publication_year INT,
    selling_price DECIMAL(10, 2) NOT NULL,
    category ENUM('Science', 'Art', 'Religion', 'History', 'Geography') NOT NULL,
    quantity_in_stock INT DEFAULT 0,
    threshold_quantity INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publisher_id) REFERENCES publishers(publisher_id),
    CHECK (quantity_in_stock >= 0),
    CHECK (selling_price > 0)
);

-- Authors Table
CREATE TABLE authors (
    author_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Book_Authors Junction Table (Many-to-Many)
CREATE TABLE book_authors (
    isbn VARCHAR(13),
    author_id INT,
    PRIMARY KEY (isbn, author_id),
    FOREIGN KEY (isbn) REFERENCES books(isbn) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(author_id) ON DELETE CASCADE
);

-- Orders Table (Orders from Publishers - Replenishment)
CREATE TABLE publisher_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(13),
    quantity INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Confirmed') DEFAULT 'Pending',
    confirmed_date TIMESTAMP NULL,
    FOREIGN KEY (isbn) REFERENCES books(isbn),
    CHECK (quantity > 0)
);

-- Users Table (Admin and Customers)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone_number VARCHAR(20),
    shipping_address TEXT,
    user_type ENUM('Admin', 'Customer') DEFAULT 'Customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customer Orders Table (Sales)
CREATE TABLE customer_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_price DECIMAL(10, 2) NOT NULL,
    credit_card_number VARCHAR(16),
    card_expiry_date VARCHAR(7),
    status ENUM('Completed', 'Pending') DEFAULT 'Completed',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Order Items Table
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    isbn VARCHAR(13),
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES customer_orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (isbn) REFERENCES books(isbn),
    CHECK (quantity > 0)
);

-- Shopping Cart Table
CREATE TABLE shopping_cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    isbn VARCHAR(13),
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (isbn) REFERENCES books(isbn) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, isbn),
    CHECK (quantity > 0)
);

-- Trigger: Prevent negative stock on update
DELIMITER //
CREATE TRIGGER before_book_update
BEFORE UPDATE ON books
FOR EACH ROW
BEGIN
    IF NEW.quantity_in_stock < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot update: Book quantity cannot be negative';
    END IF;
END//
DELIMITER ;

-- Trigger: Auto-place order when stock drops below threshold
DELIMITER //
CREATE TRIGGER after_book_stock_update
AFTER UPDATE ON books
FOR EACH ROW
BEGIN
    -- Check if quantity dropped from above threshold to below threshold
    IF OLD.quantity_in_stock >= OLD.threshold_quantity 
       AND NEW.quantity_in_stock < NEW.threshold_quantity THEN
        -- Place order with constant quantity (e.g., 50 books)
        INSERT INTO publisher_orders (isbn, quantity, status)
        VALUES (NEW.isbn, 50, 'Pending');
    END IF;
END//
DELIMITER ;

-- Insert Sample Admin User (password: admin123)
INSERT INTO users (username, password, first_name, last_name, email, user_type)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'Admin', 'User', 'admin@bookstore.com', 'Admin');

-- Insert Sample Publishers
INSERT INTO publishers (name, address, telephone) VALUES
('Penguin Random House', '1745 Broadway, New York, NY 10019', '+1-212-782-9000'),
('HarperCollins', '195 Broadway, New York, NY 10007', '+1-212-207-7000'),
('Simon & Schuster', '1230 Avenue of the Americas, New York, NY 10020', '+1-212-698-7000'),
('Macmillan Publishers', '120 Broadway, New York, NY 10271', '+1-646-307-5151');

-- Insert Sample Authors
INSERT INTO authors (name) VALUES
('Stephen Hawking'),
('Carl Sagan'),
('Yuval Noah Harari'),
('Leonardo da Vinci'),
('Karen Armstrong');

-- Insert Sample Books
INSERT INTO books (isbn, title, publisher_id, publication_year, selling_price, category, quantity_in_stock, threshold_quantity)
VALUES
('9780553380163', 'A Brief History of Time', 1, 1988, 18.99, 'Science', 45, 10),
('9780345539434', 'Cosmos', 1, 1980, 22.50, 'Science', 30, 10),
('9780062316097', 'Sapiens', 2, 2015, 24.99, 'History', 55, 15),
('9781501139154', 'The Art Book', 3, 2016, 35.00, 'Art', 20, 8),
('9780679783275', 'A History of God', 4, 1993, 19.99, 'Religion', 25, 10);

-- Link Books to Authors
INSERT INTO book_authors (isbn, author_id) VALUES
('9780553380163', 1),
('9780345539434', 2),
('9780062316097', 3),
('9781501139154', 4),
('9780679783275', 5);