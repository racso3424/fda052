<?php
require 'db_conexion.php';

if (isset($_GET['matricula'])) {
    $matricula = $_GET['matricula'];

    $query = "SELECT * FROM alumnos_estadia WHERE matricula = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $matricula);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(array("success" => true, "empresa" => $data['empresa'], "departamento" => $data['departamento'], "proyecto" => $data['proyecto'], "nombre_tutor" => $data['nombre_tutor'], "correo_tutor" => $data['correo_tutor'], "telefono_tutor" => $data['telefono_tutor']));
    } else {
        echo json_encode(array("success" => false));
    }
    
    $stmt->close();
} else {
    echo json_encode(array("success" => false));
}

$conn->close();
?>
