<?php
session_start();
require 'db_conexion.php';

date_default_timezone_set('America/Mexico_City'); // Ajusta según tu ubicación
$fecha_actual = date('Y-m-d');



// Consultar la fecha permitida de evaluación
$query_fecha = "SELECT evaluacion3 FROM fechas_evaluacion LIMIT 1";
$result_fecha = $conn->query($query_fecha);
$row_fecha = $result_fecha->fetch_assoc();

$fecha_permitida = $row_fecha['evaluacion3'] ?? null;
$evaluacion_habilitada = ($fecha_actual === $fecha_permitida);

// Si la fecha no coincide, evitar el registro
if (!$evaluacion_habilitada) {
    echo "<script>alert('No se puede registrar la evaluación en esta fecha. La fecha permitida es $fecha_permitida.'); window.location.href='maestro.php';</script>";
    exit();
}

// Obtener matrícula si se envía por GET
$matricula = isset($_GET['matricula']) ? $_GET['matricula'] : '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $evaluacion_habilitada) {
    $matricula = $_POST['matricula'];
    $notas = [];

    // Capturar las 10 calificaciones
    for ($i = 1; $i <= 10; $i++) {
        $notas[] = isset($_POST[$i]) ? (int)$_POST[$i] : 0;
    }

    // Calcular el promedio
    $promedio = array_sum($notas) / count($notas);

    // Verificar si la matrícula ya tiene evaluación
    $stmt_check = $conn->prepare("SELECT * FROM evaluacion_3 WHERE matricula = ?");
    $stmt_check->bind_param("s", $matricula);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo "<script>alert('Esta matrícula ya tiene una evaluación registrada.');</script>";
    } else {
        // Insertar datos
        $stmt_insert = $conn->prepare("INSERT INTO evaluacion_3 (matricula, `uno`, `dos`, `tres`, `cuatro`, `cinco`, `seis`, `siete`, `ocho`, `nueve`, `diez`, promedio_2) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Crear la cadena de tipos dinámicamente
        $types = "s" . str_repeat("i", count($notas)) . "i";

        // Combinar todos los parámetros en un solo array
        $params = array_merge([$matricula], $notas, [$promedio]);

        // Pasar los parámetros desglosados
        $stmt_insert->bind_param($types, ...$params);

        if ($stmt_insert->execute()) {
            echo "<script>alert('Evaluación registrada con éxito.'); window.location.href='evaluacion_1.php';</script>";
        } else {
            echo "<script>alert('Error al registrar la evaluación.');</script>";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación 3</title>
</head>
<body>
    <h2>Registrar Evaluación</h2>
    <p><strong>Fecha permitida para la evaluación:</strong> <?php echo htmlspecialchars($fecha_permitida); ?></p>

    <form method="POST" action="">
        <label>Matrícula:</label>
        <input type="text" name="matricula" value="<?php echo htmlspecialchars($matricula); ?>" required readonly><br><br>

        <?php 
        $aspectos = [
            "1.- Cumplimiento de las normas generales de seguridad",
            "2.- Cumplimiento e iniciativa en el trabajo asignado",
            "3.- Facilidad para el trabajo en equipo",
            "4.- Puntualidad y Asistencia",
            "5.- Capacidad de comunicación oral y escrita",
            "6.- Aplicación de conocimientos en la solución de problemas",
            "7.- Desarrollo de habilidades en la práctica industrial",
            "8.- Responsabilidad",
            "9.- Comportamiento",
            "10.- Avance mensual del programa de trabajo"
        ];

        for ($i = 1; $i <= 10; $i++): ?>
            <label><?php echo $aspectos[$i - 1]; ?>:</label>
            <input type="number" name="<?php echo $i; ?>" min="0" max="100" required><br><br>
        <?php endfor; ?>

        <button type="submit" <?php echo !$evaluacion_habilitada ? 'disabled' : ''; ?>>Registrar</button>
    </form>

    <?php if (!$evaluacion_habilitada): ?>
        <p style="color: red;">No puedes registrar la evaluación hoy. La fecha permitida es <?php echo htmlspecialchars($fecha_permitida); ?>.</p>
    <?php endif; ?>
</body>
</html>
