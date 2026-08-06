<?php
session_start();
include "conexion.php";

header("Content-Type: application/json");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["favorito" => false]);
    exit();
}

$usuario = $_SESSION['usuario'];
$cancion = $_GET['cancion'];

$stmt = $conn->prepare("SELECT id FROM favoritos WHERE usuario = ? AND cancion = ?");
$stmt->bind_param("ss", $usuario, $cancion);
$stmt->execute();
$stmt->store_result();

echo json_encode(["favorito" => $stmt->num_rows > 0]);
$stmt->close();
$conn->close();
?>