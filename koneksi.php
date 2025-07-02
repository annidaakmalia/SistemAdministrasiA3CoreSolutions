<?php
// koneksi.php

$host = 'localhost';
$user = 'root';
$password = ''; // Ganti dengan password MySQL Anda jika ada
$database = 'sistemadministrasi';

// Buat koneksi baru
$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    // Untuk pengembangan, bisa tampilkan error. Untuk produksi, log error saja dan tampilkan pesan umum.
    die("Koneksi gagal: " . $conn->connect_error);
}
?>