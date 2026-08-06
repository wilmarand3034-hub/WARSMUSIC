<?php
/*
============================================================
  ARCHIVO: index.php
  DESCRIPCIÓN: Página principal de WARSMUSIC
  FUNCIÓN: Muestra la interfaz del reproductor, el menú de
           navegación, el reproductor de audio y verifica
           si el usuario tiene sesión activa y es premium.
============================================================

  RUTAS QUE USA ESTE ARCHIVO:
  GET  → canciones.php         : trae todas las canciones
  GET  → mis_favoritos.php     : trae favoritos del usuario
  GET  → verificar_favorito.php: verifica si una canción es favorita
  GET  → verificar_premium.php : lee estado premium del usuario
  POST → registro.php          : crea un nuevo usuario
  POST → procesar.php          : inicia sesión
  POST → favorito.php          : agrega o quita un favorito
  POST → activar_premium.php   : activa o desactiva premium
  UPDATE → activar_premium.php : UPDATE usuarios SET premium
  DELETE → favorito.php        : DELETE FROM favoritos (cuando ya existe)
*/

// session_start() inicia la sesión del usuario en el servidor.
// Permite leer y escribir en $_SESSION durante toda la visita.
// SIEMPRE debe ir en la primera línea, antes de cualquier salida HTML.
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>WARSMUSIC</title>

    <!-- Hoja de estilos externa que controla todo el diseño visual -->
    <link rel="stylesheet" href="style.css">

    <!-- Ícono que aparece en la pestaña del navegador -->
    <link rel="shortcut icon" href="./imagenes/unnamed.png">

    <!-- Configuración para instalar la app en móvil (PWA) -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#00cfff">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="WARSMUSIC">
    <link rel="apple-touch-icon" href="./imagenes/unnamed.png">

    <!-- Íconos de Font Awesome (fa-play, fa-heart, etc.) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <!-- ============================================================
         VARIABLES PHP — TÍTULO Y SALUDO
         isset() verifica si la variable de sesión existe antes de
         mostrarla, evitando errores si el usuario no ha iniciado sesión.
    ============================================================ -->
    <h1>WARSMUSIC</h1>
    <!-- VARIABLE: $_SESSION['usuario'] guarda el nombre del usuario logueado -->
    <h2>¡Bienvenido<?php if(isset($_SESSION['usuario'])) echo ", " . $_SESSION['usuario']; ?>!</h2>


    <!-- ============================================================
         BUSCADOR
         El input captura texto del usuario. El <ul> se llena
         dinámicamente con JavaScript según lo que se escriba.
    ============================================================ -->
    <div class="buscador-box">
        <input type="text" id="buscador" placeholder="🔍 Buscar canción...">
        <ul id="resultados-busqueda"></ul>
    </div>


    <!-- ============================================================
         MENÚ DE NAVEGACIÓN
         Algunos elementos están ocultos por defecto (display:none)
         y se muestran en JavaScript al hacer clic en "Inicio".
    ============================================================ -->
    <nav class="navbar">
        <ul class="nav-links">
            <li><a href="#" id="btn-inicio">Inicio</a></li>
            <!-- class="menu-oculto" los marca para que JS los pueda seleccionar todos -->
            <li class="menu-oculto" style="display:none;"><a href="#" id="btn-explorar">Explorar</a></li>
            <li class="menu-oculto" style="display:none;"><a href="#" id="btn-biblioteca">Biblioteca</a></li>
            <li class="menu-oculto" style="display:none;"><a href="#" id="btn-premium">Premium</a></li>
        </ul>
    </nav>


    <!-- ============================================================
         REPRODUCTOR DE AUDIO HTML5
         El elemento <audio> es controlado completamente desde JS.
         No tiene controles nativos del navegador (los hacemos nosotros).
    ============================================================ -->
    <audio id="reproductor"></audio>


    <!-- ============================================================
         PANEL DEL REPRODUCTOR (interfaz visual)
         Contiene: nombre de la canción, botones play/anterior/siguiente,
         barra de progreso, control de volumen y botones extra.
         Está oculto (display:none) hasta que se selecciona una canción.
    ============================================================ -->
    <div class="player-controls" id="player-controls" style="display:none;">

        <!-- Botón para colapsar o expandir el reproductor -->
        <button id="btn-toggle-reproductor" class="btn-toggle">
            <i class="fas fa-chevron-down" id="icono-toggle"></i>
        </button>

        <!-- Etiqueta animada que indica que hay reproducción activa -->
        <div class="reproduciendo-label">
            <i class="fas fa-compact-disc fa-spin"></i>
            Reproduciendo ahora
        </div>

        <!-- Nombre de la canción actual — se actualiza desde JS -->
        <p id="nombre-cancion"></p>

        <!-- Botones principales de control: anterior, play/pausa, siguiente -->
        <div class="botones-principales">
            <i class="fas fa-backward" id="btn-prev"></i>
            <i class="fas fa-play"     id="btn-play"></i>
            <i class="fas fa-forward"  id="btn-next"></i>
        </div>

        <!-- BARRA DE PROGRESO
             Muestra el tiempo transcurrido y el total de la canción.
             El clic sobre ella permite saltar a ese punto del audio. -->
        <div class="progress-container">
            <span id="tiempo-actual">0:00</span>
            <div class="progress-bar" id="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            <span id="tiempo-total">0:00</span>
        </div>

        <!-- CONTROL DE VOLUMEN
             input type="range" genera un slider entre 0 y 1.
             JavaScript conecta su valor al volumen del reproductor. -->
        <div class="volume-container">
            <i class="fas fa-volume-down"></i>
            <input type="range" id="volumen" min="0" max="1" step="0.01" value="1">
            <i class="fas fa-volume-up"></i>
        </div>

        <!-- BOTONES EXTRA: Aleatorio, Repetir y Favorito -->
        <div class="extra-controls">
            <div class="extra-btn" id="btn-shuffle">
                <i class="fas fa-random"></i>
                <span>Aleatorio</span>
            </div>
            <div class="extra-btn" id="btn-repeat">
                <i class="fas fa-redo"></i>
                <span>Repetir</span>
            </div>
            <!-- Clic en el ícono corazón → guarda/quita favorito
                 Triple clic en "Favorito" (texto) → abre panel de favoritos -->
            <div class="extra-btn" id="btn-favorite">
                <i class="fas fa-heart" id="icono-favorito"></i>
                <span id="texto-favorito">Favorito</span>
            </div>
        </div>

    </div><!-- Fin player-controls -->


    <!-- ============================================================
         BOTÓN MENÚ INFERIOR
         Siempre visible. Solo funciona si el usuario es Premium.
         Abre/cierra opciones de Letra, Temporizador y Video.
    ============================================================ -->
    <button id="btn-menu-inferior" class="btn-inferior">
        <i class="fas fa-chevron-up" id="icono-inferior"></i>
    </button>

    <!-- MENÚ INFERIOR — opciones exclusivas para usuarios Premium -->
    <div id="menu-inferior" style="display:none;">
        <div class="menu-inferior-item" id="opc-letra">
            <i class="fas fa-microphone"></i> Letra
        </div>
        <div class="menu-inferior-item" id="opc-temporizador">
            <i class="fas fa-clock"></i> Temporizador
        </div>
        <div class="menu-inferior-item" id="opc-video">
            <i class="fas fa-film"></i> Video
        </div>
    </div>


    <!-- ============================================================
         BOTÓN DE SESIÓN (esquina superior derecha)
         SENTENCIA CONDICIONAL PHP:
         - Si hay sesión activa → muestra "Cerrar sesión"
         - Si no hay sesión     → muestra "Iniciar sesión"
    ============================================================ -->
    <div class="login-box">
        <?php if (isset($_SESSION['usuario'])): ?>
            <a href="cerrar_sesion.php" id="btn-login">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        <?php else: ?>
            <a href="login.php" id="btn-login">
                <i class="fas fa-user"></i> Iniciar sesión
            </a>
        <?php endif; ?>
    </div>


    <!-- Acceso directo a la tienda de planes Premium -->
    <div style="position:fixed;bottom:80px;left:20px;z-index:9999;">
        <a href="tienda.php" style="display:inline-block;padding:10px 18px;background:rgba(0,207,255,0.15);border:1px solid #00cfff;border-radius:20px;color:#00cfff;text-decoration:none;font-size:0.8rem;font-family:Arial;letter-spacing:1px;">
            🛒 Planes
        </a>
    </div>


    <!-- ============================================================
         PANEL DE FAVORITOS
         Oculto por defecto. Se muestra con triple clic en "Favorito".
         La lista se llena dinámicamente desde JavaScript.
    ============================================================ -->
    <section id="panel-favoritos" style="display: none;">
        <h2><i class="fas fa-heart" style="color:#ff4444;"></i> Mis Favoritos</h2>
        <ul id="lista-favoritos"></ul>
    </section>


    <!-- ============================================================
         BIBLIOTECA
         Lista de canciones de la base de datos.
         JavaScript la llena al cargar la página (fetch a canciones.php).
    ============================================================ -->
    <section id="biblioteca" style="display: none;">
        <h2>Mi Biblioteca</h2>
        <ul></ul>
    </section>


    <!-- ============================================================
         SIDEBAR PREMIUM
         Panel lateral que muestra beneficios y opciones según el
         estado del usuario: con Premium o sin Premium.
         Tiene overlay (fondo oscuro) y panel con header/body/footer.
    ============================================================ -->
    <div id="sidebar-premium">
        <div id="sidebar-overlay"></div>
        <div id="sidebar-panel">
            <div id="sidebar-header">
                <!-- Logo SVG animado de WARSMUSIC -->
                <svg width="26" height="26" viewBox="0 0 130 130">
                    <defs><filter id="gls"><feGaussianBlur stdDeviation="2.5" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>
                    <rect x="6"  y="52" width="5" height="26" rx="2.5" fill="#00cfff" opacity="0.5"/>
                    <rect x="14" y="44" width="5" height="42" rx="2.5" fill="#00cfff" opacity="0.7"/>
                    <rect x="22" y="36" width="5" height="58" rx="2.5" fill="#00cfff" opacity="0.9"/>
                    <rect x="30" y="44" width="5" height="42" rx="2.5" fill="#00cfff" opacity="0.7"/>
                    <rect x="38" y="52" width="5" height="26" rx="2.5" fill="#00cfff" opacity="0.5"/>
                    <rect x="87"  y="52" width="5" height="26" rx="2.5" fill="#00cfff" opacity="0.5"/>
                    <rect x="95"  y="44" width="5" height="42" rx="2.5" fill="#00cfff" opacity="0.7"/>
                    <rect x="103" y="36" width="5" height="58" rx="2.5" fill="#00cfff" opacity="0.9"/>
                    <rect x="111" y="44" width="5" height="42" rx="2.5" fill="#00cfff" opacity="0.7"/>
                    <rect x="119" y="52" width="5" height="26" rx="2.5" fill="#00cfff" opacity="0.5"/>
                    <polyline points="72,8 47,65 63,65 53,122" fill="none" stroke="#00cfff" stroke-width="5" stroke-linejoin="round" stroke-linecap="round" filter="url(#gls)"/>
                    <polyline points="72,8 84,8 72,57 91,57 53,122" fill="none" stroke="#8A2BE2" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" opacity="0.8"/>
                </svg>
                <div>
                    <p id="sidebar-title">Premium</p>
                    <p id="sidebar-sub">WARSMUSIC</p>
                </div>
                <span id="sidebar-cerrar">✕</span>
            </div>
            <!-- El contenido del body y footer se genera dinámicamente desde JS -->
            <div id="sidebar-body"></div>
            <div id="sidebar-footer"></div>
        </div>
    </div>


    <!-- ============================================================
         FONDO ANIMADO
         Esferas que suben con animación CSS.
         pointer-events:none asegura que no bloqueen los clics.
    ============================================================ -->
    <div class="area" style="pointer-events: none; z-index: 0;">
        <ul class="circulos" style="pointer-events: none;">
            <li></li><li></li><li></li><li></li><li></li>
            <li></li><li></li><li></li><li></li><li></li>
            <li></li><li></li><li></li><li></li><li></li>
            <li></li><li></li><li></li><li></li><li></li>
        </ul>
    </div>


    <!-- ============================================================
         VARIABLES JAVASCRIPT GENERADAS DESDE PHP
         PHP inyecta datos del servidor directamente en JS para que
         el frontend los use sin hacer peticiones adicionales.

         VARIABLE usuarioActivo: string con el nombre del usuario,
         o null si no hay sesión. Se usa en JS para saber si hay login.

         VARIABLE esPremium: booleano true/false.
         Se hace una CONSULTA SQL (SELECT premium FROM usuarios)
         para verificar si el usuario actual tiene premium activado.
    ============================================================ -->
    <script>
        // VARIABLE: nombre del usuario activo (viene de PHP/sesión)
        const usuarioActivo = <?php echo isset($_SESSION['usuario']) ? '"' . $_SESSION['usuario'] . '"' : 'null'; ?>;

        // VARIABLE: estado premium (viene de consulta SQL en tiempo real)
        const esPremium = <?php
            if(isset($_SESSION['usuario'])) {
                // CONSULTA SQL — SELECT: lee el campo premium del usuario
                include "conexion.php";
                $stmt = $conn->prepare("SELECT premium FROM usuarios WHERE username = ?");
                $stmt->bind_param("s", $_SESSION['usuario']); // enlaza el parámetro de forma segura
                $stmt->execute();
                $stmt->bind_result($prem);
                $stmt->fetch();
                echo $prem ? 'true' : 'false'; // convierte 0/1 a booleano JS
            } else {
                echo 'false'; // si no hay sesión, no es premium
            }
        ?>;
    </script>


    <!-- ============================================================
         ARCHIVO JAVASCRIPT PRINCIPAL
         defer: el script se carga después de que el HTML esté listo,
         garantizando que todos los elementos del DOM existan
         cuando JS intente acceder a ellos.
    ============================================================ -->
    <script src="rep_music.js" defer></script>


    <!-- ============================================================
         ÍCONO DE INFORMACIÓN
         Abre un modal con la descripción de WARSMUSIC.
    ============================================================ -->
    <div class="info-box">
        <i class="fas fa-info-circle" id="btn-info"></i>
    </div>

    <!-- MODAL DE INFORMACIÓN -->
    <div id="modal-info" class="modal">
        <div class="modal-content">
            <h3>¿Qué es WARSMUSIC?</h3>
            <p id="texto-info"></p>
            <button onclick="cerrarInfo()">Cerrar</button>
        </div>
    </div>


    <!-- Registro del Service Worker para funcionamiento offline (PWA) -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>

</body>
</html>