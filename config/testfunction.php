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

function rule_desain($material, $desain_pilihan, $data_aturan_desain)
{
    if (empty($desain_pilihan)) return true;

    foreach ($desain_pilihan as $id_rule_desain) {
        if (!isset($data_aturan_desain[$id_rule_desain])) continue;
        $rule = $data_aturan_desain[$id_rule_desain];

        if ($rule['Estetika'] !== null && $material['Estetika'] < $rule['Min_Estetika']) return false;
        if ($rule['KS'] !== null && $material['KS'] < $rule['Min_KK']) return false;
        if ($rule['KA'] !== null && $material['KA'] < $rule['Min_KA']) return false;

        if ($rule['Tipe_Material'] !== null) {
            $rule_tipe = array_map('trim', explode(',', strtolower($rule['Tipe_Material'])));
            $material_tipe = array_map('trim', explode(',', strtolower($material['Tipe_Material'] ?? $material['Tipe'] ?? '')));
            if (count(array_intersect($rule_tipe, $material_tipe)) == 0) return false;
        }
    }
    return true;
}


define('DEFAULT_CF_RULE', 0.6);

function rule_material($material, $aktif_rules, $data_aturan)
{
    $cf_gabungan = 0;

    foreach ($aktif_rules as $rule_id) {

        if (!isset($data_aturan[$rule_id])) continue;

        $rule = $data_aturan[$rule_id];
        $hasil = true;

        if ($rule['KS'] !== null && $material['KS'] < $rule['KS']) $hasil = false;
        if ($rule['KA'] !== null && $material['KA'] < $rule['KA']) $hasil = false;
        if ($rule['Estetika'] !== null && $material['Estetika'] < $rule['Estetika']) $hasil = false;
        if (isset($rule['Min_Aford']) && $rule['Min_Aford'] !== null) {
            if ($material['Afordabilitas'] < $rule['Min_Aford']) $hasil = false;
        }
        if (isset($rule['Max_Aford']) && $rule['Max_Aford'] !== null) {
            if ($material['Afordabilitas'] >= $rule['Max_Aford']) $hasil = false;
        }
        if (isset($rule['Min_Lebar']) && $rule['Min_Lebar'] !== null) {
            if ($material['CF_Lebar'] < $rule['Min_Lebar']) $hasil = false;
        }
        $cf_k = $hasil ? ($rule['CF_Rule'] ?? DEFAULT_CF_RULE) : 0;
        if ($cf_k > 0) {
            $cf_gabungan = $cf_gabungan + ($cf_k * (1 - $cf_gabungan));
        }
    }
    return round($cf_gabungan, 6);
}


$hasil_rekomendasi = [];
$aturan_aktif = [];
$is_submitted = false;
$error_message = "";

$desain_pilihan = $_POST['desain'] ?? [];

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

$selAD = $pdo->query("SELECT * FROM Aturan_Desain");
$data_aturan_desain_db = $selAD->fetchAll();

$data_aturan_desain = [];
foreach ($data_aturan_desain_db as $d) {
    $data_aturan_desain[$d['ID_RULE_DESAIN']] = [
        'Estetika'      => $d['Min_Estetika'] ?? null,
        'KS'           => $d['Min_KS'] ?? null,
        'KA'           => $d['Min_KA'] ?? null,
        'Tipe_Material'=> $d['Tipe_Material'] ?? null,
        'CF_Rule'      => $d['CF_Pakar'] ?? 0.6
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $is_submitted = true;

    foreach (['lokasi','kualitas_rangka','awet','prioritas','estetika','ukuran'] as $key) {
        if (isset($_POST[$key])) $aturan_aktif[] = $_POST[$key];
    }

    if (empty($aturan_aktif)) {
        $error_message = "Mohon pilih minimal 1 kriteria";
    } else {

        // untuk select database material
        $selM = $pdo->query("SELECT * FROM Material");
        $data_material = $selM->fetchAll();

        foreach ($data_material as $material) {

            // fiter desain
            if (!rule_desain($material, $desain_pilihan, $data_aturan_desain)) {
                continue;
            }

            // CF akhier
            $cf = rule_material($material, $aturan_aktif, $data_aturan);

            if ($cf > 0) {
                $material['CF_Akhir'] = $cf;
                $hasil_rekomendasi[] = $material;
            }
        }

        usort($hasil_rekomendasi, fn($a,$b) => $b['CF_Akhir'] <=> $a['CF_Akhir']);
    }
}
?>
