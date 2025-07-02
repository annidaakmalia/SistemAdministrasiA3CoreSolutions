<?php
// index.php (Halaman Login)
session_start();
include 'koneksi.php';

// Jika user sudah login, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, username, password, role_id FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error_message = "Username atau password salah.";
        }
    } else {
        $error_message = "Username atau password salah.";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Administrasi Karyawan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1 style="font-size:24px;" class="company-name">Sistem Administrasi Karyawan</h1>
            <h2 style="margin-top: 15px;">A3 Core Solutions</h2>
            <?php if ($error_message): ?>
                <div class="alert-message alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form method="POST" action="index.php">
                <input type="text" name="username" placeholder="Username" required autocomplete="username">
                <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
                <button type="submit">Login</button>
                <p style="margin-top: 20px;">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            </form>
        </div>
    </div>
</body>
</html>
