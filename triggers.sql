-- ---------------------------------------------------------
-- 1. AUTOMATIC REPLENISHMENT TRIGGER (Requirement 3)
-- Description: Places an order to the publisher when stock 
-- drops below the threshold.
-- ---------------------------------------------------------
DELIMITER //

CREATE TRIGGER auto_place_order_trigger
AFTER UPDATE ON books FOR EACH ROW
BEGIN
    -- Condition: Stock falls from above threshold to below threshold
    IF NEW.quantity_in_stock < NEW.threshold_quantity 
       AND OLD.quantity_in_stock >= NEW.threshold_quantity THEN
        
        -- Requirement 3b: Insert a fixed quantity order (e.g., 50 copies)
        INSERT INTO publisher_orders (isbn, quantity, status, order_date)
        VALUES (NEW.isbn, 50, 'Pending', NOW());
        
    END IF;
END //

DELIMITER ;


-- ---------------------------------------------------------
-- 2. INVENTORY UPDATE TRIGGER
-- Description: Automatically decreases book stock quantity 
-- after a customer completes a purchase.
-- ---------------------------------------------------------
DELIMITER //

CREATE TRIGGER decrease_stock_after_sale
AFTER INSERT ON order_items FOR EACH ROW
BEGIN
    UPDATE books 
    SET quantity_in_stock = quantity_in_stock - NEW.quantity
    WHERE isbn = NEW.isbn;
END //

DELIMITER ;


-- ---------------------------------------------------------
-- 3. STOCK VALIDATION TRIGGER (Integrity Check)
-- Description: Prevents sales if the requested quantity 
-- exceeds the current stock level.
-- ---------------------------------------------------------
DELIMITER //

CREATE TRIGGER check_stock_before_insert
BEFORE INSERT ON order_items FOR EACH ROW
BEGIN
    DECLARE available_qty INT;
    
    -- Get current quantity for the specific book
    SELECT quantity_in_stock INTO available_qty 
    FROM books 
    WHERE isbn = NEW.isbn;
    
    -- Check if stock is sufficient
    IF NEW.quantity > available_qty THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Transaction Error: Insufficient stock available';
    END IF;
END //

DELIMITER ;