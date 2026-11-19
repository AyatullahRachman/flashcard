<?php
require "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (($_POST["aksi"] ?? "") === "hapus") {
        $stmt = $pdo->prepare("DELETE FROM kata WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO kata (inggris, arti, contoh) VALUES (?, ?, ?)"
        );
        $stmt->execute([$_POST["inggris"], $_POST["arti"], $_POST["contoh"]]);
    }
    header("Location: index.php");
    exit;
}

$daftar = $pdo->query("SELECT * FROM kata ORDER BY id DESC")->fetchAll();
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
    <h1>Kartu Hafalan Kosakata</h1>
    <p><a href="kartu.php">Mulai belajar</a></p>

    <form method="post">
        <input name="inggris" placeholder="Kata (English)" required>
        <input name="arti" placeholder="Arti" required>
        <input name="contoh" placeholder="Contoh kalimat" required>
        <button>Tambah</button>
    </form>

    <h2>Daftar Kata (<?= count($daftar) ?>)</h2>
    <ul>
        <?php foreach ($daftar as $k): ?>
            <li>
                <strong><?= htmlspecialchars($k["inggris"]) ?></strong>
                = <?= htmlspecialchars($k["arti"]) ?>
                <?php if ($k["contoh"]): ?>
                    <em>(<?= htmlspecialchars($k["contoh"]) ?>)</em>
                <?php endif; ?>

                <a href="edit.php?id=<?= (int)$k["id"] ?>">Edit</a>

                <form method="post" style="display:inline"
                      onsubmit="return confirm('Hapus kata ini?')">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= (int)$k["id"] ?>">
                    <button>Hapus</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>