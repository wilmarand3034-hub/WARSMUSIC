<?php
session_start();
include "conexion.php";

header("Content-Type: application/json");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["error" => "No hay sesión"]);
    exit();
}

$usuarioActual = $_SESSION['usuario'];

// Validar que lleguen los campos requeridos
if (!isset($_POST['campo'], $_POST['valor'], $_POST['password_confirm'])) {
    echo json_encode(["error" => "Datos incompletos"]);
    exit();
}

$campo           = $_POST['campo'];
$valor           = trim($_POST['valor']);
$password_confirm = $_POST['password_confirm'];

// Lista blanca de campos permitidos
$camposPermitidos = ["username", "password", "documento"];
if (!in_array($campo, $camposPermitidos)) {
    echo json_encode(["error" => "Campo no válido"]);
    exit();
}

// Verificar contraseña actual
$stmt = $conn->prepare("SELECT password FROM usuarios WHERE username = ?");
$stmt->bind_param("s", $usuarioActual);
$stmt->execute();
$stmt->bind_result($hashGuardado);
$stmt->fetch();
$stmt->close();

if (!$hashGuardado || !password_verify($password_confirm, $hashGuardado)) {
    echo json_encode(["error" => "Contraseña actual incorrecta"]);
    exit();
}

if (empty($valor)) {
    echo json_encode(["error" => "El valor no puede estar vacío"]);
    exit();
}

if ($campo === "username") {
    $stmt = $conn->prepare("UPDATE usuarios SET username = ? WHERE username = ?");
    $stmt->bind_param("ss", $valor, $usuarioActual);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $_SESSION['usuario'] = $valor;
        $stmt->close();
        $conn->close();
        echo json_encode(["ok" => true, "msg" => "Usuario actualizado", "nuevoUsuario" => $valor]);
    } else {
        $stmt->close();
        $conn->close();
        echo json_encode(["error" => "Ese usuario ya existe o no hubo cambios"]);
    }

} elseif ($campo === "password") {
    if (strlen($valor) < 4) {
        echo json_encode(["error" => "Mínimo 4 caracteres"]);
        exit();
    }
    $hash = password_hash($valor, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hash, $usuarioActual);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(["ok" => true, "msg" => "Contraseña actualizada"]);

} elseif ($campo === "documento") {
    $doc = (int)$valor;
    if ($doc <= 0) {
        echo json_encode(["error" => "Documento inválido"]);
        exit();
    }
    $stmt = $conn->prepare("UPDATE usuarios SET id_usuario = ? WHERE username = ?");
    $stmt->bind_param("is", $doc, $usuarioActual);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $conn->close();
        echo json_encode(["ok" => true, "msg" => "Documento actualizado"]);
    } else {
        $stmt->close();
        $conn->close();
        echo json_encode(["error" => "Ese documento ya existe o no hubo cambios"]);
    }
}
?>