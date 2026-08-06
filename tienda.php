<?php
session_start();
include "conexion.php";

// Leer plan actual del usuario
$planActual = "free";
$esPremium  = false;

if (isset($_SESSION['usuario'])) {
    $stmt = $conn->prepare("SELECT premium FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $stmt->bind_result($prem);
    $stmt->fetch();
    $stmt->close();
    $esPremium  = (bool)$prem;
    $planActual = $esPremium ? "premium" : "free";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WARSMUSIC — Planes</title>
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

        /* FONDO */
        .area { position: fixed; top:0; left:0; width:100%; height:100%; z-index:0; pointer-events:none; overflow:hidden; }
        .circulos { list-style:none; position:absolute; top:0; left:0; width:100%; height:100%; }
        .circulos li { position:absolute; bottom:-150px; background:rgba(0,207,255,0.05); border:1px solid rgba(0,207,255,0.1); border-radius:50%; animation:subir linear infinite; }
        @keyframes subir { 0%{transform:translateY(0) rotate(0deg);opacity:1} 100%{transform:translateY(-110vh) rotate(720deg);opacity:0} }
        .circulos li:nth-child(1){width:80px;height:80px;left:10%;animation-duration:12s;animation-delay:0s}
        .circulos li:nth-child(2){width:30px;height:30px;left:20%;animation-duration:8s;animation-delay:2s}
        .circulos li:nth-child(3){width:50px;height:50px;left:35%;animation-duration:10s;animation-delay:4s}
        .circulos li:nth-child(4){width:100px;height:100px;left:50%;animation-duration:15s;animation-delay:0s}
        .circulos li:nth-child(5){width:20px;height:20px;left:65%;animation-duration:7s;animation-delay:3s}
        .circulos li:nth-child(6){width:60px;height:60px;left:75%;animation-duration:11s;animation-delay:1s}

        /* HEADER */
        .page-header {
            position: relative; z-index: 10;
            text-align: center;
            padding: 40px 20px 10px;
        }

        h1.titulo {
            font-size: 2rem; color: #00cfff;
            text-shadow: 0 0 10px #00cfff, 0 0 30px #00cfff;
            letter-spacing: 6px;
            animation: parpadeo 2.5s infinite alternate;
        }

        @keyframes parpadeo {
            from { text-shadow: 0 0 10px #00cfff, 0 0 30px #00cfff; }
            to   { text-shadow: 0 0 20px #00cfff, 0 0 60px #00cfff, 0 0 100px #00cfff; }
        }

        .subtitulo {
            color: #aaa; font-size: 0.85rem;
            margin-top: 6px; letter-spacing: 2px;
        }

        /* NAV */
        .nav-bar {
            position: relative; z-index: 10;
            display: flex; justify-content: center;
            gap: 20px; padding: 14px 0 0;
        }
        .nav-bar a {
            color: #aaa; text-decoration: none;
            font-size: 0.85rem; letter-spacing: 1px;
            transition: color 0.3s;
        }
        .nav-bar a:hover, .nav-bar a.active { color: #00cfff; }

        /* LOGIN */
        .login-box {
            position: fixed; top: 16px; right: 20px; z-index: 100;
        }
        .login-box a {
            color: #00cfff; text-decoration: none;
            font-size: 0.85rem; border: 1px solid rgba(0,207,255,0.4);
            padding: 6px 14px; border-radius: 20px; transition: background 0.3s;
        }
        .login-box a:hover { background: rgba(0,207,255,0.1); }

        /* PLANES */
        .planes-container {
            position: relative; z-index: 10;
            display: flex; justify-content: center;
            flex-wrap: wrap; gap: 24px;
            padding: 40px 20px 60px;
            max-width: 960px; margin: 0 auto;
        }

        .plan-card {
            background: rgba(8, 99, 127, 0.15);
            border: 1px solid rgba(0,207,255,0.2);
            border-radius: 18px;
            padding: 32px 28px;
            width: 260px;
            display: flex; flex-direction: column;
            align-items: center; gap: 12px;
            transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
            backdrop-filter: blur(8px);
        }

        .plan-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 0 30px rgba(0,207,255,0.2);
            border-color: rgba(0,207,255,0.5);
        }

        .plan-card.destacado {
            border-color: #00cfff;
            box-shadow: 0 0 24px rgba(0,207,255,0.25);
            background: rgba(0,207,255,0.08);
        }

        .plan-card.actual {
            border-color: #00ff99;
            box-shadow: 0 0 20px rgba(0,255,153,0.2);
        }

        .badge-popular {
            background: #00cfff; color: #1e1e2f;
            font-size: 9px; font-weight: bold;
            padding: 3px 10px; border-radius: 20px;
            letter-spacing: 2px; text-transform: uppercase;
        }

        .badge-actual {
            background: #00ff99; color: #1e1e2f;
            font-size: 9px; font-weight: bold;
            padding: 3px 10px; border-radius: 20px;
            letter-spacing: 2px; text-transform: uppercase;
        }

        .plan-icono {
            font-size: 2.2rem;
            margin-bottom: 4px;
        }

        .plan-nombre {
            font-size: 1.1rem; font-weight: bold;
            letter-spacing: 3px; text-transform: uppercase;
            color: #fff;
        }

        .plan-precio {
            font-size: 2.2rem; font-weight: bold;
            color: #00cfff;
            line-height: 1;
        }

        .plan-precio span {
            font-size: 0.85rem; color: #aaa;
            font-weight: normal;
        }

        .plan-features {
            list-style: none; width: 100%;
            display: flex; flex-direction: column; gap: 8px;
            margin: 8px 0;
        }

        .plan-features li {
            font-size: 0.8rem; color: #a0bdd0;
            display: flex; align-items: center; gap: 8px;
        }

        .plan-features li i {
            font-size: 11px; width: 14px; text-align: center;
        }

        .plan-features li i.fa-check  { color: #00ff99; }
        .plan-features li i.fa-times  { color: #ff4444; }
        .plan-features li i.fa-star   { color: gold; }

        .btn-plan {
            width: 100%; padding: 11px;
            background: transparent;
            border: 1px solid #00cfff;
            border-radius: 10px; color: #00cfff;
            font-size: 0.85rem; font-weight: bold;
            letter-spacing: 2px; text-transform: uppercase;
            cursor: pointer; transition: 0.3s;
            font-family: Arial;
            margin-top: auto;
        }

        .btn-plan:hover {
            background: #00cfff; color: #1e1e2f;
            box-shadow: 0 0 20px rgba(0,207,255,0.4);
        }

        .btn-plan:disabled {
            border-color: #00ff99; color: #00ff99;
            cursor: default; opacity: 0.8;
        }

        .btn-plan.gratis {
            border-color: #555; color: #888;
        }
        .btn-plan.gratis:hover {
            background: #555; color: white;
            box-shadow: none;
        }

        /* CARRITO FLOTANTE */
        #carrito-btn {
            position: fixed; bottom: 30px; right: 30px;
            background: #00cfff; color: #1e1e2f;
            width: 56px; height: 56px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; cursor: pointer; z-index: 9999;
            box-shadow: 0 0 20px rgba(0,207,255,0.5);
            transition: transform 0.2s;
        }
        #carrito-btn:hover { transform: scale(1.1); }

        #carrito-badge {
            position: absolute; top: -4px; right: -4px;
            background: #ff4444; color: white;
            width: 20px; height: 20px; border-radius: 50%;
            font-size: 11px; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
        }

        /* MODAL CARRITO */
        #modal-carrito {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); z-index: 99999;
            align-items: center; justify-content: center;
        }

        .carrito-panel {
            background: #0f1b2d;
            border: 1px solid rgba(0,207,255,0.3);
            border-radius: 18px; padding: 28px;
            width: 360px; max-width: 95vw;
            box-shadow: 0 0 40px rgba(0,207,255,0.15);
        }

        .carrito-panel h3 {
            color: #00cfff; font-size: 1rem;
            letter-spacing: 3px; text-align: center;
            margin-bottom: 20px; text-transform: uppercase;
        }

        .carrito-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .carrito-item-info { display: flex; flex-direction: column; gap: 3px; }
        .carrito-item-info strong { font-size: 0.9rem; color: #fff; }
        .carrito-item-info span   { font-size: 0.75rem; color: #aaa; }

        .carrito-item-precio {
            font-size: 1.1rem; font-weight: bold; color: #00cfff;
        }

        .carrito-vacio {
            text-align: center; color: #555;
            font-size: 0.85rem; padding: 20px 0;
        }

        .carrito-total {
            display: flex; justify-content: space-between;
            align-items: center; padding: 16px 0 0;
            font-size: 0.9rem; color: #aaa;
        }
        .carrito-total strong { font-size: 1.2rem; color: #00cfff; }

        .btn-checkout {
            width: 100%; margin-top: 16px; padding: 13px;
            background: #00cfff; color: #1e1e2f;
            border: none; border-radius: 10px;
            font-size: 0.9rem; font-weight: bold;
            letter-spacing: 2px; text-transform: uppercase;
            cursor: pointer; transition: 0.3s; font-family: Arial;
        }
        .btn-checkout:hover { box-shadow: 0 0 24px rgba(0,207,255,0.5); }
        .btn-checkout:disabled { background: #555; color: #888; cursor: default; box-shadow: none; }

        .btn-vaciar {
            width: 100%; margin-top: 8px; padding: 10px;
            background: transparent; color: #555;
            border: 1px solid #333; border-radius: 10px;
            font-size: 0.8rem; cursor: pointer; font-family: Arial;
            transition: 0.3s;
        }
        .btn-vaciar:hover { border-color: #ff4444; color: #ff4444; }

        .btn-cerrar-carrito {
            position: absolute; top: 16px; right: 18px;
            background: none; border: none; color: #555;
            font-size: 1.1rem; cursor: pointer; transition: 0.2s;
        }
        .btn-cerrar-carrito:hover { color: #ff4444; }

        /* TOAST */
        #toast {
            display: none; position: fixed; bottom: 100px; right: 30px;
            background: rgba(0,207,255,0.15);
            border: 1px solid rgba(0,207,255,0.4);
            border-radius: 10px; padding: 10px 18px;
            color: #00cfff; font-size: 0.82rem;
            font-family: Arial; z-index: 99999;
            animation: fadeToast 0.3s ease;
        }
        @keyframes fadeToast { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        /* MENSAJE RESULTADO PAGO */
        #msg-pago {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.85); z-index: 999999;
            align-items: center; justify-content: center;
        }
        .msg-pago-box {
            background: #0f1b2d;
            border: 1px solid rgba(0,255,153,0.4);
            border-radius: 18px; padding: 36px;
            text-align: center; width: 320px;
            box-shadow: 0 0 40px rgba(0,255,153,0.15);
        }
        .msg-pago-box i { font-size: 3rem; color: #00ff99; margin-bottom: 14px; display: block; }
        .msg-pago-box h3 { color: #00ff99; letter-spacing: 2px; margin-bottom: 10px; }
        .msg-pago-box p  { color: #aaa; font-size: 0.85rem; margin-bottom: 20px; }
        .msg-pago-box button {
            padding: 10px 28px; background: transparent;
            border: 1px solid #00cfff; border-radius: 8px;
            color: #00cfff; cursor: pointer; font-family: Arial;
            font-size: 0.85rem; transition: 0.3s;
        }
        .msg-pago-box button:hover { background: #00cfff; color: #1e1e2f; }
    </style>
</head>
<body>

    <!-- FONDO -->
    <div class="area">
        <ul class="circulos">
            <li></li><li></li><li></li><li></li><li></li><li></li>
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

    <!-- HEADER -->
    <div class="page-header">
        <h1 class="titulo">WARSMUSIC</h1>
        <p class="subtitulo">Elige tu plan</p>
    </div>

    <!-- NAV -->
    <nav class="nav-bar">
        <a href="index.php">Inicio</a>
        <a href="tienda.php" class="active">Planes</a>
    </nav>

    <!-- PLANES -->
    <div class="planes-container">

        <!-- FREE -->
        <div class="plan-card <?php echo $planActual === 'free' ? 'actual' : ''; ?>">
            <?php if ($planActual === 'free'): ?>
                <span class="badge-actual">Plan actual</span>
            <?php endif; ?>
            <div class="plan-icono">🎵</div>
            <div class="plan-nombre">Free</div>
            <div class="plan-precio">$0 <span>/ siempre</span></div>
            <ul class="plan-features">
                <li><i class="fas fa-check"></i> Reproducción básica</li>
                <li><i class="fas fa-check"></i> Biblioteca de canciones</li>
                <li><i class="fas fa-check"></i> Buscador local</li>
                <li><i class="fas fa-times"></i> YouTube integrado</li>
                <li><i class="fas fa-times"></i> Temporizador</li>
                <li><i class="fas fa-times"></i> Sin anuncios</li>
            </ul>
            <button class="btn-plan gratis"
                onclick="agregarAlCarrito('Free', 0, 'free')"
                <?php echo $planActual === 'free' ? 'disabled' : ''; ?>>
                <?php echo $planActual === 'free' ? '✓ Plan actual' : 'Seleccionar'; ?>
            </button>
        </div>

        <!-- MENSUAL -->
        <div class="plan-card destacado <?php echo ($planActual === 'mensual') ? 'actual' : ''; ?>">
            <span class="badge-popular">Más popular</span>
            <?php if ($planActual === 'mensual'): ?>
                <span class="badge-actual">Plan actual</span>
            <?php endif; ?>
            <div class="plan-icono">⚡</div>
            <div class="plan-nombre">Premium</div>
            <div class="plan-precio">$9.900 <span>/ mes</span></div>
            <ul class="plan-features">
                <li><i class="fas fa-check"></i> Todo lo del plan Free</li>
                <li><i class="fas fa-star"></i> YouTube integrado</li>
                <li><i class="fas fa-star"></i> Búsqueda unificada</li>
                <li><i class="fas fa-star"></i> Temporizador</li>
                <li><i class="fas fa-star"></i> Menú exclusivo</li>
                <li><i class="fas fa-check"></i> Sin anuncios</li>
            </ul>
            <button class="btn-plan"
                onclick="agregarAlCarrito('Premium Mensual', 9900, 'mensual')"
                <?php echo $esPremium ? 'disabled' : ''; ?>>
                <?php echo $esPremium ? '✓ Ya tienes Premium' : 'Agregar al carrito'; ?>
            </button>
        </div>

        <!-- ANUAL -->
        <div class="plan-card <?php echo ($planActual === 'anual') ? 'actual' : ''; ?>">
            <?php if ($planActual === 'anual'): ?>
                <span class="badge-actual">Plan actual</span>
            <?php endif; ?>
            <div class="plan-icono">👑</div>
            <div class="plan-nombre">Premium Anual</div>
            <div class="plan-precio">$79.900 <span>/ año</span></div>
            <ul class="plan-features">
                <li><i class="fas fa-check"></i> Todo lo del plan Premium</li>
                <li><i class="fas fa-star"></i> 2 meses gratis</li>
                <li><i class="fas fa-star"></i> Prioridad en soporte</li>
                <li><i class="fas fa-star"></i> Descargas offline</li>
                <li><i class="fas fa-star"></i> Acceso anticipado</li>
                <li><i class="fas fa-check"></i> Sin anuncios</li>
            </ul>
            <button class="btn-plan"
                onclick="agregarAlCarrito('Premium Anual', 79900, 'anual')"
                <?php echo $esPremium ? 'disabled' : ''; ?>>
                <?php echo $esPremium ? '✓ Ya tienes Premium' : 'Agregar al carrito'; ?>
            </button>
        </div>

    </div>

    <!-- CARRITO FLOTANTE -->
    <div id="carrito-btn" onclick="abrirCarrito()">
        <i class="fas fa-shopping-cart"></i>
        <div id="carrito-badge" style="display:none">0</div>
    </div>

    <!-- MODAL CARRITO -->
    <div id="modal-carrito" onclick="cerrarCarrito(event)">
        <div class="carrito-panel" style="position:relative;">
            <button class="btn-cerrar-carrito" onclick="cerrarCarrito()">
                <i class="fas fa-times"></i>
            </button>
            <h3><i class="fas fa-shopping-cart" style="margin-right:8px;"></i>Tu carrito</h3>
            <div id="carrito-contenido"></div>
            <div id="carrito-total-box" style="display:none;">
                <div class="carrito-total">
                    <span>Total</span>
                    <strong id="carrito-total-precio">$0</strong>
                </div>
                <button class="btn-checkout" id="btn-checkout" onclick="confirmarCompra()">
                    <i class="fas fa-bolt" style="margin-right:6px;"></i>Confirmar compra
                </button>
                <button class="btn-vaciar" onclick="vaciarCarrito()">Vaciar carrito</button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="toast"></div>

    <!-- MENSAJE ÉXITO -->
    <div id="msg-pago" onclick="cerrarMsgPago(event)">
        <div class="msg-pago-box">
            <i class="fas fa-check-circle"></i>
            <h3>¡Compra exitosa!</h3>
            <p id="msg-pago-texto">Tu plan ha sido activado.</p>
            <button onclick="window.location.href='index.php'">Ir al inicio</button>
        </div>
    </div>

    <script>
    const usuarioActivo = <?php echo isset($_SESSION['usuario']) ? '"' . $_SESSION['usuario'] . '"' : 'null'; ?>;

    let carrito = [];

    function formatPrecio(n) {
        if (n === 0) return 'Gratis';
        return '$' + n.toLocaleString('es-CO');
    }

    function agregarAlCarrito(nombre, precio, plan) {
        if (!usuarioActivo) {
            mostrarToast('⚠️ Debes iniciar sesión primero');
            setTimeout(() => window.location.href = 'login.php', 1200);
            return;
        }

        // Solo 1 item en el carrito a la vez
        carrito = [{ nombre, precio, plan }];
        actualizarBadge();
        mostrarToast(`✓ ${nombre} agregado al carrito`);
    }

    function actualizarBadge() {
        const badge = document.getElementById('carrito-badge');
        if (carrito.length > 0) {
            badge.style.display = 'flex';
            badge.textContent = carrito.length;
        } else {
            badge.style.display = 'none';
        }
    }

    function abrirCarrito() {
        const contenido = document.getElementById('carrito-contenido');
        const totalBox  = document.getElementById('carrito-total-box');

        if (carrito.length === 0) {
            contenido.innerHTML = '<p class="carrito-vacio"><i class="fas fa-shopping-cart" style="font-size:2rem;display:block;margin-bottom:10px;"></i>Tu carrito está vacío</p>';
            totalBox.style.display = 'none';
        } else {
            let html = '';
            let total = 0;
            carrito.forEach(item => {
                total += item.precio;
                html += `
                    <div class="carrito-item">
                        <div class="carrito-item-info">
                            <strong>${item.nombre}</strong>
                            <span>${item.precio === 0 ? 'Sin costo' : formatPrecio(item.precio)}</span>
                        </div>
                        <div class="carrito-item-precio">${formatPrecio(item.precio)}</div>
                    </div>`;
            });
            contenido.innerHTML = html;
            document.getElementById('carrito-total-precio').textContent = formatPrecio(total);
            totalBox.style.display = 'block';

            const btnCO = document.getElementById('btn-checkout');
            btnCO.disabled = (total === 0 && carrito[0]?.plan === 'free');
        }

        document.getElementById('modal-carrito').style.display = 'flex';
    }

    function cerrarCarrito(e) {
        if (!e || e.target === document.getElementById('modal-carrito')) {
            document.getElementById('modal-carrito').style.display = 'none';
        }
    }

    function vaciarCarrito() {
        carrito = [];
        actualizarBadge();
        document.getElementById('modal-carrito').style.display = 'none';
        mostrarToast('Carrito vaciado');
    }

    function confirmarCompra() {
        if (!usuarioActivo) {
            mostrarToast('⚠️ Debes iniciar sesión');
            return;
        }
        if (carrito.length === 0) return;

        const item = carrito[0];
        const btn  = document.getElementById('btn-checkout');
        btn.disabled = true;
        btn.textContent = 'Procesando...';

        const fd = new FormData();
        fd.append('plan',   item.plan);
        fd.append('precio', item.precio);

        fetch('pagar.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    document.getElementById('modal-carrito').style.display = 'none';
                    document.getElementById('msg-pago-texto').textContent = data.msg;
                    document.getElementById('msg-pago').style.display = 'flex';
                    carrito = [];
                    actualizarBadge();
                } else {
                    mostrarToast('❌ ' + (data.error || 'Error al procesar'));
                    btn.disabled = false;
                    btn.textContent = 'Confirmar compra';
                }
            })
            .catch(() => {
                mostrarToast('❌ Error de conexión');
                btn.disabled = false;
                btn.textContent = 'Confirmar compra';
            });
    }

    function cerrarMsgPago(e) {
        if (!e || e.target === document.getElementById('msg-pago')) {
            document.getElementById('msg-pago').style.display = 'none';
        }
    }

    function mostrarToast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.style.display = 'block';
        clearTimeout(window._toastTimer);
        window._toastTimer = setTimeout(() => { t.style.display = 'none'; }, 2500);
    }
    </script>

</body>
</html>
