<?php
$pdo = new PDO(
    "mysql:host=localhost;dbname=flashcard;charset=utf8mb4",
    "belajar",
    "passwordku"
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);