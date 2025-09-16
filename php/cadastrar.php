<?php
session_start();
include_once "../config/database.php"; // Supondo que $conn seja uma instância PDO

// Inicializa variáveis de mensagem
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        // Verificar se email já existe
        $checkSql = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $checkStmt->execute();
        if ($checkStmt->rowCount() > 0) {
            $error = "Este email já está cadastrado.";
        } else {
            // Hash da senha
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Inserir usuário
            $sql = "INSERT INTO users (email, password, full_name) VALUES (:email, :password, :full_name)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':full_name', $name, PDO::PARAM_STR);
            if ($stmt->execute()) {
                $success = "Cadastro realizado com sucesso! Você pode fazer login agora.";
                header("Location: pagina_inicial.php");
                exit();
            } else {
                $error = "Erro ao cadastrar usuário. Tente novamente.";
            }
        }
    }
}

?>

<!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../assets/css/style.css">
        <title>Login - Vibe</title>
    </head>

    <body class="login-body">

        <div class="container">
                <?php if (!empty($error)): ?>
                    <div class="error-message" style="color: red; margin-bottom: 10px;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="success-message" style="color: green; margin-bottom: 10px;">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
            <button class="voltar-login" type="button" onclick="history.back()">
            Voltar
        </button>
            <div class="logo-login">
                <img src="../assets/img/vibe-logo.png" alt="">
            </div>
            <form class="fomulário-de-login" action="" method="POST">
                <label class="label-login" for="name">Nome:</label>
                <input class="campos-login" type="text" id="name" name="name" required>

                <label class="label-login" for="email">Email:</label>
                <input class="campos-login" type="email" id="email" name="email" required>

                <label class="label-login" for="password">Senha:</label>
                <input class="campos-login" type="password" id="password" name="password" required>

                <button class="button-login" type="submit">Cadastrar</button>
            </form>
        </div>
    </body>

    </html>