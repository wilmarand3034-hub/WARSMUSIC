<?php
session_start();
include "conexion.php";

header("Content-Type: application/json");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No hay sesión"]);
    exit();
}

$usuario = $_SESSION['usuario'];

$stmt = $conn->prepare("SELECT cancion, fecha FROM favoritos WHERE usuario = ? ORDER BY fecha DESC");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();

$favoritos = [];
while ($fila = $resultado->fetch_assoc()) {
    $favoritos[] = $fila;
}

echo json_encode($favoritos);
$stmt->close();
$conn->close();
?>