<?php
require "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (($_POST["aksi"] ?? "") === "hapus") {
        $stmt = $pdo->prepare("DELETE FROM kata WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    } else {
        $kid = ($_POST["kategori_id"] ?? "") !== "" ? (int)$_POST["kategori_id"] : null;
        $stmt = $pdo->prepare(
            "INSERT INTO kata (inggris, arti, contoh, kategori_id) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$_POST["inggris"], $_POST["arti"], $_POST["contoh"], $kid]);
    }
    header("Location: index.php");
    exit;
}

$kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();

// Filter berdasarkan kategori (?kategori=ID atau ?kategori=tanpa)
$filter = $_GET["kategori"] ?? "";
$where = "";
$param = [];
if ($filter === "tanpa") {
    $where = "WHERE w.kategori_id IS NULL";
} elseif ($filter !== "") {
    $where = "WHERE w.kategori_id = ?";
    $param[] = (int)$filter;
}

$stmt = $pdo->prepare(
    "SELECT w.*, k.nama AS nama_kategori
     FROM kata w LEFT JOIN kategori k ON k.id = w.kategori_id
     $where
     ORDER BY w.id DESC"
);
$stmt->execute($param);
$daftar = $stmt->fetchAll();
?>

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
                    <button type="button" class="tombol kecil suara"
                        data-teks="<?= htmlspecialchars($k["inggris"]) ?>">Listen</button>
                    <span class="petunjuk">Klik kartu untuk melihat arti</span>
                </div>
                <div class="sisi belakang">
                    <h2><?= htmlspecialchars($k["arti"]) ?></h2>
                    <em><?= htmlspecialchars($k["contoh"]) ?></em>
                    <?php if ($k["contoh"]):?>
                        <button type="button" class="tombol kecil suara"
                            data-teks="<?= htmlspecialchars($k["contoh"]) ?>">Contoh Kalimat</button>
                    <?php endif; ?>
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