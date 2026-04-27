use beautystore;
-- 1. Повна інформація про кожен продукт
SELECT 
    p.product_id, 
    p.name AS 'Товар', 
    p.price AS 'Ціна', 
    cat.name AS 'Категорія', 
    p.manufacturer AS 'Виробник', 
    p.stock AS 'Залишок',
    GROUP_CONCAT(CONCAT(c.characteristic_name, ': ', c.characteristic_value) SEPARATOR '; ') AS 'Характеристики'
FROM product p
LEFT JOIN categories cat ON p.category_id = cat.category_id
LEFT JOIN characteristics c ON p.product_id = c.product_id 
GROUP BY p.product_id;

-- 2. Усі продукти з обраної категорії

SELECT 
    p.product_id, 
    p.name, 
    p.price, 
    p.manufacturer, 
    cat.name AS category_name, 
    p.description, 
    p.stock 
FROM product p
JOIN categories cat ON p.category_id = cat.category_id
WHERE cat.name = 'Макіяж'
ORDER BY p.price DESC;

-- 3. Топ-5 проданих продуктів

SELECT 
    p.product_id, 
    p.name, 
    cat.name AS category_name, 
    p.manufacturer, 
    SUM(od.quantity) AS total_quantity_sold
FROM product p 
JOIN Order_Details od ON p.product_id = od.product_id 
LEFT JOIN categories cat ON p.category_id = cat.category_id
WHERE od.status = 'ordered'
GROUP BY p.product_id, p.name, category_name, p.manufacturer
ORDER BY total_quantity_sold DESC 
LIMIT 5;

-- 4. Усі продукти, куплені обраним клієнтом

SELECT 
    u.user_id, 
    u.first_name, 
    u.last_name, 
    p.product_id, 
    p.name AS product_name, 
    od.quantity, 
    od.unit_price, 
    (od.quantity * od.unit_price) AS total_per_product,
    SUM(od.quantity * od.unit_price) OVER (PARTITION BY u.user_id) AS total_spent
FROM users u
JOIN orders o ON u.user_id = o.user_id
JOIN Order_Details od ON o.order_id = od.order_id
JOIN product p ON od.product_id = p.product_id
WHERE u.user_id = 1 AND od.status = 'ordered';

-- 5. Топ категорії за продажами

SELECT 
    cat.name AS category_name, 
    SUM(od.quantity) AS total_quantity_sold
FROM product p
JOIN Order_Details od ON p.product_id = od.product_id
JOIN categories cat ON p.category_id = cat.category_id
WHERE od.status = 'ordered'
GROUP BY cat.category_id, cat.name
ORDER BY total_quantity_sold DESC;

-- 6. Клієнти з кількістю замовлень, більше вказаної

SELECT 
    u.user_id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    COUNT(DISTINCT o.order_id) AS order_count
FROM users u
JOIN orders o ON u.user_id = o.user_id
GROUP BY u.user_id, u.first_name, u.last_name, u.email
HAVING order_count > 1;

-- 7. Найкраще продаваний продукт від кожного виробника

WITH RankedProducts AS (
    SELECT 
        p.manufacturer, 
        p.product_id, 
        p.name, 
        SUM(od.quantity) AS total_quantity_sold,
        ROW_NUMBER() OVER (PARTITION BY p.manufacturer ORDER BY SUM(od.quantity) DESC) AS rn
    FROM product p
    JOIN Order_Details od ON p.product_id = od.product_id
    WHERE od.status = 'ordered'
    GROUP BY p.manufacturer, p.product_id, p.name
)
SELECT 
    manufacturer, 
    product_id, 
    name, 
    total_quantity_sold
FROM RankedProducts
WHERE rn = 1;

-- 8. Клієнти, які витратили понад вказану суму

SELECT 
    u.user_id, 
    u.first_name, 
    u.last_name, 
    u.email, 
    SUM(od.quantity * od.unit_price) AS total_spent
FROM users u
JOIN orders o ON u.user_id = o.user_id
JOIN Order_Details od ON o.order_id = od.order_id
WHERE od.status = 'ordered'
GROUP BY u.user_id, u.first_name, u.last_name, u.email
HAVING total_spent > 1000;

-- 9. Продукти, які ніколи не купувались

SELECT 
    p.product_id, 
    p.name, 
    cat.name AS category_name, 
    p.manufacturer,
    p.stock
FROM product p
LEFT JOIN categories cat ON p.category_id = cat.category_id
LEFT JOIN Order_Details od ON p.product_id = od.product_id AND od.status = 'ordered'
WHERE od.product_id IS NULL;

-- 10. Продукти з запасом менше 15 одиниць

SELECT 
    product_id, 
    name, 
    stock
FROM product
WHERE stock < 15
ORDER BY stock ASC;