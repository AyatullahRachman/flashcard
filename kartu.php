<?php
require "koneksi.php";

$kat = $_GET["kategori"] ?? "";

// Simpan penilaian (hafal / belum), lalu lanjut ke kartu berikutnya
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $status = ($_POST["status"] ?? "") === "hafal" ? "hafal" : "belum";
    $stmt = $pdo->prepare("UPDATE kata SET status = ? WHERE id = ?");
    $stmt->execute([$status, $_POST["id"]]);
    $tambahan = ($_POST["kategori"] ?? "") !== ""
        ? "&kategori=" . urlencode($_POST["kategori"]) : "";
    header("Location: kartu.php?terakhir=" . (int)$_POST["id"] . $tambahan);
    exit;
}

$terakhir = (int)($_GET["terakhir"] ?? 0);

// Batasi ke kategori yang dipilih (kalau ada)
$syaratKat = "";
$paramKat = [];
if ($kat === "tanpa") {
    $syaratKat = " AND kategori_id IS NULL";
} elseif ($kat !== "") {
    $syaratKat = " AND kategori_id = ?";
    $paramKat[] = (int)$kat;
}

// 70% kemungkinan ambil kata yang belum hafal, 30% yang sudah hafal
$target = (mt_rand(1, 100) <= 70) ? "belum" : "hafal";

$ambil = $pdo->prepare(
    "SELECT * FROM kata WHERE status = ? AND id != ?" . $syaratKat . " ORDER BY RAND() LIMIT 1"
);
$ambil->execute(array_merge([$target, $terakhir], $paramKat));
$k = $ambil->fetch();

// Kalau kelompok itu kosong, ambil dari kelompok lainnya
if (!$k) {
    $lain = ($target === "belum") ? "hafal" : "belum";
    $ambil->execute(array_merge([$lain, $terakhir], $paramKat));
    $k = $ambil->fetch();
}

// Kalau hanya ada satu kata (di kategori itu)
if (!$k) {
    $satu = $pdo->prepare("SELECT * FROM kata WHERE 1=1" . $syaratKat . " LIMIT 1");
    $satu->execute($paramKat);
    $k = $satu->fetch();
}

// Statistik kemajuan (ikut terfilter oleh kategori)
$stat = $pdo->prepare(
    "SELECT COUNT(*) AS total, SUM(status = 'hafal') AS hafal FROM kata WHERE 1=1" . $syaratKat
);
$stat->execute($paramKat);
$s = $stat->fetch();
$total = (int)$s["total"];
$hafal = (int)$s["hafal"];

$kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
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

    <form method="get">
        <select name="kategori" onchange="this.form.submit()" style="max-width:320px">
            <option value="">Semua kategori</option>
            <option value="tanpa" <?= $kat === "tanpa" ? "selected" : "" ?>>Tanpa kategori</option>
            <?php foreach ($kategori as $c): ?>
                <option value="<?= (int)$c["id"] ?>"
                    <?= (string)$kat === (string)$c["id"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($c["nama"]) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($k): ?>
        <div class="scene" id="scene">
            <div class="kartu" id="kartu">
                <div class="sisi depan">
                    <h2><?= htmlspecialchars($k["inggris"]) ?></h2>
                    <button type="button" class="tombol kecil suara"
                        data-teks="<?= htmlspecialchars($k["inggris"]) ?>">Listen</button>
                    <span class="petunjuk">Klik kartu untuk melihat arti</span>
                </div>
                <div class="sisi belakang">
                    <h2><?= htmlspecialchars($k["arti"]) ?></h2>
                    <em><?= htmlspecialchars($k["contoh"]) ?></em>
                    <?php if ($k["contoh"]): ?>
                        <button type="button" class="tombol kecil suara"
                            data-teks="<?= htmlspecialchars($k["contoh"]) ?>">Contoh Kalimat</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form method="post" id="penilaian" class="penilaian" hidden>
            <input type="hidden" name="id" value="<?= (int)$k["id"] ?>">
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($kat) ?>">
            <button class="tombol" name="status" value="belum">Belum hafal</button>
            <button class="tombol utama" name="status" value="hafal">Sudah hafal</button>
        </form>

        <p>
            <a class="tombol kecil"
               href="kartu.php?terakhir=<?= (int)$k["id"] ?><?= $kat !== "" ? "&kategori=" . urlencode($kat) : "" ?>">Lewati</a>
        </p>
    <?php else: ?>
        <p>Belum ada kata<?= $kat !== "" ? " di kategori ini" : "" ?>. Tambahkan dulu di halaman utama.</p>
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

    function ucapkan(teks) {
        if (!('speechSynthesis' in window)) {
            alert('Browser ini tidak mendukung fitur pengucapan.');
            return;
        }
        window.speechSynthesis.cancel();          // hentikan suara sebelumnya
        const u = new SpeechSynthesisUtterance(teks);
        u.lang = 'en-US';
        u.rate = 0.9;                              // sedikit lebih pelan
        const suara = window.speechSynthesis.getVoices()
            .find(v => v.lang.startsWith('en'));
        if (suara) u.voice = suara;
        window.speechSynthesis.speak(u);
    }

    document.querySelectorAll('.suara').forEach(function (tombol) {
        tombol.addEventListener('click', function (e) {
            e.stopPropagation();                   // jangan ikut membalik kartu
            ucapkan(tombol.dataset.teks);
        });
    });
</script>
</body>
</html>