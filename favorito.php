<?php
session_start();
include "conexion.php";

header("Content-Type: application/json");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No hay sesión"]);
    exit();
}

$usuario = $_SESSION['usuario'];
$cancion = $_POST['cancion'];

// Verificar si ya existe
$check = $conn->prepare("SELECT id FROM favoritos WHERE usuario = ? AND cancion = ?");
$check->bind_param("ss", $usuario, $cancion);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Ya existe — la quitamos
    $stmt = $conn->prepare("DELETE FROM favoritos WHERE usuario = ? AND cancion = ?");
    $stmt->bind_param("ss", $usuario, $cancion);
    $stmt->execute();
    echo json_encode(["estado" => "quitado"]);
} else {
    // No existe — la agregamos
    $stmt = $conn->prepare("INSERT INTO favoritos (usuario, cancion) VALUES (?, ?)");
    $stmt->bind_param("ss", $usuario, $cancion);
    $stmt->execute();
    echo json_encode(["estado" => "agregado"]);
}

$stmt->close();
$conn->close();
?>