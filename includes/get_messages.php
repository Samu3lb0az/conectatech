<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'];
$last_message_id = isset($_GET['last_message_id']) ? $_GET['last_message_id'] : 0;

// Aguardar até 30 segundos por novas mensagens
$timeout = 30;
$start_time = time();

while (time() - $start_time < $timeout) {
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.full_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) 
        AND m.id > ? 
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id, $last_message_id]);
    $messages = $stmt->fetchAll();
    
    if (!empty($messages)) {
        echo json_encode(['messages' => $messages]);
        exit;
    }
    
    // Aguardar 1 segundo antes de verificar novamente
    sleep(1);
}

// Retornar vazio se não houver novas mensagens
echo json_encode(['messages' => []]);
?>