<?php
session_start();
include "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $documento = (int)$_POST['documento'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT username, password FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $documento);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($username, $hashGuardado);
        $stmt->fetch();

        if (password_verify($password, $hashGuardado)) {
            $_SESSION['usuario'] = $username;
            header("Location: index.php");
            exit();
        } else {
            $mensaje = urlencode("Documento o contraseña incorrectos.");
        }
    } else {
        $mensaje = urlencode("Documento o contraseña incorrectos.");
    }

    $stmt->close();
    $conn->close();

    header("Location: login.php?mensaje=$mensaje");
    exit();
}
?>