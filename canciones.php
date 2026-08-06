<?php
header("Content-Type: application/json");
include "conexion.php";

$resultado = mysqli_query($conn, "SELECT * FROM canciones");
$canciones = [];

while ($fila = mysqli_fetch_assoc($resultado)) {
    $canciones[] = $fila;
}

echo json_encode($canciones);

?>