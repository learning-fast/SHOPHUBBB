INSERT INTO products (category_id, name, price, image, description)
SELECT 1, 'Oversized Cotton T-Shirt', 899.00, 'uploads/1769358226_tshirt.png',
       'Soft oversized cotton t-shirt for everyday casual wear. Lightweight fabric, relaxed fit, and easy to style with jeans or trousers.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Oversized Cotton T-Shirt');

INSERT INTO products (category_id, name, price, image, description)
SELECT 1, 'Printed Summer Kurti', 1450.00, 'uploads/1769358418_kurthi.jpg',
       'Comfortable printed kurti designed for daily wear and college outfits. The breathable fabric keeps it suitable for warm weather.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Printed Summer Kurti');

INSERT INTO products (category_id, name, price, image, description)
SELECT 1, 'Casual Denim Jacket', 2450.00, 'uploads/1771988897_Screenshot_2026-02-25_085303.png',
       'Classic denim jacket with a structured fit. A versatile layering piece for casual and semi-casual looks.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Casual Denim Jacket');

INSERT INTO products (category_id, name, price, image, description)
SELECT 1, 'High Waist Wide Leg Pants', 1850.00, 'uploads/1771989189_Screenshot_2026-02-25_085400.png',
       'High waist wide leg pants with a clean silhouette. Ideal for office, college, and smart casual styling.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'High Waist Wide Leg Pants');

INSERT INTO products (category_id, name, price, image, description)
SELECT 2, 'Aloe Vera Hydrating Gel', 550.00, 'uploads/1769271634_aleoveraa.jpg',
       'Cooling aloe vera gel for daily skin hydration. Helps soothe dry skin and leaves a fresh non-sticky feel.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Aloe Vera Hydrating Gel');

INSERT INTO products (category_id, name, price, image, description)
SELECT 2, 'Acne Shield Face Cream', 750.00, 'uploads/1769358136_acnesheild.jpg',
       'Lightweight face cream made for acne-prone skin. Helps control excess oil while keeping skin moisturized.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Acne Shield Face Cream');

INSERT INTO products (category_id, name, price, image, description)
SELECT 2, 'Vitamin C Brightening Serum', 950.00, 'uploads/1769272059_1000_F_408978716_eP0xkfvcvLcjQpvyR4D3kwIQq6fx8SYd.jpg',
       'Daily brightening serum with a smooth finish. Supports an even-looking skin tone and adds a healthy glow.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Vitamin C Brightening Serum');

INSERT INTO products (category_id, name, price, image, description)
SELECT 2, 'Gentle Foaming Face Wash', 420.00, 'uploads/1770026400_Screenshot 2026-02-02 154320.png',
       'Gentle foaming cleanser for daily use. Removes dirt and oil without making the skin feel overly dry.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Gentle Foaming Face Wash');

INSERT INTO products (category_id, name, price, image, description)
SELECT 3, 'Everyday Canvas Tote Bag', 690.00, 'uploads/1774874506_Screenshot_2026-03-30_181016.png',
       'Durable canvas tote bag with enough space for books, daily essentials, and shopping items.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Everyday Canvas Tote Bag');

INSERT INTO products (category_id, name, price, image, description)
SELECT 3, 'Butterfly Pendant Necklace', 380.00, 'uploads/1774874448_Screenshot_2026-03-30_181227.png',
       'Minimal butterfly pendant necklace for simple everyday styling. Lightweight and easy to pair with casual outfits.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Butterfly Pendant Necklace');

INSERT INTO products (category_id, name, price, image, description)
SELECT 3, 'Classic Analog Wrist Watch', 1199.00, 'uploads/1774874305_Screenshot_2026-03-30_181604.png',
       'Minimal analog wrist watch with a clean dial. Suitable for daily wear, office looks, and gifting.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Classic Analog Wrist Watch');

INSERT INTO products (category_id, name, price, image, description)
SELECT 3, 'Aesthetic Ring Set', 450.00, 'uploads/1774874140_Screenshot_2026-03-30_181726.png',
       'Set of stylish rings for mixing and matching. A simple accessory pack for casual and party outfits.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Aesthetic Ring Set');
