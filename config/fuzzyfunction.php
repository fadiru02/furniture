<?php
$host = 'localhost';
$db   = 'db_furniture';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi database GAGAL! Error: " . $e->getMessage());
}

// Fungsi untuk menghitung derajat keanggotaan fuzzy (membership function)
function fuzzy_membership($nilai_aktual, $nilai_target, $tipe = 'naik')
{
    if ($nilai_target == 0 || $nilai_target === null) return 1;
    
    if ($tipe == 'naik') {
        // Semakin tinggi nilai aktual, semakin baik (untuk KS, KA, Estetika)
        $derajat = $nilai_aktual / $nilai_target;
        return min($derajat, 1); // Cap at 1
    } elseif ($tipe == 'turun') {
        // Semakin rendah nilai aktual, semakin baik (untuk harga/affordability)
        if ($nilai_aktual <= $nilai_target) return 1;
        $derajat = $nilai_target / $nilai_aktual;
        return max($derajat, 0); // Floor at 0
    }
    
    return 0;
}

// Rule untuk filter desain (hard constraint)
function rule_desain($material, $desain_pilihan, $data_aturan_desain)
{
    // FIX: pastikan array
    if (!is_array($desain_pilihan)) {
        $desain_pilihan = [$desain_pilihan];
    }

    if (empty($desain_pilihan)) return true;

    foreach ($desain_pilihan as $id_rule_desain) {
        if (!isset($data_aturan_desain[$id_rule_desain])) continue;
        $rule = $data_aturan_desain[$id_rule_desain];

        if ($rule['Estetika'] !== null && $material['Estetika'] < $rule['Estetika']) return false;
        if ($rule['KS'] !== null && $material['KS'] < $rule['KS']) return false;
        if ($rule['KA'] !== null && $material['KA'] < $rule['KA']) return false;

        if ($rule['Tipe_Material'] !== null) {
            $rule_tipe = array_map('trim', explode(',', strtolower($rule['Tipe_Material'])));
            $material_tipe = array_map('trim', explode(',', strtolower($material['Tipe_Material'] ?? $material['Tipe'] ?? '')));
            if (count(array_intersect($rule_tipe, $material_tipe)) == 0) return false;
        }
    }
    
    return true;
}


define('DEFAULT_CF_RULE', 0.6);

// Fungsi utama: Fuzzy Logic + Certainty Factor
function rule_material($material, $aktif_rules, $data_aturan)
{
    $cf_gabungan = 0; 

    foreach ($aktif_rules as $rule_id) {
        if (!isset($data_aturan[$rule_id])) continue;
        $rule = $data_aturan[$rule_id];

        $derajat_fuzzy = []; 

        // 1. Kekuatan Struktural (KS) - semakin tinggi semakin baik
        if ($rule['KS'] !== null) {
            $derajat_ks = fuzzy_membership($material['KS'], $rule['KS'], 'naik');
            $derajat_fuzzy[] = $derajat_ks;
        }

        // 2. Ketahanan Aus (KA) - semakin tinggi semakin baik
        if ($rule['KA'] !== null) {
            $derajat_ka = fuzzy_membership($material['KA'], $rule['KA'], 'naik');
            $derajat_fuzzy[] = $derajat_ka;
        }

        // 3. Estetika - semakin tinggi semakin baik
        if ($rule['Estetika'] !== null && $rule['Estetika'] > 0) {
            $derajat_estetika = fuzzy_membership($material['Estetika'], $rule['Estetika'], 'naik');
            $derajat_fuzzy[] = $derajat_estetika;
        }

        // 4. Affordability Min - material harus lebih murah dari batas
        if (isset($rule['Min_Aford']) && $rule['Min_Aford'] !== null) {
            $derajat_aford = fuzzy_membership($material['Afordabilitas'], $rule['Min_Aford'], 'turun');
            $derajat_fuzzy[] = $derajat_aford;
        }

        // 5. Affordability Max - material harus di bawah batas maksimal
        if (isset($rule['Max_Aford']) && $rule['Max_Aford'] !== null) {
            if ($material['Afordabilitas'] > $rule['Max_Aford']) {
                $derajat_fuzzy[] = 0; // Gagal memenuhi kriteria
            }
        }

        // 6. Lebar minimal
        if (isset($rule['Min_Lebar']) && $rule['Min_Lebar'] !== null) {
            $derajat_lebar = fuzzy_membership($material['CF_Lebar'], $rule['Min_Lebar'], 'naik');
            $derajat_fuzzy[] = $derajat_lebar;
        }

        // Agregasi fuzzy menggunakan MIN operator (T-norm)
        $derajat_fuzzy_final = empty($derajat_fuzzy) ? 1 : min($derajat_fuzzy);

        // Hitung CF untuk rule ini
        $cf_rule = $rule['CF_Rule'] ?? DEFAULT_CF_RULE;
        $cf_k = $derajat_fuzzy_final * $cf_rule;

        // Kombinasi CF menggunakan rumus CF1 + CF2 * (1 - CF1)
        if ($cf_k > 0) {
            $cf_gabungan = $cf_gabungan + ($cf_k * (1 - $cf_gabungan));
        }
    }

    return round($cf_gabungan, 6);
}

// ========== MAIN EXECUTION ==========

$hasil_rekomendasi = [];
$aturan_aktif = [];
$is_submitted = false;
$error_message = "";

$desain_pilihan = $_POST['desain'] ?? [];

// Load rules dari database
$SelA = $pdo->query("SELECT * FROM Aturan");
$data_aturan_db = $SelA->fetchAll();

$data_aturan = [];
foreach ($data_aturan_db as $r) {
    $data_aturan[$r['ID_RULE']] = [
        'CF_Rule'      => $r['CF_Rule'] ?? 0.6,
        'KS'           => $r['Min_KS'] ?? null,
        'KA'           => $r['Min_KA'] ?? null,
        'Estetika'     => $r['Min_Estetika'] ?? null,
        'Min_Aford'    => $r['Min_Afordabilitas'] ?? null,
        'Max_Aford'    => $r['Max_Afordabilitas'] ?? null,
        'Min_Lebar'    => $r['Min_Lebar'] ?? null,
    ];
}

// Load design rules
$selAD = $pdo->query("SELECT * FROM Aturan_Desain");
$data_aturan_desain_db = $selAD->fetchAll();

$data_aturan_desain = [];
foreach ($data_aturan_desain_db as $d) {
    $data_aturan_desain[$d['ID_RULE_DESAIN']] = [
        'Estetika'      => $d['Min_Estetika'] ?? null,
        'KS'            => $d['Min_KS'] ?? null,
        'KA'            => $d['Min_KA'] ?? null,
        'Min_Aford'     => $d['Min_Aford'] ?? null,
        'Tipe_Material' => $d['Tipe_Material'] ?? null,
        'CF_Rule'       => $d['CF_Pakar'] ?? 0.6
    ];
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $is_submitted = true;

    // Kumpulkan rules yang aktif
    foreach (['lokasi','kualitas_rangka','awet','prioritas','estetika','ukuran'] as $key) {
        if (isset($_POST[$key])) $aturan_aktif[] = $_POST[$key];
    }

    if (empty($aturan_aktif)) {
        $error_message = "Mohon pilih minimal 1 kriteria";
    } else {
        // Ambil semua material dari database
        $selM = $pdo->query("SELECT * FROM Material");
        $data_material = $selM->fetchAll();

        foreach ($data_material as $material) {
            // Filter berdasarkan desain (hard constraint)
            if (!rule_desain($material, $desain_pilihan, $data_aturan_desain)) {
                continue;
            }

            // Hitung CF menggunakan fuzzy logic
            $cf = rule_material($material, $aturan_aktif, $data_aturan);

            if ($cf > 0) {
                $material['CF_Akhir'] = $cf;
                $hasil_rekomendasi[] = $material;
            }
        }

        // Sort descending berdasarkan CF (CF tertinggi di atas)
        usort($hasil_rekomendasi, fn($a,$b) => $b['CF_Akhir'] <=> $a['CF_Akhir']);
    }
}
?>