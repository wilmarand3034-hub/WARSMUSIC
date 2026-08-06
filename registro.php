<?php
include "conexion.php";

$mensaje = "";
$tipo = "";

if (isset($_GET["mensaje"])) {
    $mensaje = $_GET["mensaje"];
    $tipo = isset($_GET["tipo"]) ? $_GET["tipo"] : "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $documento = (int)trim($_POST['documento']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmar = $_POST['confirmar'];

    if (empty($documento) || empty($username) || empty($password)) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo = "error";
    } elseif ($password !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo = "error";
    } elseif (strlen($password) < 4) {
        $mensaje = "La contraseña debe tener mínimo 4 caracteres.";
        $tipo = "error";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO usuarios (id_usuario, username, password) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $documento, $username, $hash);

        if ($stmt->execute()) {
            session_start();
            $_SESSION['usuario'] = $username;
            header("Location: index.php");
            exit();
        } else {
            $mensaje = "Ese documento o usuario ya existe.";
            $tipo = "error";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WARSMUSIC — Registro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="./imagenes/unnamed.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #1e1e2f;
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        h1.titulo-wars {
            font-size: 2.5rem;
            text-align: center;
            color: #00cfff;
            text-shadow: 0 0 10px #00cfff, 0 0 30px #00cfff, 0 0 60px #00cfff;
            letter-spacing: 6px;
            margin-bottom: 8px;
            animation: parpadeo 2.5s infinite alternate;
        }
        p.subtitulo { text-align: center; color: #aaa; font-size: 0.9rem; margin-bottom: 30px; letter-spacing: 2px; }
        @keyframes parpadeo {
            from { text-shadow: 0 0 10px #00cfff, 0 0 30px #00cfff; }
            to   { text-shadow: 0 0 20px #00cfff, 0 0 60px #00cfff, 0 0 100px #00cfff; }
        }
        .registro-container {
            background: rgba(8, 99, 127, 0.25);
            border: 1px solid rgba(0, 207, 255, 0.3);
            backdrop-filter: blur(10px);
            padding: 36px 40px;
            border-radius: 16px;
            width: 340px;
            box-shadow: 0 0 30px rgba(0, 207, 255, 0.15), 0 8px 32px rgba(0,0,0,0.5);
            position: relative;
            z-index: 10;
        }
        .registro-container h2 { text-align: center; margin-bottom: 24px; font-size: 1.2rem; color: #00cfff; letter-spacing: 3px; text-transform: uppercase; }
        .campo { margin-bottom: 18px; }
        .campo label { display: block; font-size: 0.78rem; color: #aaa; margin-bottom: 6px; letter-spacing: 1px; text-transform: uppercase; }
        .input-wrap { position: relative; display: flex; align-items: center; }
        .input-wrap i { position: absolute; left: 12px; color: #00cfff; font-size: 0.85rem; }
        .campo input {
            width: 100%;
            padding: 10px 12px 10px 36px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(0, 207, 255, 0.25);
            border-radius: 8px;
            color: white;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .campo input:focus { border-color: #00cfff; box-shadow: 0 0 10px rgba(0, 207, 255, 0.3); }
        .campo input::placeholder { color: #555; }
        .btn-registro {
            width: 100%; padding: 12px; margin-top: 6px;
            background: transparent; border: 1px solid #00cfff; border-radius: 8px;
            color: #00cfff; font-size: 0.95rem; font-weight: bold;
            letter-spacing: 2px; text-transform: uppercase; cursor: pointer;
            transition: background 0.3s, box-shadow 0.3s, color 0.3s;
        }
        .btn-registro:hover { background: #00cfff; color: #1e1e2f; box-shadow: 0 0 20px rgba(0, 207, 255, 0.5); }
        .mensaje-box { margin-top: 16px; padding: 10px 14px; background: rgba(255,255,255,0.05); border-left: 3px solid #00cfff; border-radius: 6px; font-size: 0.85rem; color: #ccc; text-align: center; }
        .mensaje-box.error { border-left-color: #ff4444; color: #ff8888; }
        .ir-login { text-align: center; margin-top: 16px; font-size: 0.82rem; color: #aaa; }
        .ir-login a { color: #00cfff; text-decoration: none; }
        .ir-login a:hover { text-decoration: underline; }
        .area { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; overflow: hidden; }
        .circulos { list-style: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .circulos li { position: absolute; bottom: -150px; background: rgba(0, 207, 255, 0.07); border: 1px solid rgba(0, 207, 255, 0.15); border-radius: 50%; animation: subir linear infinite; }
        @keyframes subir {
            0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(-110vh) rotate(720deg); opacity: 0; }
        }
        .circulos li:nth-child(1)  { width:80px;  height:80px;  left:10%;  animation-duration:12s; animation-delay:0s; }
        .circulos li:nth-child(2)  { width:30px;  height:30px;  left:20%;  animation-duration:8s;  animation-delay:2s; }
        .circulos li:nth-child(3)  { width:50px;  height:50px;  left:35%;  animation-duration:10s; animation-delay:4s; }
        .circulos li:nth-child(4)  { width:100px; height:100px; left:50%;  animation-duration:15s; animation-delay:0s; }
        .circulos li:nth-child(5)  { width:20px;  height:20px;  left:65%;  animation-duration:7s;  animation-delay:3s; }
        .circulos li:nth-child(6)  { width:60px;  height:60px;  left:75%;  animation-duration:11s; animation-delay:1s; }
        .circulos li:nth-child(7)  { width:40px;  height:40px;  left:85%;  animation-duration:9s;  animation-delay:5s; }
        .circulos li:nth-child(8)  { width:70px;  height:70px;  left:5%;   animation-duration:13s; animation-delay:2s; }
        .circulos li:nth-child(9)  { width:25px;  height:25px;  left:45%;  animation-duration:6s;  animation-delay:1s; }
        .circulos li:nth-child(10) { width:90px;  height:90px;  left:90%;  animation-duration:14s; animation-delay:0s; }
    </style>
</head>
<body>
    <div class="area">
        <ul class="circulos">
            <li></li><li></li><li></li><li></li><li></li>
            <li></li><li></li><li></li><li></li><li></li>
        </ul>
    </div>

    <h1 class="titulo-wars">WARSMUSIC</h1>
    <p class="subtitulo">Crea tu cuenta</p>

    <div class="registro-container">
        <h2><i class="fas fa-user-plus" style="margin-right:8px;"></i>Registro</h2>

        <form method="post" action="registro.php">

            <div class="campo">
                <label for="documento">Documento</label>
                <div class="input-wrap">
                    <i class="fas fa-id-card"></i>
                    <input type="number" id="documento" name="documento" placeholder="Tu número de documento" required>
                </div>
            </div>

            <div class="campo">
                <label for="username">Usuario</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Elige un usuario" required>
                </div>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="campo">
                <label for="confirmar">Confirmar contraseña</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirmar" name="confirmar" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-registro">
                <i class="fas fa-user-plus" style="margin-right:6px;"></i>Registrarse
            </button>

        </form>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje-box <?php echo $tipo === 'error' ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <p class="ir-login">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </p>
    </div>
</body>
</html>