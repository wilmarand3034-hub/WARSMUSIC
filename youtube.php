<?php
session_start();
include "conexion.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT premium FROM usuarios WHERE username = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$stmt->bind_result($prem);
$stmt->fetch();
$stmt->close();
$conn->close();

if (!$prem) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WARSMUSIC — Buscar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="./imagenes/unnamed.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #1e1e2f;
            color: white;
            min-height: 100vh;
            overflow-x: hidden;
        }

        h1.titulo-wars {
            font-size: 2rem;
            text-align: center;
            color: #00cfff;
            text-shadow: 0 0 10px #00cfff, 0 0 30px #00cfff;
            letter-spacing: 6px;
            padding: 24px 0 8px;
            animation: parpadeo 2.5s infinite alternate;
        }

        @keyframes parpadeo {
            from { text-shadow: 0 0 10px #00cfff, 0 0 30px #00cfff; }
            to   { text-shadow: 0 0 20px #00cfff, 0 0 60px #00cfff, 0 0 100px #00cfff; }
        }

        .nav-bar {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 10px 0 20px;
        }

        .nav-bar a {
            color: #aaa;
            text-decoration: none;
            font-size: 0.9rem;
            letter-spacing: 1px;
            transition: color 0.3s;
        }

        .nav-bar a:hover, .nav-bar a.active { color: #00cfff; }

        /* BUSCADOR */
        .search-box {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 0 20px 30px;
        }

        .search-box input {
            width: 100%;
            max-width: 500px;
            padding: 12px 20px;
            background: rgba(0,0,0,0.35);
            border: 1px solid rgba(0, 207, 255, 0.3);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .search-box input:focus {
            border-color: #00cfff;
            box-shadow: 0 0 10px rgba(0,207,255,0.3);
        }

        .search-box input::placeholder { color: #555; }

        .btn-buscar {
            padding: 12px 24px;
            background: transparent;
            border: 1px solid #00cfff;
            border-radius: 8px;
            color: #00cfff;
            font-size: 0.95rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s, color 0.3s;
            white-space: nowrap;
        }

        .btn-buscar:hover { background: #00cfff; color: #1e1e2f; }

        /* REPRODUCTOR */
        #player-container {
            display: none;
            max-width: 800px;
            margin: 0 auto 30px;
            padding: 0 20px;
        }

        #player-container h3 {
            color: #00cfff;
            font-size: 1rem;
            margin-bottom: 10px;
            text-align: center;
            letter-spacing: 1px;
        }

        #youtube-player {
            width: 100%;
            aspect-ratio: 16/9;
            border-radius: 12px;
            border: 1px solid rgba(0,207,255,0.3);
            overflow: hidden;
        }

        /* RESULTADOS */
        #resultados {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        .card {
            background: rgba(8, 99, 127, 0.2);
            border: 1px solid rgba(0,207,255,0.2);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: border-color 0.3s, transform 0.2s;
        }

        .card:hover {
            border-color: #00cfff;
            transform: translateY(-3px);
        }

        .card img {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
        }

        .card-info {
            padding: 10px 12px;
        }

        .card-info h4 {
            font-size: 0.82rem;
            color: #fff;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-info p {
            font-size: 0.75rem;
            color: #aaa;
            margin-top: 4px;
        }

        .card.activa {
            border-color: #00cfff;
            box-shadow: 0 0 12px rgba(0,207,255,0.4);
        }

        /* LOADING */
        #loading {
            display: none;
            text-align: center;
            color: #aaa;
            padding: 20px;
            font-size: 0.9rem;
        }

        #sin-resultados {
            display: none;
            text-align: center;
            color: #aaa;
            padding: 20px;
            font-size: 0.9rem;
        }

        /* LOGIN BOX */
        .login-box {
            position: fixed;
            top: 16px;
            right: 20px;
            z-index: 100;
        }

        .login-box a {
            color: #00cfff;
            text-decoration: none;
            font-size: 0.85rem;
            border: 1px solid rgba(0,207,255,0.4);
            padding: 6px 14px;
            border-radius: 20px;
            transition: background 0.3s;
        }

        .login-box a:hover { background: rgba(0,207,255,0.1); }

        /* FONDO */
        .area { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; overflow: hidden; }
        .circulos { list-style: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .circulos li { position: absolute; bottom: -150px; background: rgba(0,207,255,0.05); border: 1px solid rgba(0,207,255,0.1); border-radius: 50%; animation: subir linear infinite; }
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

    <!-- LOGIN -->
    <div class="login-box">
        <?php if (isset($_SESSION['usuario'])): ?>
            <a href="cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
        <?php else: ?>
            <a href="login.php"><i class="fas fa-user"></i> Iniciar sesión</a>
        <?php endif; ?>
    </div>

    <h1 class="titulo-wars">WARSMUSIC</h1>

    <nav class="nav-bar">
        <a href="index.php">Inicio</a>
        <a href="youtube.php" class="active">Buscar videos</a>
    </nav>

    <!-- BUSCADOR -->
    <div class="search-box">
        <input type="text" id="input-buscar" placeholder="🔍 Busca una canción o artista..." />
        <button class="btn-buscar" onclick="buscar()">
            <i class="fas fa-search"></i> Buscar
        </button>
    </div>

    <!-- REPRODUCTOR -->
    <div id="player-container">
        <h3 id="titulo-reproduciendo"><i class="fas fa-compact-disc fa-spin"></i> Reproduciendo ahora</h3>
        <iframe id="youtube-player" src="" frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen>
        </iframe>
    </div>

    <!-- LOADING -->
    <div id="loading"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>
    <div id="sin-resultados">No se encontraron resultados.</div>

    <!-- RESULTADOS -->
    <div id="resultados"></div>

    <script>
        const API_KEY = "AIzaSyDYa-zAdtEYxe4NrQ_5xeNkHBL8jXtE6yU";

        document.getElementById("input-buscar").addEventListener("keydown", function(e) {
            if (e.key === "Enter") buscar();
        });

        async function buscar() {
            const query = document.getElementById("input-buscar").value.trim();
            if (!query) return;

            const resultados = document.getElementById("resultados");
            const loading = document.getElementById("loading");
            const sinResultados = document.getElementById("sin-resultados");

            resultados.innerHTML = "";
            sinResultados.style.display = "none";
            loading.style.display = "block";

            try {
                const url = `https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(query)}&type=video&maxResults=12&key=${API_KEY}`;
                const res = await fetch(url);
                const data = await res.json();

                loading.style.display = "none";

                if (!data.items || data.items.length === 0) {
                    sinResultados.style.display = "block";
                    return;
                }

                data.items.forEach(item => {
                    const videoId = item.id.videoId;
                    const titulo = item.snippet.title;
                    const canal = item.snippet.channelTitle;
                    const thumb = item.snippet.thumbnails.medium.url;

                    const card = document.createElement("div");
                    card.className = "card";
                    card.innerHTML = `
                        <img src="${thumb}" alt="${titulo}">
                        <div class="card-info">
                            <h4>${titulo}</h4>
                            <p>${canal}</p>
                        </div>
                    `;
                    card.onclick = () => reproducir(videoId, titulo, card);
                    resultados.appendChild(card);
                });

            } catch (err) {
                loading.style.display = "none";
                sinResultados.textContent = "Error al buscar. Intenta de nuevo.";
                sinResultados.style.display = "block";
            }
        }

        function reproducir(videoId, titulo, card) {
            document.querySelectorAll(".card").forEach(c => c.classList.remove("activa"));
            card.classList.add("activa");

            const player = document.getElementById("youtube-player");
            const container = document.getElementById("player-container");
            const tituloEl = document.getElementById("titulo-reproduciendo");

            player.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            tituloEl.innerHTML = `<i class="fas fa-compact-disc fa-spin"></i> ${titulo}`;
            container.style.display = "block";

            container.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    </script>

</body>
</html>