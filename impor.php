<?php
require "koneksi.php";

$laporan = null;
$kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $baris = preg_split('/\R/', trim($_POST["teks"] ?? ""));
    $tambah = 0;
    $duplikat = [];
    $rusak = [];

    // Tentukan kategori: kategori baru (kalau diisi) menang atas pilihan dropdown
    $kid = ($_POST["kategori_id"] ?? "") !== "" ? (int)$_POST["kategori_id"] : null;
    $baru = trim($_POST["kategori_baru"] ?? "");
    if ($baru !== "") {
        $pdo->prepare("INSERT IGNORE INTO kategori (nama) VALUES (?)")->execute([$baru]);
        $cari = $pdo->prepare("SELECT id FROM kategori WHERE nama = ?");
        $cari->execute([$baru]);
        $kid = (int)$cari->fetchColumn();
    }

    $cek = $pdo->prepare("SELECT COUNT(*) FROM kata WHERE LOWER(inggris) = LOWER(?)");
    $sisip = $pdo->prepare(
        "INSERT INTO kata (inggris, arti, contoh, kategori_id) VALUES (?, ?, ?, ?)"
    );

    $pdo->beginTransaction();
    foreach ($baris as $no => $b) {
        $b = trim($b);
        if ($b === "") continue;

        // Lewati garis pemisah tabel seperti |---|---|
        if (preg_match('/^[\s|:\-]+$/', $b)) continue;

        // Buang tanda | di awal/akhir (kalau AI membuat format tabel)
        $b = trim($b, "| ");

        $bagian = array_map("trim", explode("|", $b));
        if (count($bagian) < 2 || $bagian[0] === "" || $bagian[1] === "") {
            $rusak[] = $no + 1;
            continue;
        }

        $inggris = $bagian[0];
        $arti    = $bagian[1];
        $contoh  = $bagian[2] ?? "";

        $cek->execute([$inggris]);
        if ($cek->fetchColumn() > 0) {
            $duplikat[] = $inggris;
            continue;
        }

        $sisip->execute([$inggris, $arti, $contoh, $kid]);
        $tambah++;
    }
    $pdo->commit();

    $laporan = ["tambah" => $tambah, "duplikat" => $duplikat, "rusak" => $rusak];

    // Refresh daftar kategori supaya kategori baru langsung muncul di dropdown
    $kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impor Kosakata</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="wadah">
    <h1>Impor Kosakata</h1>
    <p class="petunjuk">
        Tempel daftar kata, satu baris per kata, dengan format:
        <code>kata | arti | contoh kalimat</code>
    </p>

    <?php if ($laporan): ?>
        <div class="item" style="display:block; margin-bottom:1rem">
            <p><strong><?= $laporan["tambah"] ?></strong> kata berhasil ditambahkan.</p>
            <?php if ($laporan["duplikat"]): ?>
                <p>Dilewati karena sudah ada:
                    <?= htmlspecialchars(implode(", ", $laporan["duplikat"])) ?></p>
            <?php endif; ?>
            <?php if ($laporan["rusak"]): ?>
                <p>Baris dengan format salah (nomor baris):
                    <?= htmlspecialchars(implode(", ", $laporan["rusak"])) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-tambah">
        <select name="kategori_id">
            <option value="">Tanpa kategori</option>
            <?php foreach ($kategori as $c): ?>
                <option value="<?= (int)$c["id"] ?>"><?= htmlspecialchars($c["nama"]) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="kategori_baru" placeholder="...atau buat kategori baru (opsional)">

        <textarea name="teks" placeholder="airport | bandara | We arrived at the airport early."
                  required></textarea>
        <button class="tombol utama">Impor</button>
    </form>

    <p><a href="index.php">Kembali ke daftar</a> · <a href="kategori.php">Kelola kategori</a></p>
</main>
</body>
</html>