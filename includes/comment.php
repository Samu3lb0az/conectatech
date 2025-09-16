<?php
session_start();
require_once '../config/database.php';
require_once 'auth.php';

if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $post_id = $_POST['post_id'];
    $comment = trim($_POST['comment']);
    
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, post_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $comment]);
    }
}

// Sempre redireciona de volta para a página inicial
header("Location: ../php/pagina_inicial.php"); // ajuste o nome do arquivo da sua página inicial
exit;
