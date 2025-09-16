<?php
function getPosts($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.profile_picture, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as liked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function likePost($pdo, $user_id, $post_id) {
    // Verificar se já curtiu
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    if ($stmt->fetch()) {
        // Já curtiu, então remove o like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
    } else {
        // Não curtiu, então adiciona o like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
    }
}

function getComments($pdo, $post_id) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

function addComment($pdo, $user_id, $post_id, $comment) {
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $post_id, $comment]);
}
?>