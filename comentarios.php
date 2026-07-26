<?php
class comentarios {
    public $idProducto;
    public $nombreUsuario;
    public $calificacion;
    public $comentario;
    public $fecha;
    
    public function __construct($idProducto, $nombreUsuario, $calificacion, $comentario) {
        $this->idProducto = $idProducto;
        $this->nombreUsuario = $nombreUsuario;
        $this->calificacion = $calificacion;
        $this->comentario = $comentario;
        $this->fecha = date("Y-m-d H:i:s");
    }
    
    public function mostrarEstrellas() {
        $estrellas = "";
        for ($i = 1; $i <= 5; $i++) {
            $estrellas .= ($i <= $this->calificacion) ? "★" : "☆";
        }
        return $estrellas;
    }
}

// Guardar comentarios
function guardarcomentarios($idProducto, $nombreUsuario, $calificacion, $comentario) {
    if ($calificacion < 1 || $calificacion > 5) {
        return "Calificacion debe ser entre entre el rango 1 y 5";
    }
    
    // Utilizar nombre diferente para el objeto
    $nuevoComentario = new comentarios($idProducto, $nombreUsuario, $calificacion, $comentario);
    
    // Utilizar nombre diferente para el array
    $listaComentarios = [];
    if (file_exists('comentarios.txt')) {
        $listaComentarios = json_decode(file_get_contents('comentarios.txt'), true) ?: [];
    }
    
    $listaComentarios[] = [
        'idProducto' => $nuevoComentario->idProducto,
        'nombreUsuario' => $nuevoComentario->nombreUsuario,
        'calificacion' => $nuevoComentario->calificacion,
        'comentario' => $nuevoComentario->comentario,
        'fecha' => $nuevoComentario->fecha
    ];
    
    file_put_contents('comentarios.txt', json_encode($listaComentarios, JSON_PRETTY_PRINT));
    
    return "Comentario guardado exitosamente";
}
?>