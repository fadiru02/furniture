<?php
$error_message = $error_message ?? '';
$desain_pilihan = $_POST['desain'] ?? [];
include 'config/fuzzyfunction.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnosa Material Furniture</title>
    <link rel="stylesheet" href="/furniture/css/ujicoba.css">
</head>

<body>
<header>
    <div class="logo">BAROKAH</div>
    <nav>
        <a href="/furniture/landing.php">Home</a>
        <a href="#" class="active">Diagnosa</a>
        <a href="#">Contact</a>
    </nav>
</header>

<div class="space"></div>
<div class="container">
    <div class="hero-text">
        <h2>Diagnosa Material Furniture</h2>
    </div>

    <?php if ($error_message): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; margin-bottom:20px;">
            <strong>Error:</strong> <?= htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form action="hasildig.php" method="POST" class="diagnosa-form"> 
        <div class="container">
            <h2>Pilih Kriteria Anda</h2>
            <p>Pilih satu opsi dari setiap kriteria di bawah ini:</p>
        </div>

        <table class="kriteria-table">
            <thead>
                <tr>
                    <th style="width:30%;">Kriteria</th>
                    <th>Pilihan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Gaya Desain</td>
                    <td>
                        <select name="desain" required>
                            <option value="">Pilih Gaya Desain...</option>
                            <option value="RD1">Klasik</option>
                            <option value="RD2">Mewah</option>
                            <option value="RD3">Vintage</option>
                            <option value="RD4">Modern</option>
                            <option value="RD5">Kontemporer</option>
                            <option value="RD6">Minimalis</option>
                            <option value="RD7">Industrial</option>
                            <option value="RD8">Modern</option>
                            <option value="RD9">Rustic</option>
                            <option value="RD10">Natural</option>
                            <option value="RD11">Tradisional</option>
                            <option value="RD12">Skandinavia</option>
                            <option value="RD13">Artistik</option>
                            <option value="RD14">Modern</option>
                            <option value="RD15">Kontemporer</option>
                            <option value="RD16">Fungsional</option>
                            <option value="RD17">Minimalis</option>
                            <option value="RD18">Ekonomis</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Lokasi Penggunaan</td>
                    <td>
                        <select name="lokasi" required>
                            <option value="">Pilih Lokasi...</option>
                            <option value="R1">Indoor</option>
                            <option value="R2">Outdoor</option>
                            <option value="R3">Area Lembab</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Kualitas Kekuatan Rangka</td>
                    <td>
                        <select name="kualitas_rangka" required>
                            <option value="">Pilih Kualitas...</option>
                            <option value="R4">Tinggi</option>
                            <option value="R5">Sedang</option>
                            <option value="R6">Rendah</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Prioritas Keawetan</td>
                    <td>
                        <select name="awet" required>
                            <option value="">Pilih Prioritas Keawetan...</option>
                            <option value="R7">Tinggi</option>
                            <option value="R8">Sedang</option>
                            <option value="R9">Rendah</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Prioritas Pembelian</td>
                    <td>
                        <select name="prioritas" required>
                            <option value="">Pilih Prioritas...</option>
                            <option value="R14">Kualitas Terbaik</option>
                            <option value="R13">Harga Sedang</option>
                            <option value="R12">Harga Terjangkau</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Pentingnya Estetika</td>
                    <td>
                        <select name="estetika" required>
                            <option value="">Pilih Tingkat Estetika...</option>
                            <option value="R15">Sangat Penting</option>
                            <option value="R16">Cukup Penting</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Ukuran Furniture</td>
                    <td>
                        <select name="ukuran" required>
                            <option value="">Pilih Ukuran...</option>
                            <option value="R10">Lebar</option>
                            <option value="R11">Biasa</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="hero-text">
            <div class="submit-area">
                <button type="submit">submit</button>
            </div>
        </div>
    </form>
</div>
<footer>
        <div class="footer-left">
            <p>PT. BAROKAH</p>
            <p>©2025. All rights reserved</p>
        </div>
        <div class="footer-right">
            <a href="#">About Us</a>
            <a href="#">Partnership</a>
            <a href="#">Privacy Police</a>
        </div>
    </footer>
</body>
</html>