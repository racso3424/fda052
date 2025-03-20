<?php
session_start();
require 'db_conexion.php';

date_default_timezone_set('America/Mexico_City'); // Ajusta según tu ubicación
$fecha_actual = date('Y-m-d');

// Consultar la fecha permitida de evaluación
$query_fecha = "SELECT evaluacion1 FROM fechas_evaluacion LIMIT 1";
$result_fecha = $conn->query($query_fecha);
$row_fecha = $result_fecha->fetch_assoc();

$fecha_permitida = $row_fecha['evaluacion1'] ?? null;
$evaluacion_habilitada = ($fecha_actual === $fecha_permitida);

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
    $stmt_check = $conn->prepare("SELECT * FROM evaluacion_1 WHERE matricula = ?");
    $stmt_check->bind_param("s", $matricula);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $mensaje_error = 'Esta matrícula ya tiene una evaluación registrada.';
        $hay_error = true;
    } else {
        // Insertar datos
        $stmt_insert = $conn->prepare("INSERT INTO evaluacion_1 (matricula, `uno`, `dos`, `tres`, `cuatro`, `cinco`, `seis`, `siete`, `ocho`, `nueve`, `diez`, promedio_1) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Crear la cadena de tipos dinámicamente
        $types = "s" . str_repeat("i", count($notas)) . "i";

        // Combinar todos los parámetros en un solo array
        $params = array_merge([$matricula], $notas, [$promedio]);

        // Pasar los parámetros desglosados
        $stmt_insert->bind_param($types, ...$params);

        if ($stmt_insert->execute()) {
            $registro_exitoso = true;
        } else {
            $mensaje_error = 'Error al registrar la evaluación.';
            $hay_error = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación 1</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <style>
        .evaluation-card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .date-badge {
            background-color: #f8f9fa;
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: 500;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card evaluation-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title mb-0 text-center py-2">Registro de Evaluación Mensual</h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Primera Evaluación</h5>
                            <span class="date-badge">
                                <i class="bi bi-calendar-event me-1"></i>
                                Fecha permitida: <?php echo htmlspecialchars($fecha_permitida); ?>
                            </span>
                        </div>

                        <?php if (!$evaluacion_habilitada): ?>
                            <div class="alert alert-warning mb-4" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                No puedes registrar la evaluación hoy. La fecha permitida es <?php echo htmlspecialchars($fecha_permitida); ?>.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="evaluacionForm">
                            <div class="row mb-3">
                                <label for="matricula" class="col-sm-3 col-form-label">Matrícula:</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="matricula" name="matricula" value="<?php echo htmlspecialchars($matricula); ?>" readonly required>
                                </div>
                            </div>

                            <?php 
                            $aspectos = [
                                "Cumplimiento de las normas generales de seguridad",
                                "Cumplimiento e iniciativa en el trabajo asignado",
                                "Facilidad para el trabajo en equipo",
                                "Puntualidad y Asistencia",
                                "Capacidad de comunicación oral y escrita",
                                "Aplicación de conocimientos en la solución de problemas",
                                "Desarrollo de habilidades en la práctica industrial",
                                "Responsabilidad",
                                "Comportamiento",
                                "Avance mensual del programa de trabajo"
                            ];

                            for ($i = 1; $i <= 10; $i++): ?>
                                <div class="row mb-3">
                                    <label for="aspecto<?php echo $i; ?>" class="col-sm-8 col-form-label">
                                        <?php echo $i; ?>.- <?php echo $aspectos[$i - 1]; ?>:
                                    </label>
                                    <div class="col-sm-4">
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="aspecto<?php echo $i; ?>" name="<?php echo $i; ?>" min="0" max="100" required <?php echo !$evaluacion_habilitada ? 'disabled' : ''; ?>>
                                            <span class="input-group-text">/ 100</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" <?php echo !$evaluacion_habilitada ? 'disabled' : ''; ?>>
                                    <i class="bi bi-save me-2"></i> Registrar Evaluación
                                </button>
                                <a href="maestro.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-2"></i> Volver
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Script para SweetAlert
        <?php if (!$evaluacion_habilitada && !isset($_POST['matricula'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Fecha no permitida',
                text: 'No se puede registrar la evaluación en esta fecha. La fecha permitida es <?php echo $fecha_permitida; ?>.',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'maestro.php';
                }
            });
        });
        <?php endif; ?>

        <?php if (isset($hay_error) && $hay_error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Error',
                text: '<?php echo $mensaje_error; ?>',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
        <?php endif; ?>

        <?php if (isset($registro_exitoso) && $registro_exitoso): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Evaluación registrada correctamente.',
                icon: 'success',
                confirmButtonText: 'Continuar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'evaluacion_1.php';
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>