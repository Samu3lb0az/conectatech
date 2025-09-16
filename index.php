<?php
session_start();
include_once "config/database.php"; // Supondo que $conn seja uma instância PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Preparar a query com parâmetro nomeado
    $sql = "SELECT id, email, password FROM users WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    // Buscar o usuário
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $login_success = true;
        } else {
            $error = "Senha incorreta.";
        }
    } else {
        $error = "Usuário não encontrado.";
    }
}

?>

<<!DOCTYPE html>
    <html lang="en">

    <head>
           <meta charset="UTF-8">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <link rel="stylesheet" href="assets/css/style.css">
           <title>Login - Vibe</title>
           <!-- SweetAlert2 -->
           <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>

    <body class="login-body">

            <?php if (!empty($login_success)): ?>
                <script>
                    Swal.fire({
                        title: 'Logado com sucesso!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'php/pagina_inicial.php';
                    });
                </script>
            <?php endif; ?>

        <div class="container">
            <div class="logo-login">
                <img src="assets/img/vibe-logo.png" alt="">
            </div>
            <form class="fomulário-de-login" action="" method="POST">
                <label class="label-login" for="email">Email:</label>
                <input class="campos-login" type="email" id="email" name="email" required>

                <label class="label-login" for="password">Senha:</label>
                <input class="campos-login" type="password" id="password" name="password" required>

                <button class="button-login" type="submit">Entrar</button>
                <a class="cadastrar" href="php/cadastrar.php">Não possui uma conta? Cadastre-se</a>
            </form>
        </div>
    </body>

    </html>