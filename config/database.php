<?php
// Configurações do RDS
$servername = "database-redesocial.cv62c22guy53.us-east-1.rds.amazonaws.com"; // Seu endpoint RDS
$username = "admin"; // Usuário master do RDS
$password = "redeSocial!"; // Senha do RDS
$dbname = "database_redeSocial"; // Nome do banco inicial
$port = "3306";

try {
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 10); // Timeout de 10 segundos
    echo "Conexão com RDS estabelecida com sucesso!";
} catch(PDOException $e) {
    echo "Erro na conexão com RDS: " . $e->getMessage();
}
?>