<?php
session_start();
include "conexion.php";

header("Content-Type: application/json");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No hay sesión"]);
    exit();
}

$usuario = $_SESSION['usuario'];
$accion = $_POST['accion']; // "activar" o "quitar"

$valor = ($accion === "activar") ? 1 : 0;

$stmt = $conn->prepare("UPDATE usuarios SET premium = ? WHERE username = ?");
$stmt->bind_param("is", $valor, $usuario);
$stmt->execute();
$stmt->close();

// Leer el valor real desde la BD para confirmar
$stmt2 = $conn->prepare("SELECT premium FROM usuarios WHERE username = ?");
$stmt2->bind_param("s", $usuario);
$stmt2->execute();
$stmt2->bind_result($premiumActual);
$stmt2->fetch();
$stmt2->close();
$conn->close();

echo json_encode([
    "estado"   => $accion === "activar" ? "activado" : "quitado",
    "premium"  => (bool)$premiumActual
]);
?>