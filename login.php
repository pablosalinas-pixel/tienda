<?php
// Usar los mismos parametros de cookie que el resto del sitio (config_sesion.php)
session_set_cookie_params([
    'lifetime' => 7200,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Si ya esta logueado y MFA verificado, redirigir
if (isset($_SESSION['usuario_id']) && isset($_SESSION['mfa_verificado']) && $_SESSION['mfa_verificado'] === true) {
    header("Location: index.php");
    exit();
}

$mensaje = "";
$mfa_pendiente = false;

if (isset($_GET['expirado'])) $mensaje = "Tu sesion expiro por inactividad. Inicia sesion nuevamente.";
if (isset($_GET['hijacking'])) $mensaje = "Sesion invalidada por seguridad. Inicia sesion nuevamente.";
if (isset($_GET['logout'])) $mensaje = "Sesion cerrada correctamente.";

// ==============
// autentificacion (Usuario/Password)
// ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paso']) && $_POST['paso'] === 'login') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($usuario === 'admin' && $password === '123456') {
        // Autenticacion primaria exitosa - Generar codigo MFA
        session_regenerate_id(true);
        
        $_SESSION['usuario_id_temp'] = 1;
        $_SESSION['usuario_nombre_temp'] = $usuario;
        $_SESSION['mfa_codigo'] = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['mfa_expira'] = time() + 300; // 5 minutos
        
        $mfa_pendiente = true;
        $mensaje = "Codigo MFA: " . $_SESSION['mfa_codigo'] . " (En produccion: SMS/Email)";
        
    } else {
        $mensaje = "Usuario o contraseña incorrectos.";
    }
}

// ==============
// verificacion mediante MFA
// ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paso']) && $_POST['paso'] === 'mfa') {
    $codigo_ingresado = $_POST['codigo_mfa'] ?? '';
    
    if (!isset($_SESSION['mfa_codigo']) || !isset($_SESSION['mfa_expira'])) {
        $mensaje = "Sesion MFA incorrecta.";
        $mfa_pendiente = false;
    } elseif (time() > $_SESSION['mfa_expira']) {
        $mensaje = "Codigo MFA expirado.";
        unset($_SESSION['mfa_codigo'], $_SESSION['mfa_expira'], $_SESSION['usuario_id_temp']);
        $mfa_pendiente = false;
    } elseif ($codigo_ingresado === $_SESSION['mfa_codigo']) {
        // MFA correcto
        $_SESSION['usuario_id'] = $_SESSION['usuario_id_temp'];
        $_SESSION['usuario_nombre'] = $_SESSION['usuario_nombre_temp'];
        $_SESSION['ultimo_acceso'] = time();
        $_SESSION['ip_usuario'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_SESSION['ua_usuario'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['mfa_verificado'] = true;
        
        unset($_SESSION['mfa_codigo'], $_SESSION['mfa_expira'], 
              $_SESSION['usuario_id_temp'], $_SESSION['usuario_nombre_temp']);
        
        header("Location: index.php");
        exit();
    } else {
        $mensaje = "Codigo MFA incorrecto.";
        $mfa_pendiente = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesion - Tienda</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-box { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .login-box input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 6px; }
        .login-box button { width: 100%; padding: 12px; background: #0078d7; color: white; border: none; border-radius: 6px; cursor: pointer; }
        .login-box button:hover { background: #005db1; }
        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        .alert-success { background: #d4edda; color: #155724; }
        .mfa-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #0078d7; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2 style="text-align:center; margin-bottom: 20px;">
            <?php echo $mfa_pendiente ? 'Verificacion MFA' : 'Iniciar Sesion'; ?>
        </h2>
        
        <?php if ($mensaje): ?>
            <div class="alert <?php echo (strpos($mensaje, 'Codigo MFA') !== false) ? 'alert-success' : ((strpos($mensaje, 'correctamente') !== false || strpos($mensaje, 'Inicia') !== false) ? 'alert-info' : 'alert-error'); ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$mfa_pendiente): ?>
            <form method="POST" action="">
                <input type="hidden" name="paso" value="login">
                <label>Usuario:</label>
                <input type="text" name="usuario" required placeholder="admin">
                <label>Contraseña:</label>
                <input type="password" name="password" required placeholder="123456">
                <button type="submit">Ingresar</button>
            </form>
            <p style="text-align:center; margin-top:15px; color:#666; font-size:14px;">
                Demo: usuario <strong>admin</strong> / contraseña <strong>123456</strong>
            </p>
        <?php else: ?>
            <div class="mfa-box">
                <p>Ingrese el codigo de 6 digitos.</p>
                <form method="POST" action="">
                    <input type="hidden" name="paso" value="mfa">
                    <label>Codigo MFA:</label>
                    <input type="text" name="codigo_mfa" required maxlength="6" pattern="\d{6}" 
                           placeholder="000000" style="text-align: center; font-size: 20px; letter-spacing: 5px;">
                    <button type="submit" style="margin-top: 10px;">Verificar</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>