<?php
require "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (($_POST["aksi"] ?? "") === "hapus") {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    } else {
        $nama = trim($_POST["nama"] ?? "");
        if ($nama !== "") {
            $stmt = $pdo->prepare("INSERT IGNORE INTO kategori (nama) VALUES (?)");
            $stmt->execute([$nama]);
        }
    }
    header("Location: kategori.php");
    exit;
}

$daftar = $pdo->query(
    "SELECT k.id, k.nama, COUNT(w.id) AS jumlah
     FROM kategori k LEFT JOIN kata w ON w.kategori_id = k.id
     GROUP BY k.id, k.nama
     ORDER BY k.nama"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="wadah">
    <h1>Kategori</h1>

    <form method="post" class="form-tambah">
        <input name="nama" placeholder="Nama kategori, misalnya Bandara" required>
        <button class="tombol utama">Tambah kategori</button>
    </form>

    <h2>Daftar Kategori (<?= count($daftar) ?>)</h2>
    <ul class="daftar">
        <?php foreach ($daftar as $c): ?>
            <li class="item">
                <div>
                    <strong><?= htmlspecialchars($c["nama"]) ?></strong>
                    <span class="arti">(<?= (int)$c["jumlah"] ?> kata)</span>
                </div>
                <div class="aksi">
                    <a class="tombol kecil" href="index.php?kategori=<?= (int)$c["id"] ?>">Lihat</a>
                    <form method="post"
                          onsubmit="return confirm('Hapus kategori ini? Kata-katanya tidak ikut terhapus.')">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= (int)$c["id"] ?>">
                        <button class="tombol kecil bahaya">Hapus</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <p><a href="index.php">Kembali ke daftar kata</a></p>
</main>
</body>
</html>