-- Query to find bilal user and their branch assignments
SELECT id, email, name FROM users WHERE email LIKE '%hebesha%' LIMIT 5;

-- Get branch assignments for the user
SELECT ru.user_id, ru.role_id, rb.branch_id, b.name as branch_name
FROM role_user ru
LEFT JOIN role_branch rb ON ru.role_id = rb.role_id
LEFT JOIN branches b ON rb.branch_id = b.id
WHERE ru.user_id = (SELECT id FROM users WHERE email = 'hebesha@gmail.com' LIMIT 1);

-- Check manufacturing items in products table
SELECT id, name, category_id, metadata FROM products WHERE name LIKE '%beherzaf%' OR name LIKE '%tukur%' LIMIT 10;
