<?php
require_once 'config_sesion.php';
// Actualizacion del timestamp de ultimo acceso
// impide que la que la sesion expire por inactividad siempre y cuando el usuario esta utilizando la pagina
echo json_encode(['status' => 'ok', 'time' => time()]);
?>