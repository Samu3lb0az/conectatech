<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function loginUser($email, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    
    return false;
}

function registerUser($username, $email, $password, $full_name) {
    global $conn;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password, $full_name]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>