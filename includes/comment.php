<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $comment = trim($_POST['comment']);
    
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $comment]);
        
        // Obter informações do usuário para retornar
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'userName' => $user['full_name'],
            'comment' => $comment
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Comentário vazio']);
    }
}
?>