<?php
/**
 *Pendiente de decisión del equipo
 */

// ==============
// INTERFAZ: Define el contrato para todas las pasarelas
// ==============
interface PasarelaPagoInterface {
    /**
     * Iniciar un nuevo proceso de pago
     * @param array $datos Datos de la transacción
     * @return array Token, URL de redirección y estado
     */
    public function iniciarPago(array $datos): array;

    /**
     * Confirmar el estado de un pago iniciado
     * @param string $token Token de la transacción
     * @return bool True si el pago fue aprobado
     */
    public function confirmarPago(string $token): bool;

    /**
     * Reversar/cancelar un pago
     * @param string $token Token de la transacción
     * @return bool True si se reversó correctamente
     */
    public function reversarPago(string $token): bool;

    /**
     * Obtener el estado actual de una transacción
     * @param string $token Token de la transacción
     * @return string Estado: 'iniciado', 'aprobado', 'rechazado', 'reversado'
     */
    public function obtenerEstado(string $token): string;

    /**
     * Obtener el nombre de la pasarela
     * @return string Nombre identificador
     */
    public function getNombre(): string;
}

// ============================================
// ADAPTER: WebPay Plus (Transbank - Chile)
// Opción A - Preferida por @compañero-carlos
// ============================================
class WebPayAdapter implements PasarelaPagoInterface {
    private $apiKey;
    private $apiSecret;
    private $ambiente; // 'integracion' o 'produccion'
    private $endpoint;

    public function __construct() {
        $this->apiKey = $_ENV['WEBPAY_API_KEY'] ?? '';
        $this->apiSecret = $_ENV['WEBPAY_API_SECRET'] ?? '';
        $ambiente = $_ENV['WEBPAY_AMBIENTE'] ?? 'integracion';
        $this->endpoint = $ambiente === 'produccion' 
            ? 'https://webpay3g.transbank.cl' 
            : 'https://webpay3gint.transbank.cl';
    }

    public function iniciarPago(array $datos): array {
        // Validar datos mínimos requeridos
        if (empty($datos['monto']) || empty($datos['orden']) || empty($datos['url_retorno'])) {
            return ['error' => 'Datos incompletos para iniciar pago'];
        }

        try {
            // En producción, usar SDK oficial de Transbank
            // require_once 'vendor/transbank/transbank-sdk-php/init.php';
            // $transaction = new Transaction();
            // $response = $transaction->create(...);

            // Simulación para ambiente de desarrollo
            $token = 'WP_' . bin2hex(random_bytes(16));

            return [
                'token' => $token,
                'url' => $this->endpoint . '/webpayserver/initTransaction',
                'estado' => 'iniciado',
                'monto' => $datos['monto'],
                'orden' => $datos['orden']
            ];

        } catch (Exception $e) {
            error_log("[WebPayAdapter::iniciarPago] Error: " . $e->getMessage());
            return ['error' => 'Error al iniciar transacción'];
        }
    }

    public function confirmarPago(string $token): bool {
        try {
            // En producción:
            // $transaction = new Transaction();
            // $response = $transaction->commit($token);
            // return $response->isApproved();

            // Simulación: validar formato del token
            return strpos($token, 'WP_') === 0;

        } catch (Exception $e) {
            error_log("[WebPayAdapter::confirmarPago] Error: " . $e->getMessage());
            return false;
        }
    }

    public function reversarPago(string $token): bool {
        try {
            // En producción:
            // $transaction = new Transaction();
            // $response = $transaction->refund($token, $monto);
            // return $response->getType() === 'REVERSED';

            return true; // Simulación
        } catch (Exception $e) {
            error_log("[WebPayAdapter::reversarPago] Error: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstado(string $token): string {
        // En producción: consultar estado real en Transbank
        return 'desconocido';
    }

    public function getNombre(): string {
        return 'WebPay Plus (Transbank)';
    }
}

// ============================================
// ADAPTER: Stripe (Opción B - @compañero-ana)
// ============================================
class StripeAdapter implements PasarelaPagoInterface {
    private $apiKey;
    private $webhookSecret;

    public function __construct() {
        $this->apiKey = $_ENV['STRIPE_API_KEY'] ?? '';
        $this->webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';
    }

    public function iniciarPago(array $datos): array {
        try {
            // En producción: usar Stripe PHP SDK
            // \Stripe\Stripe::setApiKey($this->apiKey);
            // $session = \Stripe\Checkout\Session::create([...]);

            $token = 'ST_' . bin2hex(random_bytes(16));

            return [
                'token' => $token,
                'url' => 'https://checkout.stripe.com/pay/' . $token,
                'estado' => 'iniciado',
                'monto' => $datos['monto'],
                'orden' => $datos['orden']
            ];
        } catch (Exception $e) {
            error_log("[StripeAdapter::iniciarPago] Error: " . $e->getMessage());
            return ['error' => 'Error al iniciar sesión de Stripe'];
        }
    }

    public function confirmarPago(string $token): bool {
        // Implementar con Stripe SDK
        return strpos($token, 'ST_') === 0;
    }

    public function reversarPago(string $token): bool {
        // Stripe permite reembolsos via API
        return true;
    }

    public function obtenerEstado(string $token): string {
        return 'desconocido';
    }

    public function getNombre(): string {
        return 'Stripe';
    }
}

// ============================================
// ADAPTER: PayPal (Opción C - @compañero-luis)
// ============================================
class PayPalAdapter implements PasarelaPagoInterface {
    private $clientId;
    private $clientSecret;
    private $sandbox;

    public function __construct() {
        $this->clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['PAYPAL_CLIENT_SECRET'] ?? '';
        $this->sandbox = ($_ENV['PAYPAL_SANDBOX'] ?? 'true') === 'true';
    }

    public function iniciarPago(array $datos): array {
        try {
            $token = 'PP_' . bin2hex(random_bytes(16));

            return [
                'token' => $token,
                'url' => 'https://www.paypal.com/checkoutnow?token=' . $token,
                'estado' => 'iniciado',
                'monto' => $datos['monto'],
                'orden' => $datos['orden']
            ];
        } catch (Exception $e) {
            error_log("[PayPalAdapter::iniciarPago] Error: " . $e->getMessage());
            return ['error' => 'Error al crear orden PayPal'];
        }
    }

    public function confirmarPago(string $token): bool {
        return strpos($token, 'PP_') === 0;
    }

    public function reversarPago(string $token): bool {
        return true;
    }

    public function obtenerEstado(string $token): string {
        return 'desconocido';
    }

    public function getNombre(): string {
        return 'PayPal';
    }
}

// ============================================
// FACTORY: Crea la pasarela configurada
// ============================================
class PasarelaPagoFactory {
    /**
     * Crear instancia de la pasarela configurada
     * @param string|null $tipo Tipo de pasarela (webpay, stripe, paypal). Null = usa configuración
     * @return PasarelaPagoInterface
     */
    public static function crear(?string $tipo = null): PasarelaPagoInterface {
        $tipo = $tipo ?? $_ENV['PASARELA_PAGO_DEFAULT'] ?? 'webpay';

        switch (strtolower($tipo)) {
            case 'webpay':
                return new WebPayAdapter();
            case 'stripe':
                return new StripeAdapter();
            case 'paypal':
                return new PayPalAdapter();
            default:
                throw new Exception("Pasarela de pago '{$tipo}' no soportada");
        }
    }

    /**
     * Obtener lista de pasarelas disponibles
     * @return array Lista con nombre y descripción
     */
    public static function obtenerDisponibles(): array {
        return [
            ['id' => 'webpay', 'nombre' => 'WebPay Plus (Transbank)', 'pais' => 'Chile', 'comision' => '2.5%'],
            ['id' => 'stripe', 'nombre' => 'Stripe', 'pais' => 'Global', 'comision' => '2.9% + $0.30'],
            ['id' => 'paypal', 'nombre' => 'PayPal', 'pais' => 'Global', 'comision' => '5.4%'],
        ];
    }
}

// ============================================
// TABLA MySQL para transacciones de pago
// ============================================
/*
CREATE TABLE TRANSACCION_PAGO (
    id_transaccion INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    token_pasarela VARCHAR(255) NOT NULL,
    pasarela ENUM('webpay', 'stripe', 'paypal') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    estado ENUM('iniciado', 'aprobado', 'rechazado', 'reversado') DEFAULT 'iniciado',
    respuesta_json TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_token (token_pasarela),
    INDEX idx_compra (id_compra),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/
?>