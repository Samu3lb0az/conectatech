<?php
function getFeedPosts($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.full_name, u.profile_picture 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ? OR p.user_id IN (
            SELECT following_id FROM followers WHERE follower_id = ?
        ) 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    return $stmt->fetchAll();
}

function isPostLiked($user_id, $post_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    return $stmt->fetch() ? true : false;
}

function getLikeCount($post_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetch()['count'];
}

function getPostComments($post_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT c.*, u.username, u.full_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Agora mesmo';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' min atrás';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' h atrás';
    } else {
        return date('d/m/Y', $timestamp);
    }
}
?>