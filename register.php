<?php
// register.php
session_start();
include 'koneksi.php'; // Panggil file koneksi database Anda

$message = ''; // Untuk menampilkan pesan sukses atau error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']); // Hapus spasi di awal/akhir
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = '<div class="alert-message alert-error">Username dan password harus diisi!</div>';
    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert-message alert-error">Konfirmasi password tidak cocok!</div>';
    } elseif (strlen($password) < 6) { // Contoh: password minimal 6 karakter
        $message = '<div class="alert-message alert-error">Password minimal 6 karakter!</div>';
    } else {
        // Cek apakah username sudah ada
        $sql_check_user = "SELECT id FROM users WHERE username = ?";
        $stmt_check_user = $conn->prepare($sql_check_user);
        $stmt_check_user->bind_param("s", $username);
        $stmt_check_user->execute();
        $result_check_user = $stmt_check_user->get_result();

        if ($result_check_user->num_rows > 0) {
            $message = '<div class="alert-message alert-error">Username sudah terdaftar!</div>';
        } else {
            // Hash password sebelum disimpan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Default role_id untuk user baru (misal: 3 untuk Karyawan)
            // Pastikan Anda memiliki role_id 3 dengan nama_role 'Karyawan' di tabel 'roles'
            $default_role_id = 3; 

            // Masukkan data user baru ke database
            $sql_insert = "INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssi", $username, $hashed_password, $default_role_id);

            if ($stmt_insert->execute()) {
                $message = '<div class="alert-message alert-success">Pendaftaran berhasil! Silakan <a href="index.php">login</a>.</div>';
                // Opsional: Langsung arahkan ke halaman login
                // header('Location: index.php');
                // exit;
            } else {
                $message = '<div class="alert-message alert-error">Error saat mendaftar: ' . $stmt_insert->error . '</div>';
            }
            $stmt_insert->close();
        }
        $stmt_check_user->close();
    }
}
$conn->close(); // Tutup koneksi
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Tambahan styling jika diperlukan untuk form register */
        .register-box {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 450px; /* Sedikit lebih lebar dari login box */
            width: 90%;
            margin: auto; /* Untuk memusatkan box saat body tidak flex */
        }
        .register-box h2 {
            margin-bottom: 30px;
            color: #0056b3;
        }
        .register-box input[type="text"],
        .register-box input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
            box-sizing: border-box;
        }
        .register-box button {
            width: 100%;
            padding: 12px;
            background-color: #28a745; /* Warna hijau untuk daftar */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }
        .register-box button:hover {
            background-color: #218838;
        }
        .register-box p {
            margin-top: 20px;
        }
        .register-box p a {
            color: #007bff;
            text-decoration: none;
        }
        .register-box p a:hover {
            text-decoration: underline;
        }

        /* Override body style dari style.css agar box terpusat */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #cccccc;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Daftar Akun Baru</h2>
        <?php echo $message; // Tampilkan pesan ?>
        <form method="POST" action="register.php">
            <input type="text" name="username" placeholder="Username Baru" required autocomplete="new-username">
            <input type="password" name="password" placeholder="Password (min 6 karakter)" required autocomplete="new-password">
            <input type="password" name="confirm_password" placeholder="Konfirmasi Password" required autocomplete="new-password">
            <button type="submit">Daftar Akun</button>
        </form>
        <p>Sudah punya akun? <a href="index.php">Login di sini</a></p>
    </div>
</body>
</html>