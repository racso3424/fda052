<?php
session_start();
require 'db_conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['no_empleado']; // Correo o número de empleado ingresado
    $password = $_POST['password']; // Nombre del tutor ingresado

    // Verificar si es un empleado
    $query = "SELECT * FROM empleados WHERE no_empleado = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $empleado = $result->fetch_assoc();
        $_SESSION['no_empleado'] = $usuario;

        if ($empleado['rol'] == 'coordinador') {
            header("Location: asignar.php");
        } elseif ($empleado['rol'] == 'maestro') {
            header("Location: maestro.php");
        } else {
            $error = "Rol no reconocido.";
        }
        exit();
    }

    // Verificar si es un tutor de estadía
    $query = "SELECT * FROM alumnos_estadia WHERE correo_tutor = ? AND nombre_tutor = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $usuario, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Guardar los datos del tutor en la sesión
        $tutor = $result->fetch_assoc();
        $_SESSION['correo_tutor'] = $usuario;
        $_SESSION['nombre_tutor'] = $tutor['nombre_tutor']; // Guardar el nombre del tutor en la sesión
        
        header("Location: tutor_estadia.php"); // Página del tutor
        exit();
    }

    // Verificar si es un alumno y que la contraseña sea su matrícula
    $query = "SELECT * FROM alumnos WHERE matricula = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Verificar si la contraseña ingresada coincide con la matrícula
        if ($usuario === $password) {
            // Guardar la matrícula y el nombre del alumno en la sesión
            $alumno = $result->fetch_assoc();
            $_SESSION['matricula'] = $usuario;
            $_SESSION['nombre'] = $alumno['nombre']; // Guardar el nombre del alumno en la sesión
            
            header("Location: alumno.php"); // Página del alumno
            exit();
        } else {
            $error = "La contraseña debe ser igual a la matrícula.";
        }
    } else {
        $error = "No se pudo iniciar sesión. Verifica tu matrícula.";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="./bootstrap-5.3.3-dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="col-md-4">
            <div class="text-center mb-4">
                <img src="UTC_logo.png" alt="Logo UTC" class="img-fluid" style="max-width: 150px;">
            </div>
            <h2 class="text-center">Inicio de Sesión</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="no_empleado">Correo del Tutor o Número de Empleado:</label>
                    <input type="text" class="form-control" id="no_empleado" name="no_empleado" required>
                </div>
                <div class="form-group">
                    <label for="password">Nombre del Tutor o Contraseña:</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Iniciar Sesión</button>
            </form>
        </div>
    </div>
</body>
</html>
