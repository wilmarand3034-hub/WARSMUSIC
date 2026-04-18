// Todos estos son los elementos del HTML para poder manipularlos con JS
const reproductor = document.getElementById("reproductor"); // audio principal, lo que hace que se reproduzca la música
const btnPlay = document.getElementById("btn-play"); // botón play/pausa
const btnNext = document.getElementById("btn-next"); // boton pasa a la siguiente canción
const btnPrev = document.getElementById("btn-prev"); // boton pasa a la canción anterior

const btnBiblioteca = document.getElementById("btn-biblioteca"); // boton con el que se abre la ventana de la biblioteca
const biblioteca = document.getElementById("biblioteca"); // este es el contenido de la biblioteca

const btnPremium = document.getElementById("btn-premium"); // botón con el que se abre la sección premium
const premium = document.getElementById("premium"); // este es el contenido de la sección premium

const nombreCancion = document.getElementById("nombre-cancion"); // este es el que deja ver el texto del nombre de cada cancion

const btnPro = document.getElementById("btn-pro"); // botón para activar PRO
const estadoPremium = document.getElementById("estado-premium"); // indicador que hace que visual que ya es usuario premium (✅)

const modal = document.getElementById("modal-pass"); // esta es la ventana donde se pone la contraseña para activar el premium
const btnValidar = document.getElementById("btn-validar"); // botón para validar la contraseña y activar el premium (Entrar)
const inputPass = document.getElementById("input-pass"); // este es el cajon donde se ingresa la contraseña

const btnInfo = document.getElementById("btn-info"); // botón donde està la informacion de la pagina
const modalInfo = document.getElementById("modal-info"); // la ventana de informacion
const textoInfo = document.getElementById("texto-info"); // el texto que está dentro de la ventana de informacion


// Lista de canciones
const listaDeCanciones = [  // Este es un array con la ruta de cada cancion que se une con la otra lista del HTML
    "./musica/", // nos trae las canciones de esta carpeta que es la carpeta de musica.
    "./musica/Wisin & Yandel - Yo Te Quiero.mp3",
    "./musica/CHUCHA.mp3",
    "./musica/BOQUISUCIO BLESSD KRIS R GEEZYDEE YOUNGFATTY TURY CARABIN3.mp3",
    "./musica/Milky Chance - Stolen Dance (Official Video).mp3",
    "./musica/No Puedo Estar Sin Sex.mp3",
    "./musica/Prrrum (Remix).mp3",
    "./musica/KRIS R  GEEZYDEE - TUKI TUKI.mp3",
    "./musica/KRIS R - GANAS  (VIDEO OFICIAL).mp3",
    "./musica/W Sound 08 Pico y Chao - Kris R, Westcol, Ovy On The Drums.mp3",
    "./musica/Yaga y Mackie -  Acechandote.mp3",
    "./musica/Ricardo Arjona - Historia De Taxi  (Video).mp3",
    "./musica/Entre Amigos, Daniel Calderón & Los Gigantes Del Vallenato, Video Letra - Sentir Vallenato.mp3"
];


const listaDeVideos = [ // Este es un array con la ruta de cada video que se une con la otra lista del HTML
 "https://nosejas1025.sirv.com/KRIS%20R%20-%20GANAS%20%20(VIDEO%20OFICIAL).mp4",
    "https://nosejas1025.sirv.com/Entre%20Amigos%20-%20Los%20Gigantes%20del%20Vallenato!.mp4",
    "https://nosejas1025.sirv.com/Ricardo%20Arjona%20-%20Historia%20De%20Taxi%20%20(Video).mp4",
    "https://nosejas1025.sirv.com/Wisin%20Y%20Yandel%20-''Yo%20Te%20Quiero''VIDEO%20OFICIAL.mp4",
    "https://nosejas1025.sirv.com/Si%20No%20Regresas%2C%20Binomio%20De%20Oro%20De%20Am%C3%A9rica%2C%20Video%20Letra%20-%20Sentir%20Vallenato.mp4",
    "https://nosejas1025.sirv.com/Setaoc%20Mass%20_%20Boiler%20Room%20x%20Glitch%20Festival%202025%20%5B1E3WsoHSxNM%5D.mp4"
];

let cancionActual = 0; // esto nos ayuda a que siempre comiense desde la cancion numero 0, es decir la primera canciòn.


// Esta es la funcion de los iconos de play, pausa, siguiente y anterior
btnPlay.onclick = () => {
    if (reproductor.paused) {
        reproductor.play();
        btnPlay.classList.replace("fa-play", "fa-pause");
    } else {
        reproductor.pause();
        btnPlay.classList.replace("fa-pause", "fa-play");
    }
};

btnNext.onclick = () => { // esto hace que al dar click en el boton de siguiente se pase a la siguiente cancion, y si es la ultima cancion vuelva a la primera.
    cancionActual = (cancionActual + 1) % listaDeCanciones.length;
    cargarCancion();
};

btnPrev.onclick = () => { // esto hace que al dar click en el boton de anterior se pase a la cancion anterior, y si es la primera cancion vuelva a la ultima.
    cancionActual =
        (cancionActual - 1 + listaDeCanciones.length) % listaDeCanciones.length;
    cargarCancion();
};

// Esta función carga la canción actual en el reproductor y actualiza el nombre de la canción.
function cargarCancion() {
    reproductor.src = listaDeCanciones[cancionActual];
    reproductor.play();
    btnPlay.classList.replace("fa-play", "fa-pause");
    actualizarNombre();
}

// Esta funcion lo que hace es quitar el nombre del archivo de la cancion y la carpeta y asi que solo se vea el nombre de la cancion actual, como yo lo tengo en el HTML.
function actualizarNombre() {
    let nombre = listaDeCanciones[cancionActual]
        .replace("./musica/", "")
        .replace(".mp3", "");

    nombreCancion.textContent = nombre;
}

// Este es para que yo asl dar click en el nombre de la cancion en la biblioteca y se reproduzca esa cancion, es decir que se seleccione esa cancion.
function seleccionarCancion(index) {
    cancionActual = index;
    cargarCancion();
}

reproductor.onended = () => btnNext.click(); // Esto hace que al terminar la canción se pase automáticamente a la siguiente.

reproductor.src = listaDeCanciones[0]; // esto siempre va a dejar ver la primera cancion al cargar la pagina, de momento no tengo una cancion sino un archivo vacio, y asi se ve en blanco la primera cancion.
actualizarNombre();


// este es para que yo al dar click en el nombre del video en la biblioteca se reproduzca ese video, es decir que se seleccione ese video.

function reproducirVideo(index) {
    const video = document.getElementById("videoPlayer"); // este es el elemento de video del HTML

    video.pause(); // pausa el video actual antes de cargar el nuevo, para evitar que se reproduzcan ambos al mismo tiempo
    video.src = listaDeVideos[index];
    video.load(); // carga el nuevo video
    video.play();
    
    video.playbackRate = 1.0; // esta es la velocidad del video (1 = normal)

    video.play(); // reproduce el video seleccionado
}


// LA BIBLIOTECA

btnBiblioteca.onclick = (e) => { 
    e.preventDefault(); // esto es para evitar que me saque de la pagina-URL y abra una simple ventana de la biblioteca

    premium.style.display = "none"; // Cierra la sección premium si estaba abierta

    biblioteca.style.display = // lo que hace es que al dar click en biblioteca, se abre o se cierra.
        biblioteca.style.display === "block" ? "none" : "block";
};



//  PREMIUM

if (localStorage.getItem("premium") === "true") { // esto lo que hace es que al salirse de la pagina no les quite la licencia premium.
    estadoPremium.textContent = "✅"; // muestra el chulito si es premium.
}

// Click en menú premium
btnPremium.onclick = (e) => { 
    e.preventDefault(); // esto es para evitar que me saque de la pagina-URL y abra una simple ventana premium.

    if (localStorage.getItem("premium") === "true") {
        abrirPremium();
    } else {
        alert("⚠️ No eres usuario Premium"); // si le doy a este boton sin ser premium me va a salir esta alerta, y no me va a dejar entrar a la sección premium.
    }
};

// Click botón PRO - abre modal - osea la ventana para ingresar la contraseña
btnPro.onclick = () => {
    modal.style.display = "flex";
};

// Validar contraseña - lo que mira si la contraseña es para activar o quitar el premium.
btnValidar.onclick = () => {
    const pass = inputPass.value;

    // CONTRASEÑA PARA ACTIVAR
    if (pass === "1234") {
        localStorage.setItem("premium", "true");
        estadoPremium.textContent = "✅";
        modal.style.display = "none";
        inputPass.value = "";
        abrirPremium();
        alert("¡Acceso Premium Activado!"); // esta es la alerta que nos tra al momento de ser premium 
    } 
    
    // CONTRASEÑA PARA QUITAR (Cámbiala por la que quieras)
    else if (pass === "9999") { 
        localStorage.removeItem("premium");
        estadoPremium.textContent = "";
        modal.style.display = "none";
        premium.style.display = "none"; // Cierra la sección si estaba abierta
        inputPass.value = "";
        alert("Licencia Removida"); // esta es la alerta que nos trae al momento de quitar el premium
        location.reload(); // Recarga para limpiar el estado de la app
    } 
    
    else {
        alert("❌ Contraseña incorrecta");
        inputPass.value = "";
    }
};

// Abrir premium
function abrirPremium() {
    biblioteca.style.display = "none";

    premium.style.display =
        premium.style.display === "block" ? "none" : "block";
}


// Esto lo que hace es que al momento de dar por fuera de la ventana se cierre lo que esté abierto, ya sea la biblioteca, la sección premium o el modal de contraseña. Es decir, si yo tengo abierta la biblioteca y doy click por fuera de ella, se cierra la biblioteca. Lo mismo para el premium y el modal.

document.addEventListener("click", (e) => {

    // Cerrar biblioteca
    if (
        biblioteca.style.display === "block" &&
        !biblioteca.contains(e.target) &&
        !btnBiblioteca.contains(e.target)
    ) {
        biblioteca.style.display = "none";
    }

    // Cerrar premium
    if (
        premium.style.display === "block" &&
        !premium.contains(e.target) &&
        !btnPremium.contains(e.target)
    ) {
        premium.style.display = "none";
    }

    // Cerrar modal - la ventana de contraseña
    if (e.target === modal) {
        modal.style.display = "none";
    }
});

function quitarPremium() {
    // borrar premium
    localStorage.removeItem("premium");

    // quitar el chulito
    estadoPremium.textContent = "";

    // cerrar sección premium si está abierta
    premium.style.display = "none";
}


btnInfo.onclick = () => {
    modalInfo.style.display = "flex";

    textoInfo.textContent = "WARSMUSIC es una aplicación web que desarrollé para reproducir música de forma interactiva. Permite controlar canciones, navegar por una biblioteca y acceder a contenido premium. Utilicé JavaScript para manejar eventos, manipular el DOM y controlar elementos multimedia como audio y video. Además, implementé almacenamiento local para simular funcionalidades reales de plataformas modernas.";
}; // Esta es la información que hay en el boton de info

function cerrarInfo() { //este es el boton para cerrar la ventana de informacion
    modalInfo.style.display = "none";
}

