<?php
require "koneksi.php";

$terakhir = (int)($_GET["terakhir"] ?? 0);

// Ambil satu kata acak, selain kata yang baru saja tampil
$stmt = $pdo->prepare("SELECT * FROM kata WHERE id != ? ORDER BY RAND() LIMIT 1");
$stmt->execute([$terakhir]);
$k = $stmt->fetch();

// Kalau hanya ada satu kata di tabel, ambil kata itu saja
if (!$k) {
    $k = $pdo->query("SELECT * FROM kata LIMIT 1")->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belajar Kartu</title>
    <style>
        body { font-family: sans-serif; text-align: center; margin: 2rem; }
        .scene { width: 320px; max-width: 90%; height: 200px; margin: 2rem auto;
                 perspective: 1000px; cursor: pointer; }
        .kartu { position: relative; width: 100%; height: 100%;
                 transition: transform 0.6s; transform-style: preserve-3d; }
        .kartu.balik { transform: rotateY(180deg); }
        .sisi { position: absolute; inset: 0; display: flex; flex-direction: column;
                justify-content: center; align-items: center; padding: 1rem;
                border: 2px solid #333; border-radius: 12px; color: #000;
                backface-visibility: hidden; background: #fff; }
        .belakang { transform: rotateY(180deg); background: #eef6ff; }
        .petunjuk { color: #666; font-size: 0.9rem; }
        .tombol { display: inline-block; padding: 0.5rem 1rem; border: 1px solid #333;
                  border-radius: 6px; text-decoration: none; color: #000; }
    </style>
</head>
<body>
    <h1>Belajar Kartu</h1>

    <?php if ($k): ?>
        <div class="scene" onclick="document.getElementById('kartu').classList.toggle('balik')">
            <div class="kartu" id="kartu">
                <div class="sisi depan">
                    <h2><?= htmlspecialchars($k["inggris"]) ?></h2>
                    <span class="petunjuk">Klik kartu untuk melihat arti</span>
                </div>
                <div class="sisi belakang">
                    <h2><?= htmlspecialchars($k["arti"]) ?></h2>
                    <em><?= htmlspecialchars($k["contoh"]) ?></em>
                </div>
            </div>
        </div>

        <a class="tombol" href="kartu.php?terakhir=<?= (int)$k["id"] ?>">Kartu berikutnya</a>
    <?php else: ?>
        <p>Belum ada kata. Tambahkan dulu di halaman utama.</p>
    <?php endif; ?>

    <p><a href="index.php">Kembali ke daftar</a></p>
</body>
</html>