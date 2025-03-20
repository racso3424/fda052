<?php
// Supongamos que la matrícula y el nombre están almacenados en variables de sesión
session_start();
$matricula = $_SESSION['matricula']; // O puedes obtenerla desde $_POST o $_GET si es necesario

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdfFile'])) {
    // Verificar si se ha subido un archivo PDF
    if ($_FILES['pdfFile']['error'] == 0) {
        $file_tmp = $_FILES['pdfFile']['tmp_name'];
        $file_name = $_FILES['pdfFile']['name'];
        $file_size = $_FILES['pdfFile']['size'];
        $file_type = $_FILES['pdfFile']['type'];

        // Asegurarse de que el archivo es un PDF
        if ($file_type == 'application/pdf') {
            // Crear un nuevo nombre para el archivo con la matrícula, el nombre del alumno y "evaluacion1"
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION); // Obtener la extensión del archivo
            $new_file_name = $matricula . "_" . "_evaluacion1." . $file_extension; // Nombre del archivo con matrícula, nombre y "evaluacion1"

            // Definir la carpeta de destino
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $target_file = $upload_dir . $new_file_name;

            // Mover el archivo a la carpeta de destino con el nuevo nombre
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Redirigir a alumno.php después de un éxito
                header("Location: alumno.php?success=1");
                exit(); // Asegura que el script termine aquí
            } else {
                // Redirigir a alumno.php con error si no se pudo mover el archivo
                header("Location: alumno.php?error=1");
                exit(); // Asegura que el script termine aquí
            }
        } else {
            // Redirigir a alumno.php si el archivo no es un PDF
            header("Location: alumno.php?error=2");
            exit(); // Asegura que el script termine aquí
        }
    } else {
        // Redirigir a alumno.php si hubo un error en la carga
        header("Location: alumno.php?error=3");
        exit(); // Asegura que el script termine aquí
    }
}
?>
