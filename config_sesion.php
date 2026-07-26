<?php
// ==============
// configuracion basada em samesite
// ==============

// Configuracion con SameSite Strict
session_set_cookie_params([
    'lifetime' => 7200,
    'path' => '/',
    'domain' => '',
    'secure' => false,      
    'httponly' => true,    
    'samesite' => 'Strict'  // ← PREVIENE ATAQUES CSRF
]);

// Iniciar sesion 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==============
// control de inactividad
// ==============
$tiempo_maximo_inactividad = 1800; // 30 minutos

if (isset($_SESSION['ultimo_acceso'])) {
    $inactividad = time() - $_SESSION['ultimo_acceso'];
    if ($inactividad > $tiempo_maximo_inactividad) {
        // Sesion cerrada por inactividad
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'samesite' => 'Strict'
        ]);
        header("Location: login.php?expirado=1");
        exit();
    }
}

// Actualizar timestamp de ultimo acceso
$_SESSION['ultimo_acceso'] = time();

// ==============
// vinculacion de sesion a ip y user-agent 
// ==============

$ip_actual = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua_actual = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (isset($_SESSION['ip_usuario']) && isset($_SESSION['ua_usuario'])) {
    if ($_SESSION['ip_usuario'] !== $ip_actual || $_SESSION['ua_usuario'] !== $ua_actual) {
        // Posible secuestro de sesion
        $_SESSION = [];
        session_destroy();
        header("Location: login.php?hijacking=1");
        exit();
    }
} else {
    // Almacenar datos
    $_SESSION['ip_usuario'] = $ip_actual;
    $_SESSION['ua_usuario'] = $ua_actual;
}
?>