<?php
// generate_password_hash.php

// Ganti 'admin123' dengan password yang ingin Anda gunakan
$password_anda = 'admin123'; 

// Hasilkan hash untuk password tersebut
$hashed_password = password_hash($password_anda, PASSWORD_DEFAULT);

echo "Hash untuk '{$password_anda}': <br>";
echo "<pre>{$hashed_password}</pre>";
echo "<br>Salin teks di atas (termasuk \$2y\$10...) dan tempelkan ke phpMyAdmin.";
?>