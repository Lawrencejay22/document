<?php
session_start();
require_once '../PHP/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if (!$receiver_id || empty($message)) {
    echo json_encode(['status' => 'error', 'error' => 'Invalid data']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (:sender_id, :receiver_id, :message_text)");
    $stmt->bindParam(':sender_id', $user_id);
    $stmt->bindParam(':receiver_id', $receiver_id);
    $stmt->bindParam(':message_text', $message);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}
