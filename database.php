<?php

$host = "localhost";
$db = "beautystore";
$user = "beautyuser";
$pass = "1234";

try {

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {

die("DB ERROR: " . $e->getMessage());

}