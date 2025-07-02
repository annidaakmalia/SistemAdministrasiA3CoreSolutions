<?php
// logout.php
session_start(); // Mulai sesi

// Hancurkan semua data sesi
session_unset(); // Menghapus semua variabel sesi
session_destroy(); // Menghancurkan sesi

// Tentukan waktu penundaan sebelum pengalihan (dalam detik)
$redirect_delay = 3; // Ubah nilai ini jika Anda ingin waktu yang berbeda
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Berhasil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS KHUSUS UNTUK HALAMAN LOGOUT INI */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        .logout-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .logout-container h1 {
            color: #dc3545; /* Merah untuk judul logout */
            margin-bottom: 20px;
            font-size: 2.2em;
        }
        .logout-container p {
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        .logout-container .logout-icon {
            font-size: 4em; /* Ukuran ikon lebih besar */
            color: #dc3545; /* Warna ikon merah */
            margin-bottom: 25px;
        }
        .logout-container .redirect-message {
            font-size: 0.9em;
            color: #666;
            margin-top: 20px;
        }
    </style>

    <meta http-equiv="refresh" content="<?php echo $redirect_delay; ?>;url=index.php">
</head>
<body>
    <div class="logout-container">
        <i class="fas fa-door-open logout-icon"></i> <h1>Anda Telah Berhasil Logout!</h1>
        <p>Terima kasih telah menggunakan aplikasi kami.</p>
        <p class="redirect-message">Anda akan dialihkan ke halaman login dalam <span id="countdown"><?php echo $redirect_delay; ?></span> detik...</p>
    </div>

    <script>
        // JavaScript untuk menampilkan hitungan mundur
        let countdown = <?php echo $redirect_delay; ?>;
        const countdownElement = document.getElementById('countdown');

        const interval = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            if (countdown <= 0) {
                clearInterval(interval); // Hentikan interval saat hitungan mencapai 0
            }
        }, 1000); // Setiap 1 detik
    </script>
</body>
</html>