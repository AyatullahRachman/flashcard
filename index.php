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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Hafalan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="wadah">
    <h1>Kartu Hafalan Kosakata</h1>

    <p>
        <a class="tombol utama" href="kartu.php<?= $filter !== "" ? "?kategori=" . urlencode($filter) : "" ?>">Mulai belajar</a>
        <a class="tombol" href="impor.php">Impor kata</a>
    </p>

    <form method="post" class="form-tambah">
        <input name="inggris" placeholder="Kata (English)" required>
        <input name="arti" placeholder="Arti" required>
        <input name="contoh" placeholder="Contoh kalimat" required>
        <select name="kategori_id">
            <option value="">Tanpa kategori</option>
            <?php foreach ($kategori as $c): ?>
                <option value="<?= (int)$c["id"] ?>"><?= htmlspecialchars($c["nama"]) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="tombol utama">Tambah</button>
    </form>

    <h2>Daftar Kata (<?= count($daftar) ?>)</h2>

    <nav class="filter">
        <a class="tombol kecil <?= $filter === "" ? "utama" : "" ?>" href="index.php">Semua</a>
        <?php foreach ($kategori as $c): ?>
            <a class="tombol kecil <?= (string)$filter === (string)$c["id"] ? "utama" : "" ?>"
               href="index.php?kategori=<?= (int)$c["id"] ?>"><?= htmlspecialchars($c["nama"]) ?></a>
        <?php endforeach; ?>
        <a class="tombol kecil <?= $filter === "tanpa" ? "utama" : "" ?>"
           href="index.php?kategori=tanpa">Tanpa kategori</a>
        <a class="tombol kecil" href="kategori.php">Kelola kategori</a>
    </nav>

    <ul class="daftar">
        <?php foreach ($daftar as $k): ?>
            <li class="item">
                <div>
                    <strong><?= htmlspecialchars($k["inggris"]) ?></strong>
                    <?php if ($k["nama_kategori"]): ?>
                        <span class="lencana"><?= htmlspecialchars($k["nama_kategori"]) ?></span>
                    <?php endif; ?>
                    <span class="arti">= <?= htmlspecialchars($k["arti"]) ?></span>
                    <?php if ($k["contoh"]): ?>
                        <em class="contoh"><?= htmlspecialchars($k["contoh"]) ?></em>
                    <?php endif; ?>
                </div>

                <div class="aksi">
                    <a class="tombol kecil" href="edit.php?id=<?= (int)$k["id"] ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Hapus kata ini?')">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= (int)$k["id"] ?>">
                        <button class="tombol kecil bahaya">Hapus</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</main>
</body>
</html>