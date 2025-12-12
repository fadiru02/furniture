<?php

$is_submitted = true; 
$hasil_rekomendasi = $hasil_rekomendasi ?? []; 

// --- PERBAIKAN PENTING ---
$desain_pilihan = $_POST['desain'] ?? "";

// Jika hasil select bukan array (karena tidak pakai multiple), jadikan array agar aman
if (!is_array($desain_pilihan)) {
    $desain_pilihan = [$desain_pilihan];
}

$error_message = ""; 
include 'config/fuzzyfunction.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Diagnosa Material Furniture</title>
    <link rel="stylesheet" href="/furniture/css/ujicoba.css">
</head>

<body>
<header>
    <div class="logo">BAROKAH</div>
    <nav>
        <a href="/furniture/landing.php">Home</a>
        <a href="diagnosa.php">Diagnosa</a>
        <a href="#">Contact</a>
    </nav>
</header>

<div class="space"></div>
<div class="container">

<?php if ($is_submitted && empty($error_message)): ?>

    <div class="text-hero">
        <h2>Hasil Perhitungan Certainty Factor</h2>
        <p>Material diurutkan berdasarkan nilai CF Akhir.</p>
    </div>

    <?php if (!empty($desain_pilihan)): ?>
        <div style="text-align:center; margin-bottom:15px;">
            <strong>Desain dipilih:</strong>
          <?= implode(', ', array_map('htmlspecialchars', (array)$desain_pilihan)); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($hasil_rekomendasi)): ?>

        <div class="table-header">
            <div>No</div>
            <div>CF Akhir</div>
            <div>Material</div>
            <div>Afordabilitas</div>
            <div>Harga</div>
            <div>Tipe Material</div>
        </div>

        <div class="warp-table">
            <table class="hasil-table">
                <tbody>
                <?php 
                $rank = 1;
                foreach ($hasil_rekomendasi as $r):
                ?>
                <tr class="<?= ($rank == 1 ? 'rank-1' : ''); ?>">
                    <td><?= $rank++; ?></td>
                    <td><strong><?= number_format($r['CF_Akhir'], 4); ?></strong></td>
                    <td><?= htmlspecialchars($r['Nama_Material']); ?></td>
                    <td><?= number_format($r['Afordabilitas'], 2); ?></td>
                    <td>Rp <?= number_format($r['Harga_Rata'], 0, ',', '.'); ?></td>
                    <td><?= nl2br(htmlspecialchars(str_replace(", ", "\n", $r['Tipe_Material']))); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div style="text-align:center; padding:20px; background:#f0f0f0; border-radius:5px;">
            <strong>Tidak ada material yang cocok.</strong> Coba pilih kriteria lebih umum.
        </div>
    <?php endif; ?>

<?php endif; ?>

    <div class="hero-text">
        <p class="text-center">
            <a href='diagnosa1.php'>&laquo; Kembali ke Form Diagnosa</a>
        </p>
    </div>
</div>

</body>
</html>
