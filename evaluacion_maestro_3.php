<?php
session_start();
require 'db_conexion.php';

date_default_timezone_set('America/Mexico_City'); // Ajusta según tu ubicación
$fecha_actual = date('Y-m-d');

// Consultar la fecha permitida de evaluación 3
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

// Procesar el formulario para la evaluación 3
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $evaluacion_habilitada) {
    $matricula = $_POST['matricula'];
    $notas = [];

    // Capturar las 7 calificaciones
    for ($i = 1; $i <= 7; $i++) {
        $notas[] = isset($_POST[$i]) ? (int)$_POST[$i] : 0;
    }

    // Calcular el promedio
    $promedio = array_sum($notas) / count($notas);

    // Verificar si la matrícula ya tiene evaluación 3
    $stmt_check = $conn->prepare("SELECT * FROM evaluacion_maestro_3 WHERE matricula = ?");
    $stmt_check->bind_param("s", $matricula);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo "<script>alert('Esta matrícula ya tiene una evaluación registrada en la Evaluación 3.'); window.location.href='maestro.php';</script>";
    } else {
        // Insertar datos
        $stmt_insert = $conn->prepare("INSERT INTO evaluacion_maestro_3 (matricula, `uno`, `dos`, `tres`, `cuatro`, `cinco`, `seis`, `siete`, promedio_3) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Crear la cadena de tipos dinámicamente
        $types = "s" . str_repeat("i", count($notas)) . "i";

        // Combinar todos los parámetros en un solo array
        $params = array_merge([$matricula], $notas, [$promedio]);

        // Pasar los parámetros desglosados
        $stmt_insert->bind_param($types, ...$params);

        if ($stmt_insert->execute()) {
            echo "<script>alert('Evaluación 3 registrada con éxito.'); window.location.href='maestro.php';</script>";
        } else {
            echo "<script>alert('Error al registrar la Evaluación 3.');</script>";
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
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="./bootstrap-5.3.3-dist/css/bootstrap.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="./sweetalert2/dist/sweetalert2.min.css">
    <style>
        .evaluation-form {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-range {
            width: 100%;
        }
        .range-value {
            font-weight: bold;
            font-size: 1.2rem;
            width: 50px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card shadow evaluation-form">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Registrar Evaluación 3</h2>
            </div>
            <div class="card-body">
                <?php if (!$evaluacion_habilitada): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        No puedes registrar la evaluación hoy. La fecha permitida es <strong><?php echo htmlspecialchars($fecha_permitida); ?></strong>.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Fecha permitida para la evaluación: <strong><?php echo htmlspecialchars($fecha_permitida); ?></strong>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="evaluationForm">
                    <div class="mb-3">
                        <label for="matricula" class="form-label">Matrícula:</label>
                        <input type="text" class="form-control" id="matricula" name="matricula" value="<?php echo htmlspecialchars($matricula); ?>" required readonly>
                    </div>

                    <?php 
                    $aspectos = [
                        "1.- Puntualidad y Asistencia",
                        "2.- Capacidad de comunicación oral y escrita",
                        "3.- Aplicación de conocimientos en la solución de problemas",
                        "4.- Desarrollo de habilidades en la práctica industrial",
                        "5.- Responsabilidad",
                        "6.- Comportamiento",
                        "7.- Avance mensual del programa de trabajo"
                    ];

                    for ($i = 1; $i <= 7; $i++): ?>
                        <div class="mb-4">
                            <label for="aspect<?php echo $i; ?>" class="form-label"><?php echo $aspectos[$i - 1]; ?>:</label>
                            <div class="d-flex align-items-center">
                                <input type="range" class="form-range me-2" id="aspect<?php echo $i; ?>" name="<?php echo $i; ?>" min="0" max="100" step="1" value="70" oninput="updateValue(this)">
                                <div class="range-value" id="value<?php echo $i; ?>">70</div>
                            </div>
                        </div>
                    <?php endfor; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" <?php echo !$evaluacion_habilitada ? 'disabled' : ''; ?>>
                            <i class="bi bi-save me-2"></i>Registrar Evaluación
                        </button>
                        <a href="maestro.php" class="btn btn-secondary">Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="./bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="./sweetalert2/dist/sweetalert2.min.js"></script>

    <script>
        // Función para actualizar el valor mostrado de los sliders
        function updateValue(slider) {
            const valueId = 'value' + slider.name;
            document.getElementById(valueId).textContent = slider.value;
        }

        // Inicializar todos los sliders
        document.addEventListener('DOMContentLoaded', function() {
            for (let i = 1; i <= 7; i++) {
                updateValue(document.getElementById('aspect' + i));
            }
        });

        <?php if (!$evaluacion_habilitada && !isset($_POST['matricula'])): ?>
        // Alerta si la fecha no está habilitada y es la primera carga
        Swal.fire({
            title: 'Fecha no permitida',
            text: 'No se puede registrar la evaluación en esta fecha. La fecha permitida es <?php echo $fecha_permitida; ?>.',
            icon: 'warning',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Entendido'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'maestro.php';
            }
        });
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        // Mostrar mensaje de error con SweetAlert2
        Swal.fire({
            title: 'Error',
            text: '<?php echo $error_message; ?>',
            icon: 'error',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Aceptar'
        });
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
        // Mostrar mensaje de éxito con SweetAlert2
        Swal.fire({
            title: '¡Éxito!',
            text: '<?php echo $success_message; ?>',
            icon: 'success',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Continuar'
        }).then((result) => {
            window.location.href = '<?php echo $redirect; ?>';
        });
        <?php endif; ?>
    </script>
</body>
</html>