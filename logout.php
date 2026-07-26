<?php
session_start();

// Limpiar todas las variables de sesion
$_SESSION = [];

// eliminar cookie de sesion con SameSite
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict'  // ← SameSite realizado
    ]);
}

// Borrar la sesion
session_destroy();

header("Location: login.php?logout=1");
exit();
?>