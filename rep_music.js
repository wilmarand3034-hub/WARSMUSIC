/*
============================================================
  ARCHIVO: rep_music.js
  DESCRIPCIÓN: Lógica principal del reproductor WARSMUSIC
  FUNCIÓN: Controla el audio, la biblioteca, los favoritos,
           el buscador, el sidebar Premium y todos los eventos
           de la interfaz de usuario.

  TEMAS CUBIERTOS:
  - Variables (const, let)
  - Arrays (listaDeCanciones, listaDeVideos, beneficios)
  - Sentencias condicionales (if / else)
  - Sentencias repetitivas (forEach, for...of)
  - Funciones (cargarCancion, actualizarNombre, etc.)
  - Consultas al servidor (fetch → PHP → MySQL)
  - Manipulación del DOM (getElementById, createElement)
  - Eventos (onclick, addEventListener)
============================================================
*/


// ============================================================
// SECCIÓN 1 — VARIABLES: ELEMENTOS DEL DOM
// Se capturan los elementos HTML usando getElementById para
// poder manipularlos desde JavaScript.
// const: variable que no cambia de referencia (buena práctica
// para elementos del DOM que siempre apuntan al mismo elemento).
// ============================================================

const reproductor  = document.getElementById("reproductor");   // El elemento <audio>
const btnPlay      = document.getElementById("btn-play");       // Botón play/pausa
const btnNext      = document.getElementById("btn-next");       // Botón siguiente canción
const btnPrev      = document.getElementById("btn-prev");       // Botón canción anterior

const btnBiblioteca = document.getElementById("btn-biblioteca"); // Enlace "Biblioteca" del menú
const biblioteca    = document.getElementById("biblioteca");     // Sección con la lista de canciones

// Referencia al elemento Premium (se mantiene como objeto con propiedades mínimas)
const premium    = { style: { display: "none" }, contains: () => false };
const btnPremium = document.getElementById("btn-premium");       // Enlace "Premium" del menú

const nombreCancion = document.getElementById("nombre-cancion"); // Párrafo que muestra el título
const btnInfo       = document.getElementById("btn-info");       // Ícono de información
const modalInfo     = document.getElementById("modal-info");     // Modal de descripción
const textoInfo     = document.getElementById("texto-info");     // Párrafo dentro del modal


// ============================================================
// SECCIÓN 2 — ARRAYS
// Un array es una lista de valores agrupados en una variable.
// Se declaran con [] y sus elementos se separan por comas.
// ============================================================

// ARRAY vacío: se llenará luego con fetch() desde la base de datos
// let: variable que puede cambiar su valor (en este caso, su contenido)
let listaDeCanciones = [];

// ARRAY con URLs de videos — definido directamente en el código
const listaDeVideos = [
    "https://nosejas1025.sirv.com/KRIS%20R%20-%20GANAS%20%20(VIDEO%20OFICIAL).mp4",
    "https://nosejas1025.sirv.com/Entre%20Amigos%20-%20Los%20Gigantes%20del%20Vallenato!.mp4",
    "https://nosejas1025.sirv.com/Ricardo%20Arjona%20-%20Historia%20De%20Taxi%20%20(Video).mp4",
    "https://nosejas1025.sirv.com/Wisin%20Y%20Yandel%20-''Yo%20Te%20Quiero''VIDEO%20OFICIAL.mp4",
    "https://nosejas1025.sirv.com/Si%20No%20Regresas%2C%20Binomio%20De%20Oro%20De%20Am%C3%A9rica%2C%20Video%20Letra%20-%20Sentir%20Vallenato.mp4",
    "https://nosejas1025.sirv.com/Setaoc%20Mass%20_%20Boiler%20Room%20x%20Glitch%20Festival%202025%20%5B1E3WsoHSxNM%5D.mp4"
];

// VARIABLE numérica: índice de la canción que se está reproduciendo
// Empieza en 0 (primer elemento del array listaDeCanciones)
let cancionActual = 0;


// ============================================================
// SECCIÓN 3 — CONSULTA AL SERVIDOR (fetch → canciones.php → MySQL)
// fetch() hace una petición HTTP GET a canciones.php.
// PHP ejecuta SELECT * FROM canciones y devuelve JSON.
// JavaScript recibe ese JSON y llena el array listaDeCanciones.
//
// SENTENCIA REPETITIVA: data.forEach recorre cada canción
// recibida y crea un elemento <li> en la biblioteca.
// ============================================================

fetch("canciones.php")          // GET a canciones.php
    .then(res => {
        // Si el servidor responde con error HTTP (404, 500, etc.) lo mostramos ya
        if (!res.ok) {
            throw new Error(`canciones.php respondió con estado ${res.status}`);
        }
        return res.text(); // primero como texto, para poder detectar respuestas rotas
    })
    .then(texto => {
        let data;
        try {
            data = JSON.parse(texto); // intenta convertir a JSON
        } catch (e) {
            // Esto pasa casi siempre porque PHP está imprimiendo un error/warning
            // ANTES del JSON (por ejemplo, un error de conexión a la base de datos).
            // Al ver este mensaje en la consola sabrás exactamente qué está fallando.
            console.error("canciones.php no devolvió JSON válido. Respuesta cruda:", texto);
            return;
        }

        // data.map() recorre el array y extrae solo el campo "musica"
        // Resultado: listaDeCanciones = ["url1.mp3", "url2.mp3", ...]
        listaDeCanciones = data.map(c => c.musica);

        // Se accede al <ul> dentro de #biblioteca para llenarlo
        const ul = document.querySelector("#biblioteca ul");
        ul.innerHTML = ""; // limpia cualquier contenido previo

        // SENTENCIA REPETITIVA forEach — recorre cada canción del array
        // (index) es la posición en el array: 0, 1, 2, ...
        data.forEach((cancion, index) => {
            if (!cancion.musica) return; // si no tiene url, se salta

            const li = document.createElement("li"); // crea elemento <li>

            // Extrae solo el nombre del archivo sin la ruta y sin ".mp3"
            li.textContent = cancion.musica.split("/").pop().replace(".mp3", "");

            // Evento clic: al hacer clic en el nombre, se reproduce esa canción
            li.onclick = () => {
                seleccionarCancion(index);
                playerControls.style.display = "block"; // muestra el reproductor
            };

            ul.appendChild(li); // agrega el <li> al <ul>
        });

        // Si hay canciones, carga la primera automáticamente (sin reproducirla)
        if (listaDeCanciones.length > 0) {
            reproductor.src = listaDeCanciones[0];
            actualizarNombre();
        } else {
            console.warn("canciones.php devolvió una lista vacía: revisa la tabla 'canciones' en la base de datos.");
        }

        console.log("Canciones cargadas:", listaDeCanciones); // para depuración
    })
    .catch(error => console.error("Error cargando canciones.php:", error)); // manejo de errores de red


// ============================================================
// SECCIÓN 4 — BUSCADOR (local + YouTube Premium)
// Captura el input del buscador y filtra canciones locales.
// Si el usuario es Premium, también busca en YouTube.
// ============================================================

const buscador            = document.getElementById("buscador");
const resultadosBusqueda  = document.getElementById("resultados-busqueda");

// VARIABLE: API Key de YouTube (clave de acceso a la API de Google)
const YT_API_KEY = "AIzaSyDYa-zAdtEYxe4NrQ_5xeNkHBL8jXtE6yU";

// Logo SVG de WARSMUSIC en formato string (para insertar en HTML dinámico)
const logoSVG = `<svg width="16" height="16" viewBox="0 0 130 130" style="display:inline-block;vertical-align:middle;margin-right:4px;">
  <defs><filter id="glb"><feGaussianBlur stdDeviation="2.5" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>
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
  <polyline points="72,8 47,65 63,65 53,122" fill="none" stroke="#00cfff" stroke-width="5" stroke-linejoin="round" stroke-linecap="round" filter="url(#glb)"/>
  <polyline points="72,8 84,8 72,57 91,57 53,122" fill="none" stroke="#8A2BE2" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" opacity="0.8"/>
</svg>`;

let ytPlayerContainer  = null;  // referencia al mini reproductor de YouTube
let ytBusquedaTimeout  = null;  // temporizador para no buscar en YT con cada letra

// ============================================================
// SINCRONIZACIÓN CUADRO AZUL (player-controls) ↔ CUADRO ROJO (yt-mini-player)
// ytPlayer:      instancia de la YouTube IFrame Player API (permite
//                controlar play/pausa y escuchar cambios de estado,
//                cosa que un <iframe> normal NO permite).
// reproduciendoYT: bandera que indica si lo que suena ahora mismo
//                viene de YouTube (true) o del <audio> local (false).
// ytApiListo:    se pone en true cuando la API de YouTube terminó de cargar.
// ============================================================
let ytPlayer         = null;
let reproduciendoYT  = false;
let ytApiListo       = false;

// La API de YouTube llama automáticamente a esta función global
// en cuanto termina de cargarse (es requisito de su documentación).
window.onYouTubeIframeAPIReady = () => { ytApiListo = true; };

// FUNCIÓN: inyecta el script de la API de YouTube una sola vez
function cargarYTApi() {
    if (document.getElementById("yt-iframe-api")) return; // ya se cargó antes
    const tag = document.createElement("script");
    tag.id  = "yt-iframe-api";
    tag.src = "https://www.youtube.com/iframe_api";
    document.head.appendChild(tag);
}
cargarYTApi(); // se pide desde ya para que esté lista cuando el usuario la necesite


// FUNCIÓN: crea el mini reproductor de YouTube si no existe
function crearYTPlayer() {
    if (document.getElementById("yt-mini-player")) return; // ya existe, no crear otro

    const div = document.createElement("div");
    div.id = "yt-mini-player";
    div.innerHTML = `
        <div id="yt-mini-header">
            <svg width="14" height="14" viewBox="0 0 130 130" style="display:inline-block;vertical-align:middle;flex-shrink:0;"><defs><filter id="glm"><feGaussianBlur stdDeviation="2.5" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs><rect x="6" y="52" width="5" height="26" rx="2.5" fill="#00cfff" opacity="0.5"/><rect x="14" y="44" width="5" height="42" rx="2.5" fill="#00cfff" opacity="0.7"/><rect x="22" y="36" width="5" height="58" rx="2.5" fill="#00cfff" opacity="0.9"/><rect x="30" y="44" width="5" height="42" rx="2.5" fill="#00cfff" opacity="0.7"/><rect x="38" y="52" width="5" height="26" rx="2.5" fill="#00cfff" opacity="0.5"/><rect x="87" y="52" width="5" height="26" rx="2.5" fill="#00cfff" opacity="0.5"/><rect x="95" y="44" width="5" height="42" rx="2.5" fill="#00cfff" opacity="0.7"/><rect x="103" y="36" width="5" height="58" rx="2.5" fill="#00cfff" opacity="0.9"/><rect x="111" y="44" width="5" height="42" rx="2.5" fill="#00cfff" opacity="0.7"/><rect x="119" y="52" width="5" height="26" rx="2.5" fill="#00cfff" opacity="0.5"/><polyline points="72,8 47,65 63,65 53,122" fill="none" stroke="#00cfff" stroke-width="5" stroke-linejoin="round" stroke-linecap="round" filter="url(#glm)"/><polyline points="72,8 84,8 72,57 91,57 53,122" fill="none" stroke="#8A2BE2" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" opacity="0.8"/></svg>
            <span id="yt-mini-titulo">YouTube Premium</span>
            <i class="fas fa-times" id="yt-mini-cerrar"></i>
        </div>
        <div id="yt-mini-target"></div>
    `;
    document.body.appendChild(div); // agrega el player al final del <body>

    // Evento para cerrar el mini reproductor
    document.getElementById("yt-mini-cerrar").onclick = () => {
        if (ytPlayer) ytPlayer.stopVideo(); // detiene el video de verdad
        div.style.display = "none";
        reproduciendoYT = false;
        btnPlay.classList.replace("fa-pause", "fa-play"); // refleja "detenido" en el cuadro azul
    };

    ytPlayerContainer = div; // guarda la referencia para usarla después
}


// FUNCIÓN: crea la instancia real de YT.Player (una sola vez) y
// conecta sus eventos con el cuadro azul.
function crearInstanciaYT(videoId) {
    ytPlayer = new YT.Player("yt-mini-target", {
        videoId: videoId,
        playerVars: { autoplay: 1 },
        events: {
            // Cuando el video arranca, aseguramos que se reproduzca
            onReady: (e) => e.target.playVideo(),

            // CLAVE DE LA SINCRONIZACIÓN:
            // cada vez que YouTube cambia de estado (play, pausa, termina),
            // este evento se dispara y actualizamos el ícono del cuadro azul
            // para que SIEMPRE refleje lo que realmente está sonando.
            onStateChange: (e) => {
                if (e.data === YT.PlayerState.PLAYING) {
                    btnPlay.classList.remove("fa-play");
                    btnPlay.classList.add("fa-pause");
                } else if (e.data === YT.PlayerState.PAUSED || e.data === YT.PlayerState.ENDED) {
                    btnPlay.classList.remove("fa-pause");
                    btnPlay.classList.add("fa-play");
                }
            }
        }
    });
}


// FUNCIÓN: reproduce un video de YouTube y refleja el estado
// tanto en el cuadro rojo (mini player) como en el cuadro azul (player-controls)
function reproducirYT(videoId, titulo) {
    crearYTPlayer();       // asegura que el contenedor del cuadro rojo existe
    reproduciendoYT = true;

    // SENTENCIA CONDICIONAL: si hay audio local sonando, lo pausa
    if (!reproductor.paused) {
        reproductor.pause();
    }

    // ---- Refleja el "ahora suena" en el CUADRO AZUL ----
    nombreCancion.textContent = titulo.length > 60 ? titulo.slice(0, 60) + "…" : titulo;
    playerControls.style.display = "block";
    btnPlay.classList.remove("fa-play");
    btnPlay.classList.add("fa-pause");

    // ---- Refleja el "ahora suena" en el CUADRO ROJO (mini player) ----
    document.getElementById("yt-mini-titulo").textContent = titulo.length > 32 ? titulo.slice(0,32)+"…" : titulo;
    ytPlayerContainer.style.display = "flex";

    // Carga el video en el reproductor real de YouTube
    if (ytPlayer && typeof ytPlayer.loadVideoById === "function") {
        ytPlayer.loadVideoById(videoId); // ya existe una instancia: solo cambia el video
    } else if (ytApiListo) {
        crearInstanciaYT(videoId); // primera vez y la API ya está lista
    } else {
        // La API todavía no cargó (puede pasar la primera vez) — esperamos un momento
        const esperarApi = setInterval(() => {
            if (ytApiListo) {
                clearInterval(esperarApi);
                crearInstanciaYT(videoId);
            }
        }, 200);
    }
}


// FUNCIÓN ASÍNCRONA: busca videos en YouTube usando su API
// async/await permite esperar la respuesta sin bloquear el resto del código
async function buscarYouTube(query) {
    try {
        // fetch a la API de YouTube con la búsqueda y la clave de API
        const res  = await fetch(`https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(query)}&type=video&maxResults=4&key=${YT_API_KEY}`);
        const data = await res.json();

        // SENTENCIA CONDICIONAL: manejo de errores de la API
        if (data.error) {
            const reason = data.error.errors?.[0]?.reason || "";
            if (reason === "quotaExceeded" || data.error.code === 403) {
                return { error: "quota" };   // cuota diaria agotada
            }
            if (data.error.code === 400) {
                return { error: "apikey" };  // clave de API inválida
            }
            return { error: "api", msg: data.error.message };
        }

        return data.items || []; // devuelve los resultados o array vacío
    } catch(e) {
        return { error: "red" }; // error de conexión
    }
}


// EVENTO: se dispara cada vez que el usuario escribe en el buscador
buscador.addEventListener("input", function() {
    const texto = this.value.toLowerCase().trim(); // texto en minúsculas sin espacios
    resultadosBusqueda.innerHTML = "";             // limpia resultados anteriores
    clearTimeout(ytBusquedaTimeout);               // cancela búsqueda YT pendiente

    // SENTENCIA CONDICIONAL: si el campo está vacío, oculta resultados
    if (texto === "") { resultadosBusqueda.style.display = "none"; return; }

    // FILTRADO LOCAL: array.filter() devuelve solo las canciones que coinciden
    // Se compara el nombre del archivo (sin ruta ni .mp3) con el texto buscado
    const encontradas = listaDeCanciones.filter(c =>
        c && c.split("/").pop().replace(".mp3","").toLowerCase().includes(texto)
    );

    // SENTENCIA CONDICIONAL + forEach: si hay resultados locales, los muestra
    if (encontradas.length > 0) {
        // Encabezado de sección "Biblioteca local"
        const h = document.createElement("li");
        h.className = "buscador-seccion";
        h.innerHTML = `<i class="fas fa-database"></i> Biblioteca local`;
        resultadosBusqueda.appendChild(h);

        // SENTENCIA REPETITIVA: forEach crea un <li> por cada canción encontrada
        encontradas.forEach(cancion => {
            const li = document.createElement("li");
            li.innerHTML = `<i class="fas fa-music"></i> ${cancion.split("/").pop().replace(".mp3","")}`;
            li.onclick = () => {
                // Al hacer clic: ubica la canción en el array y la reproduce
                cancionActual = listaDeCanciones.indexOf(cancion);
                cargarCancion();
                playerControls.style.display = "block";
                resultadosBusqueda.style.display = "none";
                buscador.value = "";
                // Cierra el mini player de YouTube si estaba abierto
                if (ytPlayerContainer) {
                    if (ytPlayer) ytPlayer.stopVideo();
                    ytPlayerContainer.style.display = "none";
                }
                reproduciendoYT = false;
            };
            resultadosBusqueda.appendChild(li);
        });
    }

    resultadosBusqueda.style.display = "block";

    // SENTENCIA CONDICIONAL: si es Premium, también busca en YouTube
    if (esPremium && usuarioActivo) {
        const cargando = document.createElement("li");
        cargando.id = "yt-cargando";
        cargando.className = "buscador-seccion yt-seccion";
        cargando.innerHTML = `${logoSVG} YouTube <i class="fas fa-spinner fa-spin" style="margin-left:4px;font-size:10px;"></i>`;
        resultadosBusqueda.appendChild(cargando);

        // setTimeout: espera 600ms antes de buscar (evita búsquedas con cada letra)
        ytBusquedaTimeout = setTimeout(async () => {
            document.getElementById("yt-cargando")?.remove(); // quita el spinner

            const items = await buscarYouTube(texto); // espera la respuesta de YouTube

            // SENTENCIA CONDICIONAL: si hubo error, muestra mensaje
            if (!Array.isArray(items)) {
                const errLi = document.createElement("li");
                errLi.className = "buscador-seccion yt-seccion";
                // ARRAY como objeto de mensajes de error (clave → mensaje)
                const mensajesError = {
                    quota:  `${logoSVG} YouTube: cuota diaria agotada, intenta mañana`,
                    apikey: `${logoSVG} YouTube: API key inválida`,
                    red:    `${logoSVG} YouTube: sin conexión`,
                    api:    `${logoSVG} YouTube: error — ${items.msg || ""}`
                };
                errLi.innerHTML = mensajesError[items.error] || `${logoSVG} YouTube: error desconocido`;
                errLi.style.pointerEvents = "none";
                resultadosBusqueda.appendChild(errLi);
                return;
            }

            if (!items.length) return; // no hay resultados de YouTube

            // Encabezado de sección YouTube
            const ytH = document.createElement("li");
            ytH.className = "buscador-seccion yt-seccion";
            ytH.innerHTML = `${logoSVG} YouTube Premium`;
            resultadosBusqueda.appendChild(ytH);

            // SENTENCIA REPETITIVA: forEach crea una tarjeta por cada video de YouTube
            items.forEach(item => {
                const li = document.createElement("li");
                li.className = "yt-resultado";
                li.innerHTML = `
                    <img src="${item.snippet.thumbnails.default.url}" class="yt-thumb">
                    <div class="yt-info">
                        <span class="yt-titulo">${item.snippet.title.slice(0,40)}…</span>
                        <span class="yt-canal">${item.snippet.channelTitle}</span>
                    </div>
                    <i class="fas fa-play yt-play-icon"></i>`;
                li.onclick = () => {
                    reproducirYT(item.id.videoId, item.snippet.title);
                    resultadosBusqueda.style.display = "none";
                    buscador.value = "";
                };
                resultadosBusqueda.appendChild(li);
            });
        }, 600);

    } else {
        // SENTENCIA CONDICIONAL: si no es Premium, muestra mensaje promocional
        const promo = document.createElement("li");
        promo.className = "buscador-seccion yt-promo";
        promo.innerHTML = usuarioActivo
            ? `<i class="fas fa-lock"></i> Activa <strong>Premium</strong> para ver canciones en línea`
            : `<i class="fas fa-user"></i> <a href="login.php" style="color:#00cfff;">Inicia sesión</a> para ver canciones Premium`;
        resultadosBusqueda.appendChild(promo);
    }

    // Si no se encontró nada en ningún lado
    if (!encontradas.length && !esPremium) {
        resultadosBusqueda.innerHTML = `<li style="color:#666;cursor:default;padding:8px 16px;">Sin resultados locales</li>`;
        resultadosBusqueda.style.display = "block";
    } else if (!encontradas.length && esPremium) {
        resultadosBusqueda.style.display = "block"; // mantiene el spinner de YouTube
    }
});

// Cierra los resultados al hacer clic fuera del buscador
document.addEventListener("click", (e) => {
    if (!buscador.contains(e.target) && !resultadosBusqueda.contains(e.target)) {
        if (!document.getElementById("yt-cargando")) {
            resultadosBusqueda.style.display = "none";
        }
    }
});


// ============================================================
// SECCIÓN 5 — TOGGLE DEL REPRODUCTOR (colapsar/expandir)
// Muestra u oculta los elementos internos del reproductor.
// ARRAY elementos: lista de referencias DOM para iterar.
// ============================================================

const btnToggleReproductor = document.getElementById("btn-toggle-reproductor");
const iconoToggle          = document.getElementById("icono-toggle");
let reproductorExpandido   = true; // VARIABLE de estado: true = expandido

btnToggleReproductor.onclick = () => {
    reproductorExpandido = !reproductorExpandido; // invierte el estado (toggle)

    // ARRAY de elementos que se muestran u ocultan
    const elementos = [
        document.querySelector(".botones-principales"),
        document.querySelector(".progress-container"),
        document.querySelector(".volume-container"),
        document.querySelector(".extra-controls"),
        document.querySelector(".reproduciendo-label"),
        document.getElementById("nombre-cancion")
    ];

    // SENTENCIA REPETITIVA: forEach oculta o muestra cada elemento
    elementos.forEach(el => {
        if (el) el.style.display = reproductorExpandido ? "" : "none";
    });

    // Cambia el ícono de la flecha según el estado
    iconoToggle.classList.toggle("fa-chevron-down", reproductorExpandido);
    iconoToggle.classList.toggle("fa-chevron-up",  !reproductorExpandido);
};


// ============================================================
// SECCIÓN 6 — CONTROLES DE REPRODUCCIÓN
// Eventos de clic en play, siguiente y anterior.
// ============================================================

// PLAY / PAUSA
// SENTENCIA CONDICIONAL: si lo que suena viene de YouTube, controla el
// reproductor de YouTube en vez del <audio> local. El ícono se actualiza
// solo mediante el evento onStateChange definido en crearInstanciaYT().
btnPlay.onclick = () => {
    if (reproduciendoYT && ytPlayer) {
        const estado = ytPlayer.getPlayerState();
        if (estado === YT.PlayerState.PLAYING) {
            ytPlayer.pauseVideo();
        } else {
            ytPlayer.playVideo();
        }
        return;
    }

    if (reproductor.paused) {
        reproductor.play().catch(err => console.error("No se pudo reproducir el audio local:", err));
        btnPlay.classList.replace("fa-play", "fa-pause"); // cambia el ícono
    } else {
        reproductor.pause();
        btnPlay.classList.replace("fa-pause", "fa-play");
    }
};

// SIGUIENTE CANCIÓN
btnNext.onclick = () => {
    // SENTENCIA CONDICIONAL: si shuffle está activo, elige posición aleatoria
    if (shuffleActivo) {
        cancionActual = Math.floor(Math.random() * listaDeCanciones.length);
    } else {
        // % (módulo) hace que al llegar al final vuelva al inicio (0)
        cancionActual = (cancionActual + 1) % listaDeCanciones.length;
    }
    cargarCancion();
};

// CANCIÓN ANTERIOR
btnPrev.onclick = () => {
    // Se suma la longitud para evitar índices negativos antes del módulo
    cancionActual = (cancionActual - 1 + listaDeCanciones.length) % listaDeCanciones.length;
    cargarCancion();
};


// ============================================================
// SECCIÓN 7 — FUNCIONES PRINCIPALES
// Funciones reutilizables que centralizan la lógica del reproductor.
// ============================================================

// FUNCIÓN: carga y reproduce la canción en cancionActual
function cargarCancion() {
    if (!listaDeCanciones.length) return; // guarda si el array está vacío

    // Si había un video de YouTube sonando, lo detenemos para que no
    // queden dos cosas reproduciéndose ni el cuadro azul desincronizado
    if (reproduciendoYT && ytPlayer) {
        ytPlayer.stopVideo();
        reproduciendoYT = false;
        if (ytPlayerContainer) ytPlayerContainer.style.display = "none";
    }

    reproductor.src = listaDeCanciones[cancionActual]; // asigna la URL al audio
    reproductor.play().catch(err => console.error("No se pudo reproducir esta canción:", listaDeCanciones[cancionActual], err));
    btnPlay.classList.replace("fa-play", "fa-pause");
    actualizarNombre(); // actualiza el título mostrado

    // Quita la clase favorito y verifica en la BD si esta canción es favorita
    btnFavorite.classList.remove("favorito");
    // CONSULTA AL SERVIDOR: GET a verificar_favorito.php con la URL como parámetro
    fetch("verificar_favorito.php?cancion=" + encodeURIComponent(listaDeCanciones[cancionActual]))
        .then(res => res.json())
        .then(data => {
            // SENTENCIA CONDICIONAL: si es favorita, agrega la clase visual
            if (data.favorito) {
                btnFavorite.classList.add("favorito");
            }
        });
}


// FUNCIÓN: actualiza el nombre visible de la canción actual
function actualizarNombre() {
    if (!listaDeCanciones[cancionActual]) return;

    // split("/") divide la URL por "/", pop() toma el último segmento (nombre del archivo)
    // replace(".mp3","") elimina la extensión
    let nombre = listaDeCanciones[cancionActual]
        .split("/")
        .pop()
        .replace(".mp3", "");

    nombreCancion.textContent = nombre; // lo escribe en el HTML
}


// FUNCIÓN: selecciona una canción por su índice en el array y la carga
function seleccionarCancion(index) {
    cancionActual = index;
    cargarCancion();
}


// FUNCIÓN: reproduce un video local por su índice en listaDeVideos
function reproducirVideo(index) {
    const video = document.getElementById("videoPlayer");
    video.pause();
    video.src = listaDeVideos[index]; // usa el array de videos
    video.load();
    video.play();
    video.playbackRate = 1.0;
}


// ============================================================
// SECCIÓN 8 — MENÚ DE NAVEGACIÓN
// Muestra u oculta el menú al hacer clic en "Inicio".
// querySelectorAll devuelve todos los elementos con esa clase.
// ============================================================

const btnInicio    = document.getElementById("btn-inicio");
const menuOculto   = document.querySelectorAll(".menu-oculto"); // NodeList (como array)
const playerControls = document.getElementById("player-controls");
let menuAbierto    = false; // VARIABLE de estado del menú

btnInicio.onclick = (e) => {
    e.preventDefault(); // evita que el enlace navegue a "#"
    menuAbierto = !menuAbierto; // invierte el estado

    // SENTENCIA REPETITIVA: forEach muestra u oculta cada ítem del menú
    menuOculto.forEach(item => {
        item.style.display = menuAbierto ? "list-item" : "none";
    });
};

// Referencia al <ul> de la biblioteca (para eventos)
const ulBiblioteca = document.querySelector("#biblioteca ul");

// BIBLIOTECA: toggle al hacer clic en "Biblioteca"
btnBiblioteca.onclick = (e) => {
    e.preventDefault();
    premium.style.display = "none"; // cierra el panel premium si está abierto
    biblioteca.style.display =
        biblioteca.style.display === "block" ? "none" : "block";
};


// ============================================================
// SECCIÓN 9 — SIDEBAR PREMIUM
// Muestra beneficios del plan Premium.
// ARRAY beneficios: lista de objetos con icon, titulo y desc.
// SENTENCIA REPETITIVA: forEach genera HTML por cada beneficio.
// ============================================================

// VARIABLE de estado del Premium (inyectada desde PHP en index.php)
if (esPremium && usuarioActivo) {
    // El usuario ya tiene Premium — no se necesita mostrar nada extra aquí
}

// Referencias al sidebar y sus partes
const sidebarPremium = document.getElementById("sidebar-premium");
const sidebarOverlay = document.getElementById("sidebar-overlay");
const sidebarCerrar  = document.getElementById("sidebar-cerrar");
const sidebarBody    = document.getElementById("sidebar-body");
const sidebarFooter  = document.getElementById("sidebar-footer");

// ARRAY DE OBJETOS: cada objeto tiene las propiedades de un beneficio Premium
const beneficios = [
    { icon: "fa-brands fa-youtube",  titulo: "YouTube integrado",  desc: "Videos en la pantalla principal" },
    { icon: "fa-solid fa-search",    titulo: "Búsqueda unificada", desc: "Local + YouTube al mismo tiempo" },
    { icon: "fa-solid fa-clock",     titulo: "Temporizador",       desc: "Apaga la música automático" },
    { icon: "fa-solid fa-desktop",   titulo: "Menú exclusivo",     desc: "Acceso a funciones avanzadas" },
];


// FUNCIÓN: abre el sidebar Premium
function abrirSidebar() {
    // SENTENCIA CONDICIONAL: si no hay sesión, no puede entrar
    if (!usuarioActivo) {
        alert("⚠️ Debes iniciar sesión para acceder al Premium");
        return;
    }

    sidebarBody.innerHTML   = ""; // limpia el contenido anterior
    sidebarFooter.innerHTML = "";

    // SENTENCIA CONDICIONAL: muestra contenido diferente según si tiene Premium
    if (esPremium) {
        // Usuario con Premium activo
        sidebarBody.innerHTML = `
            <div class="sb-premium-badge">
                <span>✦ PREMIUM ACTIVO</span>
                <small>Todos los beneficios desbloqueados</small>
            </div>
        `;

        // SENTENCIA REPETITIVA: forEach genera un div por cada beneficio
        beneficios.forEach(b => {
            sidebarBody.innerHTML += `
                <div class="sb-benefit activo">
                    <i class="${b.icon}"></i>
                    <div class="sb-btext">
                        <strong>${b.titulo} <span class="sb-badge-activo">ACTIVO</span></strong>
                        ${b.desc}
                    </div>
                </div>
            `;
        });

        sidebarFooter.innerHTML = `
            <button class="sb-btn-perfil" id="sb-btn-perfil">✏️ Editar perfil</button>
            <button class="sb-btn-remover" id="sb-btn-remover">Remover licencia</button>
        `;

        document.getElementById("sb-btn-perfil").onclick = () => abrirPerfil();

        // CONSULTA AL SERVIDOR: POST a activar_premium.php para quitar el Premium
        document.getElementById("sb-btn-remover").onclick = () => {
            if (!confirm("¿Seguro que quieres remover tu licencia Premium?")) return;
            fetch("activar_premium.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "accion=quitar"
            })
            .then(res => res.json())
            .then(() => location.reload()); // recarga la página para reflejar el cambio
        };

    } else {
        // Usuario sin Premium — muestra opción de comprar y beneficios bloqueados
        sidebarBody.innerHTML += `<a href="tienda.php" style="display:block;width:100%;padding:11px;background:rgba(0,207,255,0.1);border:1px solid #00cfff;border-radius:8px;color:#00cfff;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;text-decoration:none;text-align:center;margin-bottom:16px;box-sizing:border-box;">🛒 Ver planes y precios</a>`;

        // SENTENCIA REPETITIVA: forEach muestra los beneficios como "bloqueados"
        beneficios.forEach(b => {
            sidebarBody.innerHTML += `
                <div class="sb-benefit">
                    <i class="${b.icon}"></i>
                    <div class="sb-btext">
                        <strong>${b.titulo}</strong>
                        ${b.desc}
                    </div>
                </div>
            `;
        });

        sidebarFooter.innerHTML = `
            <a href="tienda.php" style="display:block;width:100%;padding:11px;background:rgba(0,207,255,0.08);border:1px solid #00cfff;border-radius:8px;color:#00cfff;font-size:11px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;cursor:pointer;font-family:Arial;text-decoration:none;text-align:center;margin-bottom:8px;box-sizing:border-box;">🛒 Ver planes y precios</a>
            <div id="modal-pass-sb">
                <input type="password" id="sb-input-pass" placeholder="Contraseña Premium">
                <button id="sb-btn-validar">Activar</button>
            </div>
            <button class="sb-btn-activar" id="sb-btn-activar">ACTIVAR PREMIUM</button>
        `;

        // Muestra/oculta el campo de contraseña
        document.getElementById("sb-btn-activar").onclick = () => {
            const passBox = document.getElementById("modal-pass-sb");
            passBox.style.display = passBox.style.display === "block" ? "none" : "block";
        };

        // CONSULTA AL SERVIDOR: POST a activar_premium.php para activar el Premium
        document.getElementById("sb-btn-validar").onclick = () => {
            const pass = document.getElementById("sb-input-pass").value;
            // SENTENCIA CONDICIONAL: verifica la contraseña antes de activar
            if (pass === "1234") {
                fetch("activar_premium.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "accion=activar"
                })
                .then(res => res.json())
                .then(() => location.reload());
            } else {
                alert("❌ Contraseña incorrecta");
                document.getElementById("sb-input-pass").value = "";
            }
        };
    }

    sidebarPremium.style.display = "block"; // hace visible el sidebar
}


// FUNCIÓN: abre el modal para editar perfil del usuario
function abrirPerfil() {
    cerrarSidebar();

    // ARRAY DE OBJETOS: define qué campos puede editar el usuario
    const campos = [
        { campo: "username",  label: "Nuevo username",   tipo: "text"     },
        { campo: "password",  label: "Nueva contraseña", tipo: "password" },
        { campo: "documento", label: "Nuevo documento",  tipo: "number"   }
    ];

    // Construye el HTML del modal con los inputs
    let html = `<div id="modal-perfil" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;display:flex;align-items:center;justify-content:center;">
        <div style="background:#1e1e2f;border:1px solid rgba(0,207,255,0.3);border-radius:16px;padding:30px;width:320px;box-shadow:0 0 30px rgba(0,207,255,0.2);">
            <h3 style="color:#00cfff;text-align:center;margin-bottom:20px;letter-spacing:2px;">✏️ EDITAR PERFIL</h3>
            <p style="color:#aaa;font-size:0.8rem;margin-bottom:16px;text-align:center;">Deja vacío lo que no quieras cambiar</p>`;

    // SENTENCIA REPETITIVA: forEach genera un campo de input por cada elemento del array
    campos.forEach(c => {
        html += `<div style="margin-bottom:14px;">
            <label style="color:#aaa;font-size:0.75rem;display:block;margin-bottom:4px;text-transform:uppercase;">${c.label}</label>
            <input type="${c.tipo}" id="perfil-${c.campo}" placeholder="Dejar vacío para no cambiar"
                style="width:100%;padding:9px 12px;background:rgba(0,0,0,0.35);border:1px solid rgba(0,207,255,0.25);border-radius:8px;color:white;font-size:0.9rem;outline:none;">
        </div>`;
    });

    html += `<div style="margin-bottom:18px;">
            <label style="color:#ff4444;font-size:0.75rem;display:block;margin-bottom:4px;text-transform:uppercase;">⚠️ Contraseña actual (requerida)</label>
            <input type="password" id="perfil-pass-confirm" placeholder="Tu contraseña actual"
                style="width:100%;padding:9px 12px;background:rgba(0,0,0,0.35);border:1px solid rgba(255,68,68,0.4);border-radius:8px;color:white;font-size:0.9rem;outline:none;">
        </div>
        <div style="display:flex;gap:10px;">
            <button id="perfil-cancelar" style="flex:1;padding:10px;background:transparent;border:1px solid #555;border-radius:8px;color:#aaa;cursor:pointer;">Cancelar</button>
            <button id="perfil-guardar"  style="flex:1;padding:10px;background:transparent;border:1px solid #00cfff;border-radius:8px;color:#00cfff;cursor:pointer;font-weight:bold;">Guardar</button>
        </div>
        <p id="perfil-msg" style="color:#aaa;font-size:0.8rem;text-align:center;margin-top:12px;"></p>
    </div></div>`;

    document.body.insertAdjacentHTML("beforeend", html); // inserta el modal en el DOM

    document.getElementById("perfil-cancelar").onclick = () =>
        document.getElementById("modal-perfil").remove(); // elimina el modal

    // FUNCIÓN ASÍNCRONA: guarda los cambios del perfil
    document.getElementById("perfil-guardar").onclick = async () => {
        const passConfirm = document.getElementById("perfil-pass-confirm").value;
        const msg         = document.getElementById("perfil-msg");

        // SENTENCIA CONDICIONAL: la contraseña actual es obligatoria
        if (!passConfirm) {
            msg.style.color = "#ff4444";
            msg.textContent = "⚠️ Ingresa tu contraseña actual";
            return;
        }

        // FILTRADO: solo envía los campos que el usuario llenó
        const cambios = campos.filter(c =>
            document.getElementById(`perfil-${c.campo}`).value.trim() !== ""
        );

        if (!cambios.length) {
            msg.style.color = "#ff4444";
            msg.textContent = "⚠️ No ingresaste ningún cambio";
            return;
        }

        msg.style.color = "#00cfff";
        msg.textContent = "Guardando...";

        // SENTENCIA REPETITIVA for...of: envía cada campo modificado al servidor
        // Se usa for...of en lugar de forEach porque necesitamos await dentro
        for (const c of cambios) {
            const valor = document.getElementById(`perfil-${c.campo}`).value.trim();

            // CONSULTA AL SERVIDOR: POST a actualizar_usuario.php
            const res  = await fetch("actualizar_usuario.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `campo=${c.campo}&valor=${encodeURIComponent(valor)}&password_confirm=${encodeURIComponent(passConfirm)}`
            });
            const data = await res.json();

            // SENTENCIA CONDICIONAL: si hay error en un campo, detiene el proceso
            if (data.error) {
                msg.style.color = "#ff4444";
                msg.textContent = "❌ " + data.error;
                return;
            }
        }

        msg.style.color = "#00cfff";
        msg.textContent = "✅ Cambios guardados. Recargando...";
        setTimeout(() => location.reload(), 1200); // recarga tras 1.2 segundos
    };
}


// FUNCIÓN: cierra el sidebar Premium
function cerrarSidebar() {
    sidebarPremium.style.display = "none";
}

// Cerrar el sidebar al hacer clic en el overlay o en la X
sidebarOverlay.addEventListener("click", cerrarSidebar);
sidebarCerrar.addEventListener("click",  cerrarSidebar);

// Abrir el sidebar al hacer clic en "Premium" del menú
btnPremium.onclick = (e) => {
    e.preventDefault();
    abrirSidebar();
};


// ============================================================
// SECCIÓN 10 — CERRAR PANELES AL HACER CLIC AFUERA
// Un solo listener en document captura todos los clics.
// SENTENCIAS CONDICIONALES anidadas comprueban si el clic
// fue dentro o fuera de cada panel abierto.
// ============================================================

document.addEventListener("click", (e) => {
    // Cierra la biblioteca si el clic fue fuera de ella
    if (
        biblioteca.style.display === "block" &&
        !biblioteca.contains(e.target) &&
        !btnBiblioteca.contains(e.target)
    ) {
        biblioteca.style.display = "none";
    }

    // Cierra el panel premium si el clic fue fuera de él
    if (
        premium.style.display === "block" &&
        !premium.contains(e.target) &&
        !btnPremium.contains(e.target)
    ) {
        premium.style.display = "none";
    }

    // Cierra el modal de info si el clic fue sobre el fondo
    if (e.target === modalInfo) {
        modalInfo.style.display = "none";
    }
});


// ============================================================
// SECCIÓN 11 — MODAL DE INFORMACIÓN
// ============================================================

// Abre el modal y escribe la descripción del proyecto
btnInfo.onclick = () => {
    modalInfo.style.display = "flex";
    textoInfo.textContent = "WARSMUSIC es una aplicación web que desarrollé para reproducir música de forma interactiva. Permite controlar canciones, navegar por una biblioteca y acceder a contenido premium. Utilicé JavaScript para manejar eventos, manipular el DOM y controlar elementos multimedia como audio y video. Además, implementé almacenamiento local para simular funcionalidades reales de plataformas modernas.";
};

// Cierra el modal (llamada desde el botón en HTML)
function cerrarInfo() {
    modalInfo.style.display = "none";
}


// ============================================================
// SECCIÓN 12 — BARRA DE PROGRESO Y TIEMPO
// Se actualiza en tiempo real mientras el audio avanza.
// ============================================================

const progressBar  = document.getElementById("progress-bar");
const progressFill = document.getElementById("progress-fill");
const tiempoActual = document.getElementById("tiempo-actual");
const tiempoTotal  = document.getElementById("tiempo-total");


// FUNCIÓN: convierte segundos a formato mm:ss
// Ejemplo: 125 → "2:05"
function formatearTiempo(segundos) {
    const min = Math.floor(segundos / 60);       // parte entera de minutos
    const seg = Math.floor(segundos % 60);       // segundos restantes
    return min + ":" + (seg < 10 ? "0" : "") + seg; // agrega 0 si < 10
}

// EVENTO ontimeupdate: se ejecuta continuamente mientras suena el audio
reproductor.ontimeupdate = () => {
    if (!reproductor.duration) return; // evita división por cero al inicio
    const porcentaje = (reproductor.currentTime / reproductor.duration) * 100;
    progressFill.style.width = porcentaje + "%"; // actualiza el ancho del fill
    tiempoActual.textContent = formatearTiempo(reproductor.currentTime);
};

// EVENTO onloadedmetadata: se ejecuta cuando el audio carga su información
reproductor.onloadedmetadata = () => {
    tiempoTotal.textContent = formatearTiempo(reproductor.duration);
};

// EVENTO clic en la barra: permite saltar a un punto del audio
progressBar.onclick = (e) => {
    const rect       = progressBar.getBoundingClientRect(); // posición del elemento
    const x          = e.clientX - rect.left;              // posición del clic dentro de la barra
    const porcentaje = x / rect.width;                     // proporción (0 a 1)
    reproductor.currentTime = porcentaje * reproductor.duration; // salta al tiempo correspondiente
};


// ============================================================
// SECCIÓN 13 — VOLUMEN
// El input range controla el volumen del reproductor (0 a 1).
// ============================================================

const volumen = document.getElementById("volumen");

// EVENTO oninput: se dispara cada vez que el usuario mueve el slider
volumen.oninput = () => {
    reproductor.volume = volumen.value; // asigna directamente el valor al audio
};


// ============================================================
// SECCIÓN 14 — ALEATORIO Y REPETIR
// Variables booleanas que cambian con cada clic (toggle).
// ============================================================

// ALEATORIO (shuffle)
let shuffleActivo = false; // VARIABLE: false = desactivado, true = activado
const btnShuffle  = document.getElementById("btn-shuffle");

btnShuffle.onclick = () => {
    shuffleActivo = !shuffleActivo; // invierte el estado
    btnShuffle.classList.toggle("activo", shuffleActivo); // clase visual
};

// REPETIR (loop)
let repeatActivo = false;
const btnRepeat  = document.getElementById("btn-repeat");

btnRepeat.onclick = () => {
    repeatActivo       = !repeatActivo;
    btnRepeat.classList.toggle("activo", repeatActivo);
    reproductor.loop   = repeatActivo; // propiedad nativa del elemento <audio>
};

// EVENTO al terminar la canción: decide qué reproducir después
reproductor.onended = () => {
    if (repeatActivo) return; // si está en loop, el <audio> ya lo maneja solo

    // SENTENCIA CONDICIONAL: shuffle o avance normal
    if (shuffleActivo) {
        cancionActual = Math.floor(Math.random() * listaDeCanciones.length);
    } else {
        cancionActual = (cancionActual + 1) % listaDeCanciones.length;
    }
    cargarCancion();
};


// ============================================================
// SECCIÓN 15 — FAVORITOS
// Clic en el corazón → guarda/quita de la BD (POST favorito.php)
// Triple clic en "Favorito" → abre panel de favoritos (GET mis_favoritos.php)
// ============================================================

const btnFavorite    = document.getElementById("btn-favorite");
const iconoFavorito  = document.getElementById("icono-favorito");
const textoFavorito  = document.getElementById("texto-favorito");
const panelFavoritos = document.getElementById("panel-favoritos");
const listaFavoritos = document.getElementById("lista-favoritos");

let clicksFavorito = 0; // VARIABLE: contador de clics para detectar triple clic
let timerFavorito;      // VARIABLE: temporizador para resetear el contador

// CLIC EN EL CORAZÓN — agrega o quita de favoritos
iconoFavorito.addEventListener("click", (e) => {
    e.stopPropagation(); // evita que el clic llegue a otros listeners del documento
    const cancion = listaDeCanciones[cancionActual];
    if (!cancion) return;

    // CONSULTA AL SERVIDOR: POST a favorito.php
    // PHP verifica si ya existe: si sí la borra (DELETE), si no la crea (INSERT)
    fetch("favorito.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "cancion=" + encodeURIComponent(cancion)
    })
    .then(res => res.json())
    .then(data => {
        // SENTENCIA CONDICIONAL: actualiza el ícono según la respuesta del servidor
        if (data.estado === "agregado") {
            btnFavorite.classList.add("favorito");
        } else if (data.estado === "quitado") {
            btnFavorite.classList.remove("favorito");
        } else {
            alert("Debes iniciar sesión para guardar favoritos");
        }
    })
    .catch(err => console.error("Error:", err));
});


// TRIPLE CLIC EN EL TEXTO "Favorito" — abre/cierra el panel de favoritos
textoFavorito.addEventListener("click", (e) => {
    e.stopPropagation();
    clicksFavorito++;            // suma un clic
    clearTimeout(timerFavorito); // reinicia el temporizador

    // setTimeout: espera 400ms; si en ese tiempo hubo 3 clics, abre el panel
    timerFavorito = setTimeout(() => {
        if (clicksFavorito === 3) {
            // CONSULTA AL SERVIDOR: GET a mis_favoritos.php
            fetch("mis_favoritos.php")
                .then(res => res.json())
                .then(data => {
                    listaFavoritos.innerHTML = ""; // limpia la lista anterior

                    // SENTENCIA CONDICIONAL: si hay error (no hay sesión)
                    if (data.error) {
                        alert("Debes iniciar sesión para ver tus favoritos");
                        return;
                    }

                    // SENTENCIA CONDICIONAL: si no hay favoritos guardados
                    if (data.length === 0) {
                        listaFavoritos.innerHTML = "<li style='border:none; color:#666;'>No tienes favoritos aún</li>";
                    } else {
                        // SENTENCIA REPETITIVA: forEach crea un <li> por cada favorito
                        data.forEach(fav => {
                            const nombre = fav.cancion.split("/").pop().replace(".mp3", "");
                            const li     = document.createElement("li");
                            li.innerHTML = `<i class="fas fa-heart"></i> ${nombre}`;
                            li.onclick   = () => {
                                const idx = listaDeCanciones.indexOf(fav.cancion);
                                if (idx !== -1) {
                                    cancionActual = idx;
                                    cargarCancion();
                                    panelFavoritos.style.display = "none";
                                }
                            };
                            listaFavoritos.appendChild(li);
                        });
                    }

                    // Toggle del panel: si estaba abierto lo cierra y viceversa
                    panelFavoritos.style.display =
                        panelFavoritos.style.display === "block" ? "none" : "block";
                });
        }
        clicksFavorito = 0; // reinicia el contador
    }, 400);
});

// Cierra el panel de favoritos al hacer clic afuera
document.addEventListener("click", (e) => {
    if (
        panelFavoritos.style.display === "block" &&
        !panelFavoritos.contains(e.target) &&
        !btnFavorite.contains(e.target)
    ) {
        panelFavoritos.style.display = "none";
    }
});


// ============================================================
// SECCIÓN 16 — MENÚ INFERIOR (solo Premium)
// Abre opciones de Letra, Temporizador y Video.
// ============================================================

const btnMenuInferior = document.getElementById("btn-menu-inferior");
btnMenuInferior.style.display = "flex"; // siempre visible
const menuInferior = document.getElementById("menu-inferior");
const iconoInferior = document.getElementById("icono-inferior");

btnMenuInferior.onclick = () => {
    // SENTENCIA CONDICIONAL: bloquea si el usuario no es Premium
    if (!esPremium) {
        alert("⚠️ Esta función es solo para usuarios Premium");
        return;
    }

    const abierto = menuInferior.style.display === "flex";
    menuInferior.style.display = abierto ? "none" : "flex"; // toggle
    iconoInferior.classList.toggle("fa-chevron-up",   abierto);
    iconoInferior.classList.toggle("fa-chevron-down", !abierto);
};

// LETRA (próximamente)
document.getElementById("opc-letra").onclick = () => {
    alert("🎵 Función de letra próximamente");
};

// TEMPORIZADOR: apaga la música después de X minutos
const opcTemporizador = document.getElementById("opc-temporizador");
let temporizador;          // VARIABLE: referencia al setTimeout para poder cancelarlo
let tempActivo = false;    // VARIABLE: estado del temporizador

opcTemporizador.onclick = () => {
    // SENTENCIA CONDICIONAL: si ya está activo, lo cancela
    if (tempActivo) {
        clearTimeout(temporizador);
        tempActivo = false;
        opcTemporizador.style.borderColor = "rgba(123, 47, 255, 0.3)";
        opcTemporizador.style.color       = "#c084fc";
        alert("⏱️ Temporizador cancelado");
        return;
    }

    const minutos = prompt("¿En cuántos minutos quieres que se apague la música?", "30");
    if (!minutos || isNaN(minutos) || minutos <= 0) return; // validación

    tempActivo = true;
    opcTemporizador.style.borderColor = "#00cfff";
    opcTemporizador.style.color       = "#00cfff";

    // setTimeout: ejecuta la función después de (minutos * 60 * 1000) milisegundos
    temporizador = setTimeout(() => {
        reproductor.pause();
        btnPlay.classList.replace("fa-pause", "fa-play");
        tempActivo = false;
        opcTemporizador.style.borderColor = "rgba(123, 47, 255, 0.3)";
        opcTemporizador.style.color       = "#c084fc";
        alert("⏱️ Temporizador terminado — música apagada");
    }, minutos * 60 * 1000);

    alert(`⏱️ La música se apagará en ${minutos} minutos`);
};

// VIDEO: abre el panel de videos
document.getElementById("opc-video").onclick = () => {
    biblioteca.style.display = "none";
    premium.style.display = premium.style.display === "block" ? "none" : "block";
};