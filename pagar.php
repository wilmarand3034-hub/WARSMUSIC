<?php
session_start();
include "conexion.php";

header("Content-Type: application/json");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No hay sesión activa"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Método no permitido"]);
    exit();
}

$usuario = $_SESSION['usuario'];
$plan    = $_POST['plan']   ?? '';
$precio  = (float)($_POST['precio'] ?? 0);

$planesValidos = ['free', 'mensual', 'anual'];
if (!in_array($plan, $planesValidos)) {
    echo json_encode(["error" => "Plan no válido"]);
    exit();
}

// Plan free — no activa premium
if ($plan === 'free') {
    echo json_encode(["ok" => true, "msg" => "Sigues con el plan gratuito."]);
    exit();
}

// Activar premium en usuarios
$stmt = $conn->prepare("UPDATE usuarios SET premium = 1 WHERE username = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->close();

// Guardar pedido en historial
$stmt2 = $conn->prepare("INSERT INTO pedidos (usuario, plan, precio) VALUES (?, ?, ?)");
$stmt2->bind_param("ssd", $usuario, $plan, $precio);
$stmt2->execute();
$stmt2->close();

$conn->close();

$mensajes = [
    'mensual' => '¡Plan Premium Mensual activado! Disfruta YouTube integrado y más.',
    'anual'   => '¡Plan Premium Anual activado! Tienes acceso completo por un año.',
];

echo json_encode([
    "ok"  => true,
    "msg" => $mensajes[$plan] ?? "Plan activado correctamente."
]);
?>
