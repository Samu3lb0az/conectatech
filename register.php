<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    
    if (registerUser($username, $email, $password, $full_name)) {
        if (loginUser($email, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Erro ao fazer login após o registro.';
        }
    } else {
        $error = 'Erro ao registrar. Email ou nome de usuário já existe.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaTech - Cadastro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-logo">
            <h1>ConectaTech</h1>
            <p>Conecte-se com seus colegas</p>
        </div>
        
        <form method="POST" class="auth-form">
            <h2>Cadastro</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Nome de usuário</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="full_name">Nome completo</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-primary">Cadastrar</button>
            
            <div class="auth-links">
                <p>Já tem uma conta? <a href="index.php">Faça login</a></p>
            </div>
        </form>
    </div>
</body>
</html>