<?php

function shophub_ensure_feature_tables(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wishlist_item (user_id, product_id)
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            rating TINYINT NOT NULL,
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_product_review (product_id, user_id)
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS product_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            user_id INT NULL,
            session_id VARCHAR(128) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS chat_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            message TEXT NOT NULL,
            reply TEXT NOT NULL,
            intent VARCHAR(80) NULL,
            suggested_product_ids VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

function shophub_log_product_view(mysqli $conn, int $productId, int $userId = 0): void
{
    $sessionId = session_id();
    $nullableUserId = $userId > 0 ? $userId : null;
    $stmt = $conn->prepare("INSERT INTO product_views (product_id, user_id, session_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $productId, $nullableUserId, $sessionId);
    $stmt->execute();
}

function shophub_is_wishlisted(mysqli $conn, int $userId, int $productId): bool
{
    if ($userId <= 0) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();

    return (bool)$stmt->get_result()->fetch_assoc();
}

function shophub_toggle_wishlist(mysqli $conn, int $userId, int $productId): bool
{
    if (shophub_is_wishlisted($conn, $userId, $productId)) {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();

        return false;
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();

    return true;
}

function shophub_save_review(mysqli $conn, int $productId, int $userId, int $rating, string $comment): void
{
    $rating = max(1, min(5, $rating));
    $stmt = $conn->prepare("
        INSERT INTO reviews (product_id, user_id, rating, comment)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param("iiis", $productId, $userId, $rating, $comment);
    $stmt->execute();
}

function shophub_get_review_summary(mysqli $conn, int $productId): array
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total, AVG(rating) AS average_rating FROM reviews WHERE product_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];

    return [
        'total' => (int)($row['total'] ?? 0),
        'average' => round((float)($row['average_rating'] ?? 0), 1),
    ];
}

function shophub_get_reviews(mysqli $conn, int $productId, int $limit = 5): array
{
    $stmt = $conn->prepare("
        SELECT r.*, u.name AS user_name
        FROM reviews r
        JOIN users u ON u.id = r.user_id
        WHERE r.product_id = ?
        ORDER BY r.id DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $productId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = [];

    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    return $reviews;
}

function shophub_log_chat(mysqli $conn, int $userId, string $message, array $response): void
{
    $reply = (string)($response['reply'] ?? '');
    $intent = (string)($response['intent'] ?? '');
    $productIds = implode(',', array_map(function ($product) {
        return (int)$product['id'];
    }, $response['products'] ?? []));
    $nullableUserId = $userId > 0 ? $userId : null;

    $stmt = $conn->prepare("
        INSERT INTO chat_logs (user_id, message, reply, intent, suggested_product_ids)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $nullableUserId, $message, $reply, $intent, $productIds);
    $stmt->execute();
}
