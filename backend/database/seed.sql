INSERT INTO categories (id, parent_id, name, slug) VALUES
    (1, NULL, 'Cuidados pessoais', 'cuidados-pessoais'),
    (2, NULL, 'Digitais', 'digitais'),
    (3, 1, 'Rotina da manha', 'rotina-da-manha');

SELECT setval('categories_id_seq', (SELECT max(id) FROM categories));

INSERT INTO collections (id, name, slug, description) VALUES
    (1, 'Lancamento Primavera', 'lancamento-primavera', 'Produtos com alta margem para campanha DTC.'),
    (2, 'Bundles com guia digital', 'bundles-guia-digital', 'Combos fisicos e digitais para aumentar AOV.');

SELECT setval('collections_id_seq', (SELECT max(id) FROM collections));

INSERT INTO warehouses (id, name, code, city) VALUES
    (1, 'CD Sao Paulo', 'SP01', 'Sao Paulo'),
    (2, 'CD Recife', 'PE01', 'Recife');

SELECT setval('warehouses_id_seq', (SELECT max(id) FROM warehouses));

INSERT INTO products (
    id, title, slug, description, product_type, visibility, price, compare_at_price,
    margin_percent, category_id, collection_id, custom_fields
) VALUES
    (
        1,
        'Kit Glow Essencial',
        'kit-glow-essencial',
        'Bundle de cuidados pessoais com necessaire e guia digital de rotina.',
        'bundle',
        'published',
        189.90,
        229.90,
        58.00,
        3,
        2,
        '{"materiais": "algodao organico, vidro reciclavel", "dimensoes": "24x18x8cm"}'
    ),
    (
        2,
        'Guia Digital Pele Radiante',
        'guia-digital-pele-radiante',
        'E-book com plano de 21 dias para rotina de cuidados.',
        'digital',
        'published',
        49.90,
        NULL,
        87.00,
        2,
        2,
        '{"formato": "PDF", "paginas": 64}'
    ),
    (
        3,
        'Serum Botanico',
        'serum-botanico',
        'Serum fisico com variantes por aroma.',
        'physical',
        'draft',
        119.90,
        149.90,
        51.00,
        1,
        1,
        '{"volume": "30ml", "ingredientes": "niacinamida, cha verde"}'
    );

SELECT setval('products_id_seq', (SELECT max(id) FROM products));

INSERT INTO product_media (product_id, media_type, url, title, sort_order) VALUES
    (1, 'image', 'https://images.unsplash.com/photo-1556228578-8c89e6adf883', 'Kit em bancada clara', 1),
    (1, 'video', 'https://example.com/videos/kit-glow-demo.mp4', 'Demo do kit', 2),
    (1, 'document', 'https://example.com/docs/manual-kit-glow.pdf', 'Manual de uso', 3),
    (2, 'document', 'https://example.com/docs/guia-pele-radiante.pdf', 'E-book', 1),
    (3, 'image', 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be', 'Serum botanico', 1);

INSERT INTO product_variants (
    id, product_id, sku, option_name, option_value, price, stock, low_stock_threshold, is_digital
) VALUES
    (1, 1, 'KIT-GLOW-P', 'Tamanho', 'Pequeno', 189.90, 18, 6, false),
    (2, 1, 'KIT-GLOW-G', 'Tamanho', 'Grande', 219.90, 7, 8, false),
    (3, 2, 'GUIA-PELE-PDF', 'Formato', 'PDF', 49.90, 9999, 0, true),
    (4, 3, 'SERUM-LAV', 'Aroma', 'Lavanda', 119.90, 4, 10, false),
    (5, 3, 'SERUM-CIT', 'Aroma', 'Citrico', 119.90, 12, 10, false);

SELECT setval('product_variants_id_seq', (SELECT max(id) FROM product_variants));

INSERT INTO inventory_levels (variant_id, warehouse_id, quantity) VALUES
    (1, 1, 12),
    (1, 2, 6),
    (2, 1, 5),
    (2, 2, 2),
    (3, 1, 9999),
    (4, 1, 4),
    (5, 1, 10),
    (5, 2, 2);

INSERT INTO inventory_movements (variant_id, warehouse_id, delta_quantity, reason, created_at) VALUES
    (1, 1, 20, 'initial_stock', now() - interval '9 days'),
    (1, 1, -8, 'orders', now() - interval '2 days'),
    (2, 1, 10, 'initial_stock', now() - interval '9 days'),
    (2, 1, -5, 'orders', now() - interval '1 day'),
    (4, 1, 4, 'initial_stock', now() - interval '3 days');

INSERT INTO customers (id, email, name, birthdate, ltv) VALUES
    (1, 'bruna@example.com', 'Bruna Alves', '1991-04-22', 409.80),
    (2, 'carol@example.com', 'Carol Lima', '1988-11-03', 239.80);

SELECT setval('customers_id_seq', (SELECT max(id) FROM customers));

INSERT INTO coupons (code, discount_type, amount, active, starts_at, ends_at) VALUES
    ('WELCOME10', 'percentage', 10.00, true, now() - interval '30 days', now() + interval '90 days'),
    ('FRETEGRATIS', 'free_shipping', 0.00, true, now() - interval '30 days', now() + interval '90 days'),
    ('DTC25', 'fixed', 25.00, true, now() - interval '7 days', now() + interval '30 days');

INSERT INTO carts (id, email, status, coupon_code, recovery_token, utm_source, created_at, updated_at) VALUES
    (1, 'marina@example.com', 'abandoned', 'WELCOME10', 'rec_kit_glow_001', 'instagram', now() - interval '3 hours', now() - interval '2 hours'),
    (2, 'patricia@example.com', 'active', NULL, 'rec_kit_glow_002', 'google', now() - interval '30 minutes', now() - interval '20 minutes'),
    (3, 'renata@example.com', 'abandoned', 'FRETEGRATIS', 'rec_serum_003', 'newsletter', now() - interval '8 hours', now() - interval '6 hours');

SELECT setval('carts_id_seq', (SELECT max(id) FROM carts));

INSERT INTO cart_items (cart_id, variant_id, quantity, unit_price) VALUES
    (1, 1, 1, 189.90),
    (1, 3, 1, 49.90),
    (2, 2, 1, 219.90),
    (3, 4, 2, 119.90);

INSERT INTO orders (
    id, customer_id, email, customer_name, status, payment_method, shipping_method,
    coupon_code, subtotal, discount_total, shipping_total, total, utm_source, metadata, created_at
) VALUES
    (1, 1, 'bruna@example.com', 'Bruna Alves', 'delivered', 'pix', 'standard', 'WELCOME10', 239.80, 23.98, 18.90, 234.72, 'instagram', '{"cross_sell_ids": [3]}', now() - interval '4 days'),
    (2, 2, 'carol@example.com', 'Carol Lima', 'processing', 'credit_card', 'express', NULL, 219.90, 0.00, 29.90, 249.80, 'google', '{"upsell_ids": [2]}', now() - interval '1 day'),
    (3, 1, 'bruna@example.com', 'Bruna Alves', 'received', 'boleto', 'standard', 'DTC25', 189.90, 25.00, 18.90, 183.80, 'newsletter', '{}', now());

SELECT setval('orders_id_seq', (SELECT max(id) FROM orders));

INSERT INTO order_items (order_id, variant_id, product_title, sku, quantity, unit_price, total) VALUES
    (1, 1, 'Kit Glow Essencial', 'KIT-GLOW-P', 1, 189.90, 189.90),
    (1, 3, 'Guia Digital Pele Radiante', 'GUIA-PELE-PDF', 1, 49.90, 49.90),
    (2, 2, 'Kit Glow Essencial', 'KIT-GLOW-G', 1, 219.90, 219.90),
    (3, 1, 'Kit Glow Essencial', 'KIT-GLOW-P', 1, 189.90, 189.90);

INSERT INTO email_events (cart_id, email, event_type, sent_at, payload) VALUES
    (1, 'marina@example.com', 'abandoned_cart_preview', now() - interval '90 minutes', '{"provider": "simulated"}');

INSERT INTO traffic_events (source, medium, campaign, visits, carts, checkouts, revenue, event_date) VALUES
    ('instagram', 'paid_social', 'primavera-dtc', 480, 72, 18, 1820.40, current_date - 5),
    ('google', 'cpc', 'skin-care-search', 310, 36, 11, 1210.20, current_date - 4),
    ('newsletter', 'email', 'welcome-flow', 150, 42, 14, 1555.10, current_date - 3),
    ('instagram', 'paid_social', 'primavera-dtc', 530, 80, 22, 2270.00, current_date - 2),
    ('direct', 'none', NULL, 190, 21, 7, 744.60, current_date - 1),
    ('google', 'cpc', 'skin-care-search', 225, 28, 8, 879.60, current_date);

