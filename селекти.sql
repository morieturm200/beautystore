use beautystore;
-- 1. Повна картка продукту з консолідованими даними
SELECT p.product_id, p.name AS 'Товар', p.price AS 'Ціна', cat.name AS 'Категорія', 
       p.manufacturer AS 'Виробник', p.stock AS 'Залишок',
       GROUP_CONCAT(CONCAT(c.characteristic_name, ': ', c.characteristic_value) SEPARATOR '; ') AS 'Характеристики'
FROM product p
LEFT JOIN categories cat ON p.category_id = cat.category_id
LEFT JOIN characteristics c ON p.product_id = c.product_id 
GROUP BY p.product_id;

-- 2.Сегментація товарів за категоріями
SELECT p.product_id, p.name, p.price, p.manufacturer, cat.name AS category_name, p.stock 
FROM product p
JOIN categories cat ON p.category_id = cat.category_id
WHERE cat.name = 'Макіяж' ORDER BY p.price DESC;

-- 3. Ідентифікація лідерів продажу (Top-5 Best Sellers)
SELECT p.product_id, p.name, SUM(od.quantity) AS total_sold
FROM product p 
JOIN Order_Details od ON p.product_id = od.product_id 
WHERE od.status = 'ordered'
GROUP BY p.product_id, p.name ORDER BY total_sold DESC LIMIT 5;

-- 4. Аналіз історії покупок та життєвого циклу клієнта (LTV)
SELECT u.user_id, u.first_name, p.name AS product_name, od.quantity, od.unit_price,
       SUM(od.quantity * od.unit_price) OVER (PARTITION BY u.user_id) AS total_spent_all_time
FROM users u
JOIN orders o ON u.user_id = o.user_id
JOIN Order_Details od ON o.order_id = od.order_id
JOIN product p ON od.product_id = p.product_id
WHERE u.user_id = 1 AND od.status = 'ordered';

-- 5. Ранжування категорій за популярністю
SELECT 
    cat.name AS category_name, 
    SUM(od.quantity) AS total_quantity_sold
FROM product p
JOIN Order_Details od ON p.product_id = od.product_id
JOIN categories cat ON p.category_id = cat.category_id
WHERE od.status = 'ordered'
GROUP BY cat.category_id, cat.name
ORDER BY total_quantity_sold DESC;

-- 6. Виявлення лояльної аудиторії (Retention Rate)
SELECT u.user_id, u.first_name, u.email, COUNT(DISTINCT o.order_id) AS order_count
FROM users u
JOIN orders o ON u.user_id = o.user_id
GROUP BY u.user_id HAVING order_count > 1;

-- 7. Найкраще продаваний продукт від кожного виробника
WITH RankedProducts AS (
    SELECT p.manufacturer, p.name, SUM(od.quantity) AS total_sold,
           ROW_NUMBER() OVER (PARTITION BY p.manufacturer ORDER BY SUM(od.quantity) DESC) AS rn
    FROM product p
    JOIN Order_Details od ON p.product_id = od.product_id
    WHERE od.status = 'ordered'
    GROUP BY p.manufacturer, p.product_id, p.name
)
SELECT manufacturer, name, total_sold FROM RankedProducts WHERE rn = 1;

-- 8. Клієнти, які витратили понад вказану суму
SELECT u.first_name, u.email, SUM(od.quantity * od.unit_price) AS total_spent
FROM users u
JOIN orders o ON u.user_id = o.user_id
JOIN Order_Details od ON o.order_id = od.order_id
WHERE od.status = 'ordered'
GROUP BY u.user_id HAVING total_spent > 1000;

-- 9. Аналіз неліквідних товарів (Dead Stock)
SELECT p.name, p.manufacturer, p.stock
FROM product p
LEFT JOIN Order_Details od ON p.product_id = od.product_id AND od.status = 'ordered'
WHERE od.product_id IS NULL;

-- 10. Моніторинг дефіциту складських запасів
SELECT name, stock FROM product WHERE stock < 15 ORDER BY stock ASC;

-- 11. Динаміка замовлень за статусами
SELECT status, COUNT(*) AS count_orders
FROM orders GROUP BY status;

-- 12.	 Ефективність системи персоналізації (Insights) 
SELECT p.name, p.stock, COUNT(w.wishlist_id) AS interested_users
FROM product p
JOIN Wishlist w ON p.product_id = w.product_id
WHERE p.stock < 5 GROUP BY p.product_id ORDER BY interested_users DESC;

-- 13. Середній чек клієнта
SELECT AVG(order_sum) AS average_order_value
FROM (SELECT order_id, SUM(quantity * unit_price) AS order_sum 
      FROM Order_Details WHERE status = 'ordered' GROUP BY order_id) AS subquery;
      
-- 14. Найактивніші автори відгуків
SELECT u.first_name, COUNT(r.review_id) AS reviews_written
FROM users u
JOIN Reviews r ON u.user_id = r.user_id
GROUP BY u.user_id ORDER BY reviews_written DESC;

-- 15. Популярність брендів у Wishlist
SELECT p.manufacturer, COUNT(w.wishlist_id) AS times_wishlisted
FROM product p
JOIN Wishlist w ON p.product_id = w.product_id
GROUP BY p.manufacturer ORDER BY times_wishlisted DESC;
