<?php
require_once 'config_sesion.php';

// Verificar autenticacion
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar MFA completado
if (!isset($_SESSION['mfa_verificado']) || $_SESSION['mfa_verificado'] !== true) {
    header("Location: login.php");
    exit();
}
?>