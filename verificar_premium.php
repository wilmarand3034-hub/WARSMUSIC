<?php
session_start();
include "conexion.php";

header("Content-Type: application/json");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["premium" => false]);
    exit();
}

$usuario = $_SESSION['usuario'];

$stmt = $conn->prepare("SELECT premium FROM usuarios WHERE username = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->bind_result($premium);
$stmt->fetch();
$stmt->close();
$conn->close();

echo json_encode(["premium" => (bool)$premium]);
?>