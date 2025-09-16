<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conteudo = isset($_POST['conteudo']) ? trim($_POST['conteudo']) : null;
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : null;

$imagem = null;
if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
    $nome_arquivo = uniqid('post_', true) . '.' . $ext;
    $caminho = '../assets/img/posts/' . $nome_arquivo;
    if (!is_dir('../assets/img/posts')) {
        mkdir('../assets/img/posts', 0777, true);
    }
    if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho)) {
        $imagem = '../assets/img/posts/' . $nome_arquivo;
    }
}

if (empty($conteudo) && empty($imagem)) {
    echo json_encode(['success' => false, 'error' => 'Preencha o texto ou envie uma imagem.']);
    exit();
}

$sql = "INSERT INTO posts (user_id, conteudo, imagem, descricao, created_at) VALUES (:user_id, :conteudo, :imagem, :descricao, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':conteudo', $conteudo, PDO::PARAM_STR);
$stmt->bindParam(':imagem', $imagem, PDO::PARAM_STR);
$stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    $errorInfo = $stmt->errorInfo();
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao salvar publicação.',
        'pdo_error' => $errorInfo
    ]);
}
