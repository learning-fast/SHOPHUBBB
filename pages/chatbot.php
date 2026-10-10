<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/shop_agents.php";
require_once __DIR__ . "/../includes/shop_features.php";

shophub_ensure_feature_tables($conn);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
    $payload = $_POST;
}

$message = trim((string)($payload['message'] ?? ""));

if ($message === "") {
    http_response_code(422);
    echo json_encode(['error' => 'Please enter a message.']);
    exit;
}

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $response = shophub_chatbot_reply($conn, $message, $userId);
    shophub_log_chat($conn, $userId, $message, $response);
    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'reply' => 'The assistant is having trouble right now. Please try again in a moment.',
        'products' => [],
    ]);
}
