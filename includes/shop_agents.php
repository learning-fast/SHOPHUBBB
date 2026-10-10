<?php

function shophub_table_exists(mysqli $conn, string $tableName): bool
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS table_count
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
    ");
    $stmt->bind_param("s", $tableName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int)($row['table_count'] ?? 0) > 0;
}

function shophub_fetch_products(mysqli $conn, string $sql, string $types = "", array $params = []): array
{
    $stmt = $conn->prepare($sql);

    if ($types !== "" && count($params) > 0) {
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    return $products;
}

function shophub_load_ai_model(): array
{
    $modelPath = __DIR__ . "/../data/shop_ai_model.json";

    if (!file_exists($modelPath)) {
        return [];
    }

    $model = json_decode((string)file_get_contents($modelPath), true);

    return is_array($model) ? $model : [];
}

function shophub_ai_tokens(string $text): array
{
    preg_match_all('/[a-z0-9]+/i', strtolower($text), $matches);
    $stopWords = ['the', 'and', 'for', 'with', 'show', 'please', 'product', 'products', 'want', 'need'];

    return array_values(array_filter($matches[0] ?? [], function ($word) use ($stopWords) {
        return strlen($word) > 1 && !in_array($word, $stopWords, true);
    }));
}

function shophub_ai_detect_intent(array $model, string $message): string
{
    if (empty($model['intents'])) {
        return "recommendation";
    }

    $tokens = shophub_ai_tokens($message);
    $bestIntent = "recommendation";
    $bestScore = null;

    foreach ($model['intents'] as $intent => $data) {
        $score = (float)($data['prior'] ?? 0);
        foreach ($tokens as $token) {
            if (isset($data['keywords'][$token])) {
                $score += (float)$data['keywords'][$token];
            }
        }

        if ($bestScore === null || $score > $bestScore) {
            $bestScore = $score;
            $bestIntent = $intent;
        }
    }

    return $bestIntent;
}

function shophub_ai_score_product(array $model, array $product, string $message = "", float $targetPrice = 0): float
{
    $tokens = shophub_ai_tokens($message);
    $haystack = strtolower(
        ($product['name'] ?? '') . ' ' .
        ($product['description'] ?? '') . ' ' .
        ($product['category_name'] ?? '')
    );
    $score = 0;

    foreach ($tokens as $token) {
        if (strpos($haystack, $token) !== false) {
            $score += 3;
        }
    }

    foreach (($model['shopping_terms'] ?? []) as $terms) {
        foreach ($terms as $term => $weight) {
            if (strpos($haystack, strtolower((string)$term)) !== false) {
                $score += (float)$weight;
            }
        }
    }

    if ($targetPrice > 0 && isset($product['price'])) {
        $distance = abs((float)$product['price'] - $targetPrice);
        $score += max(0, 3 - ($distance / max(1, $targetPrice)));
    }

    return $score;
}

function shophub_ai_sort_products(array $products, array $model, string $message = "", float $targetPrice = 0): array
{
    usort($products, function ($a, $b) use ($model, $message, $targetPrice) {
        $scoreA = shophub_ai_score_product($model, $a, $message, $targetPrice);
        $scoreB = shophub_ai_score_product($model, $b, $message, $targetPrice);

        if ($scoreA === $scoreB) {
            return ((int)$b['id']) <=> ((int)$a['id']);
        }

        return $scoreB <=> $scoreA;
    });

    return $products;
}

function shophub_behavior_scores(mysqli $conn, array $productIds): array
{
    $productIds = array_values(array_unique(array_map('intval', $productIds)));
    if (count($productIds) === 0) {
        return [];
    }

    $scores = array_fill_keys($productIds, 0.0);
    $placeholders = implode(",", array_fill(0, count($productIds), "?"));
    $types = str_repeat("i", count($productIds));

    if (shophub_table_exists($conn, "product_views")) {
        $rows = shophub_fetch_products($conn, "
            SELECT product_id AS id, COUNT(*) AS total
            FROM product_views
            WHERE product_id IN ($placeholders)
            GROUP BY product_id
        ", $types, $productIds);

        foreach ($rows as $row) {
            $scores[(int)$row['id']] += min(4, (int)$row['total'] * 0.12);
        }
    }

    if (shophub_table_exists($conn, "wishlist")) {
        $rows = shophub_fetch_products($conn, "
            SELECT product_id AS id, COUNT(*) AS total
            FROM wishlist
            WHERE product_id IN ($placeholders)
            GROUP BY product_id
        ", $types, $productIds);

        foreach ($rows as $row) {
            $scores[(int)$row['id']] += min(3, (int)$row['total'] * 0.5);
        }
    }

    if (shophub_table_exists($conn, "reviews")) {
        $rows = shophub_fetch_products($conn, "
            SELECT product_id AS id, AVG(rating) AS average_rating, COUNT(*) AS total
            FROM reviews
            WHERE product_id IN ($placeholders)
            GROUP BY product_id
        ", $types, $productIds);

        foreach ($rows as $row) {
            $scores[(int)$row['id']] += ((float)$row['average_rating'] * 0.7) + min(2, (int)$row['total'] * 0.25);
        }
    }

    return $scores;
}

function shophub_sort_by_behavior(mysqli $conn, array $products): array
{
    $scores = shophub_behavior_scores($conn, array_column($products, 'id'));
    if (count($scores) === 0) {
        return $products;
    }

    usort($products, function ($a, $b) use ($scores) {
        $scoreA = $scores[(int)$a['id']] ?? 0;
        $scoreB = $scores[(int)$b['id']] ?? 0;

        if ($scoreA === $scoreB) {
            return ((int)$b['id']) <=> ((int)$a['id']);
        }

        return $scoreB <=> $scoreA;
    });

    return $products;
}

function shophub_get_recommendations(mysqli $conn, array $options = []): array
{
    $limit = max(1, (int)($options['limit'] ?? 4));
    $excludeId = (int)($options['exclude_id'] ?? 0);
    $categoryId = (int)($options['category_id'] ?? 0);
    $userId = (int)($options['user_id'] ?? 0);
    $searchTerm = trim((string)($options['search'] ?? ""));
    $price = isset($options['price']) ? (float)$options['price'] : 0;
    $aiMessage = trim((string)($options['message'] ?? $searchTerm));
    $aiModel = shophub_load_ai_model();

    $baseSelect = "
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
    ";
    $hasPersonalHistory = $userId > 0
        && $categoryId === 0
        && $searchTerm === ""
        && $price <= 0
        && shophub_table_exists($conn, "order_items");

    $conditions = [];
    $types = "";
    $params = [];

    if ($excludeId > 0) {
        $conditions[] = "p.id <> ?";
        $types .= "i";
        $params[] = $excludeId;
    }

    if ($categoryId > 0) {
        $conditions[] = "p.category_id = ?";
        $types .= "i";
        $params[] = $categoryId;
    }

    if ($searchTerm !== "") {
        $conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
        $like = "%" . $searchTerm . "%";
        $types .= "sss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($price > 0) {
        $minPrice = max(0, $price * 0.65);
        $maxPrice = $price * 1.35;
        $conditions[] = "p.price BETWEEN ? AND ?";
        $types .= "dd";
        $params[] = $minPrice;
        $params[] = $maxPrice;
    }

    $products = [];

    if (!$hasPersonalHistory) {
        $sql = $baseSelect;
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY p.id DESC LIMIT ?";
        $types .= "i";
        $params[] = $limit;

        $products = shophub_fetch_products($conn, $sql, $types, $params);
    }

    if (count($products) < $limit && $userId > 0 && ($hasPersonalHistory || shophub_table_exists($conn, "order_items"))) {
        $remaining = $limit - count($products);
        $existingIds = array_map('intval', array_column($products, 'id'));
        if ($excludeId > 0) {
            $existingIds[] = $excludeId;
        }

        $historySql = "
            SELECT p.*, c.name AS category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.category_id IN (
                SELECT DISTINCT hp.category_id
                FROM orders o
                JOIN order_items oi ON oi.order_id = o.id
                JOIN products hp ON hp.id = oi.product_id
                WHERE o.user_id = ?
            )
        ";
        $historyTypes = "i";
        $historyParams = [$userId];

        if (count($existingIds) > 0) {
            $placeholders = implode(",", array_fill(0, count($existingIds), "?"));
            $historySql .= " AND p.id NOT IN ($placeholders)";
            $historyTypes .= str_repeat("i", count($existingIds));
            $historyParams = array_merge($historyParams, $existingIds);
        }

        $historySql .= " ORDER BY p.id DESC LIMIT ?";
        $historyTypes .= "i";
        $historyParams[] = $remaining;

        $products = array_merge(
            $products,
            shophub_fetch_products($conn, $historySql, $historyTypes, $historyParams)
        );
    }

    if (count($products) < $limit) {
        $remaining = $limit - count($products);
        $existingIds = array_map('intval', array_column($products, 'id'));
        if ($excludeId > 0) {
            $existingIds[] = $excludeId;
        }

        $fallbackSql = $baseSelect;
        $fallbackTypes = "";
        $fallbackParams = [];

        if (count($existingIds) > 0) {
            $placeholders = implode(",", array_fill(0, count($existingIds), "?"));
            $fallbackSql .= " WHERE p.id NOT IN ($placeholders)";
            $fallbackTypes .= str_repeat("i", count($existingIds));
            $fallbackParams = array_merge($fallbackParams, $existingIds);
        }

        $fallbackSql .= " ORDER BY p.id DESC LIMIT ?";
        $fallbackTypes .= "i";
        $fallbackParams[] = $remaining;

        $products = array_merge(
            $products,
            shophub_fetch_products($conn, $fallbackSql, $fallbackTypes, $fallbackParams)
        );
    }

    if (!empty($aiModel)) {
        $products = shophub_ai_sort_products($products, $aiModel, $aiMessage, $price);
    }

    $products = shophub_sort_by_behavior($conn, $products);

    return array_slice($products, 0, $limit);
}

function shophub_render_product_cards(array $products, string $basePath = ""): void
{
    foreach ($products as $product): ?>
        <div class="product-card">
            <div class="product-image-wrapper">
                <img src="<?= htmlspecialchars($basePath . $product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
            <div class="product-info">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['category_name']) ?></p>
                <p class="price">Rs. <?= number_format((float)$product['price'], 2) ?></p>
                <a class="btn-primary" href="<?= htmlspecialchars($basePath) ?>pages/product.php?id=<?= (int)$product['id'] ?>">View Details</a>
            </div>
        </div>
    <?php endforeach;
}

function shophub_chatbot_reply(mysqli $conn, string $message, int $userId = 0): array
{
    $aiModel = shophub_load_ai_model();
    $intent = shophub_ai_detect_intent($aiModel, $message);
    $text = strtolower(trim($message));
    $search = "";
    $categoryId = 0;
    $maxPrice = 0;

    if (preg_match('/(?:under|below|less than|upto|up to)\s*(?:rs\.?|npr)?\s*([0-9]+)/i', $message, $match)) {
        $maxPrice = (float)$match[1];
    }

    $categories = shophub_fetch_products($conn, "SELECT id, name FROM categories ORDER BY name ASC");
    foreach ($categories as $category) {
        if (strpos($text, strtolower($category['name'])) !== false) {
            $categoryId = (int)$category['id'];
            $search = $category['name'];
            break;
        }
    }

    if ($search === "") {
        $clean = preg_replace('/[^a-z0-9 ]/i', ' ', $text);
        $stopWords = ['show', 'me', 'find', 'recommend', 'suggest', 'product', 'products', 'please', 'want', 'need', 'buy', 'under', 'below', 'less', 'than', 'upto', 'up', 'to', 'rs', 'npr'];
        $words = array_values(array_filter(explode(' ', $clean), function ($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords, true) && !is_numeric($word);
        }));
        $search = implode(' ', array_slice($words, 0, 3));
    }

    $sql = "
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
    ";
    $conditions = [];
    $types = "";
    $params = [];

    if ($categoryId > 0) {
        $conditions[] = "p.category_id = ?";
        $types .= "i";
        $params[] = $categoryId;
    } elseif ($search !== "") {
        $conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
        $like = "%" . $search . "%";
        $types .= "sss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($maxPrice > 0) {
        $conditions[] = "p.price <= ?";
        $types .= "d";
        $params[] = $maxPrice;
    }

    $supportOnlyIntent = in_array($intent, ['order_help', 'payment_help', 'greeting'], true)
        && $search === ""
        && $maxPrice <= 0;

    $products = [];

    if (!$supportOnlyIntent) {
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY p.id DESC LIMIT 3";
        $products = shophub_fetch_products($conn, $sql, $types, $params);
    }

    if (!empty($aiModel)) {
        $products = shophub_ai_sort_products($products, $aiModel, $message, $maxPrice);
    }

    if (!$supportOnlyIntent && count($products) === 0) {
        $products = shophub_get_recommendations($conn, [
            'limit' => 3,
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    if ($intent === 'order_help' || strpos($text, 'order') !== false || strpos($text, 'delivery') !== false || strpos($text, 'shipping') !== false) {
        $reply = $aiModel['reply_templates']['order_help'] ?? "You can place an order from any product detail page. Standard delivery is available, and express delivery adds Rs. 50 where offered.";
    } elseif ($intent === 'payment_help' || strpos($text, 'payment') !== false || strpos($text, 'cash') !== false || strpos($text, 'cod') !== false) {
        $reply = $aiModel['reply_templates']['payment_help'] ?? "ShopHub supports Cash on Delivery. Some checkout screens also show online payment as a demo option.";
    } elseif ($intent === 'greeting') {
        $reply = $aiModel['reply_templates']['greeting'] ?? "Hi, I am your ShopHub AI assistant. Tell me what you want to buy.";
    } elseif ($intent === 'budget') {
        $reply = $aiModel['reply_templates']['budget'] ?? "I found budget friendly products that fit your price request.";
    } elseif (count($products) > 0) {
        $reply = $aiModel['reply_templates']['recommendation'] ?? "Here are smart picks from ShopHub based on your message:";
    } else {
        $reply = "I could not find matching products yet. Try asking for a category, product name, or budget like \"kurti under 2000\".";
    }

    return [
        'reply' => $reply,
        'intent' => $intent,
        'products' => array_map(function ($product) {
            return [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'category' => $product['category_name'],
                'price' => (float)$product['price'],
                'image' => $product['image'],
                'url' => '/SHOPHUBBB/pages/product.php?id=' . (int)$product['id'],
            ];
        }, $products),
    ];
}
