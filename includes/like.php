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
    
    // Verificar se já curtiu
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $already_liked = $stmt->fetch();
    
    if ($already_liked) {
        // Remover like
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $liked = false;
    } else {
        // Adicionar like
        $stmt = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
        $liked = true;
    }
    
    // Obter nova contagem de likes
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $like_count = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likeCount' => $like_count
    ]);
}
?>