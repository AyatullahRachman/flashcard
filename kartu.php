<?php
require "koneksi.php";

// Simpan penilaian (hafal / belum), lalu lanjut ke kartu berikutnya
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $status = ($_POST["status"] ?? "") === "hafal" ? "hafal" : "belum";
    $stmt = $pdo->prepare("UPDATE kata SET status = ? WHERE id = ?");
    $stmt->execute([$status, $_POST["id"]]);
    header("Location: kartu.php?terakhir=" . (int)$_POST["id"]);
    exit;
}

$terakhir = (int)($_GET["terakhir"] ?? 0);

// 70% kemungkinan ambil kata yang belum hafal, 30% yang sudah hafal
$target = (mt_rand(1, 100) <= 70) ? "belum" : "hafal";

$ambil = $pdo->prepare(
    "SELECT * FROM kata WHERE status = ? AND id != ? ORDER BY RAND() LIMIT 1"
);
$ambil->execute([$target, $terakhir]);
$k = $ambil->fetch();

// Kalau kelompok itu kosong, ambil dari kelompok lainnya
if (!$k) {
    $lain = ($target === "belum") ? "hafal" : "belum";
    $ambil->execute([$lain, $terakhir]);
    $k = $ambil->fetch();
}

// Kalau hanya ada satu kata di tabel
if (!$k) {
    $k = $pdo->query("SELECT * FROM kata LIMIT 1")->fetch();
}

// Statistik kemajuan
$stat = $pdo->query(
    "SELECT COUNT(*) AS total, SUM(status = 'hafal') AS hafal FROM kata"
)->fetch();
$total = (int)$stat["total"];
$hafal = (int)$stat["hafal"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belajar Kartu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="wadah tengah">
    <h1>Belajar Kartu</h1>
    <p class="petunjuk">Sudah hafal: <?= $hafal ?> dari <?= $total ?> kata</p>

    <?php if ($k): ?>
        <div class="scene" id="scene">
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

        <form method="post" id="penilaian" class="penilaian" hidden>
            <input type="hidden" name="id" value="<?= (int)$k["id"] ?>">
            <button class="tombol" name="status" value="belum">Belum hafal</button>
            <button class="tombol utama" name="status" value="hafal">Sudah hafal</button>
        </form>

        <p><a class="tombol kecil" href="kartu.php?terakhir=<?= (int)$k["id"] ?>">Lewati</a></p>
    <?php else: ?>
        <p>Belum ada kata. Tambahkan dulu di halaman utama.</p>
    <?php endif; ?>

    <p><a href="index.php">Kembali ke daftar</a></p>
</main>

<script>
    const scene = document.getElementById('scene');
    if (scene) {
        scene.addEventListener('click', function () {
            document.getElementById('kartu').classList.toggle('balik');
            document.getElementById('penilaian').hidden = false;
        });
    }
</script>
</body>
</html>