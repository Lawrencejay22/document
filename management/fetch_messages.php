<?php
session_start();
require_once '../PHP/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'] ?? 0;

if (!$receiver_id) {
    echo json_encode(['status' => 'error', 'error' => 'No receiver specified']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message_text, sent_at 
        FROM messages 
        WHERE (sender_id = :user_id AND receiver_id = :receiver_id) 
           OR (sender_id = :receiver_id AND receiver_id = :user_id)
        ORDER BY sent_at ASC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':receiver_id', $receiver_id);
    $stmt->execute();
    $raw_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $messages = [];
    foreach ($raw_messages as $msg) {
        $msg['time'] = date('M d, H:i', strtotime($msg['sent_at']));
        $msg['is_sent'] = ($msg['sender_id'] == $user_id);
        $messages[] = $msg;
    }

    echo json_encode(['status' => 'success', 'messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}
