<?php
require "koneksi.php";

$kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $kid = ($_POST["kategori_id"] ?? "") !== "" ? (int)$_POST["kategori_id"] : null;
    $stmt = $pdo->prepare(
        "UPDATE kata SET inggris = ?, arti = ?, contoh = ?, kategori_id = ? WHERE id = ?"
    );
    $stmt->execute([
        $_POST["inggris"], $_POST["arti"], $_POST["contoh"], $kid, $_POST["id"]
    ]);
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kata WHERE id = ?");
$stmt->execute([$_GET["id"] ?? 0]);
$k = $stmt->fetch();

if (!$k){
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kata</title>
</head>
<body>
<main class="wadah>
    <h1>Edit Kata</h1>
    <form method="post">
        <input type="hidden" name="id" value="<?= (int)$k["id"]?>">
        <input name="inggris" value="<?= htmlspecialchars($k["inggris"])?>">
        <input name="arti" value="<?= htmlspecialchars($k["arti"])?>">
        <input name="contoh" value="<?= htmlspecialchars($k["contoh"])?>">
        <select name="kategori_id">
        <option value="">Tanpa kategori</option>
            <?php foreach ($kategori as $c): ?>
                <option value="<?= (int)$c["id"] ?>"
                    <?= (int)$k["kategori_id"] === (int)$c["id"] ? "selected" : "" ?>>
                    <?= htmlspecialchars($c["nama"]) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button>Simpan</button>
    </form>
    <p><a href="index.php"></a></p>
</main>
</body>
</html>