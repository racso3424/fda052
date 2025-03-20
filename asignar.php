<?php
session_start();
require 'db_conexion.php';

if (!isset($_SESSION['no_empleado'])) {
    header("Location: index.php");
    exit();
}

// Querys & Resultados Maestros & Alumnos
$query_maestros = "SELECT no_empleado, nombre, apellido_1, apellido_2, rol FROM empleados WHERE rol IN ('maestro')";
$result_maestros = $conn->query($query_maestros);

$query_alumnos = "SELECT matricula, nombre, apellido_1, apellido_2, estatus FROM alumnos WHERE estatus = 0";
$result_alumnos = $conn->query($query_alumnos);

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['matricula']) && isset($_POST['no_empleado'])) {
        $alumnos = $_POST['matricula'];
        $no_empleados = $_POST['no_empleado'];

        $success_count = 0;
        $already_assigned_count = 0;
        $error_count = 0;

        foreach ($alumnos as $matricula) {
            // Verificar si el alumno ya está asignado
            $query_estatus = "SELECT estatus FROM alumnos WHERE matricula = ?";
            $stmt_estatus = $conn->prepare($query_estatus);
            $stmt_estatus->bind_param("s", $matricula);
            $stmt_estatus->execute();
            $result_estatus = $stmt_estatus->get_result();
            $alumno = $result_estatus->fetch_assoc();

            if ($alumno['estatus'] == 1) {
                $already_assigned_count++;
                continue; // Omitir si ya está asignado
            }

            foreach ($no_empleados as $no_empleado) {
                // Insertar asignación
                $query1 = "INSERT INTO asignaciones (matricula, no_empleado) VALUES (?, ?)";
                $stmt1 = $conn->prepare($query1);
                if (!$stmt1->bind_param("ss", $matricula, $no_empleado) || !$stmt1->execute()) {
                    $error_count++;
                    continue; // Si falla, pasar al siguiente
                }

                // Actualizar estatus del alumno
                $query2 = "UPDATE alumnos SET estatus = 1 WHERE matricula = ?";
                $stmt2 = $conn->prepare($query2);
                if (!$stmt2->bind_param("s", $matricula) || !$stmt2->execute()) {
                    $error_count++;
                }
            }

            $success_count++;
        }

        // Preparar mensaje para SweetAlert
        $mensaje = json_encode([
            'success_count' => $success_count,
            'already_assigned_count' => $already_assigned_count,
            'error_count' => $error_count
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Alumnos</title>
    <link rel="stylesheet" href="./bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #03855a;
            border-radius: 4px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(3, 133, 90, 0.1);
        }
    </style>
</head>
<body class="antialiased">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 bg-white shadow-sm py-3 mb-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="UTC_logo.png" alt="Institution Logo" class="mr-3" style="height: 50px;">
                    <h2 class="text-xl font-bold text-gray-800 mb-0">Sistema de Asignación de Alumnos</h2>
                </div>
                <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#miModal">
                        <i class="mr-2"></i>Fechas de evaluación
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                </a>
               
            </div>
        </div>

        <div class="container">
            <form method="POST" id="assignmentForm">
                <div class="row">
                    <!-- Maestros Table -->
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Maestros</h5>
                                <div class="input-group input-group-sm" style="width: 250px;">
                                    <input type="text" id="nombremaestro" class="form-control" placeholder="Buscar Nombre">
                                    <select id="categoriamaestro" class="form-select" style="max-width: 120px;">
                                        <option value="all">Todos</option>
                                        <option value="coordinador">Coordinador</option>
                                        <option value="maestro">Maestro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive custom-scrollbar" style="max-height: 500px;">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Categoría</th>
                                                <th>Seleccionar</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabla-maestros">
                                            <?php 
                                            $result_maestros->data_seek(0);
                                            while($maestro = $result_maestros->fetch_assoc()): ?>
                                                <tr class="fila-maestro">
                                                    <td><?php echo $maestro['nombre'] . ' ' . $maestro['apellido_1'] . ' ' . $maestro['apellido_2']; ?></td>
                                                    <td><?php echo ucfirst($maestro['rol']); ?></td>
                                                    <td>
                                                        <input type="checkbox" name="no_empleado[]" class="maestro-checkbox form-check-input" value="<?php echo $maestro['no_empleado']; ?>">
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alumnos Table -->
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Alumnos</h5>
                                <div class="input-group input-group-sm" style="width: 250px;">
                                    <input type="text" id="nombrealumno" class="form-control" placeholder="Buscar Nombre">
                                    <button type="button" id="checkallalumnos" class="btn btn-light">
                                        <i class="fas fa-check-square"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive custom-scrollbar" style="max-height: 500px;">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead class="bg-light sticky-top">
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Seleccionar</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabla-alumnos">
                                            <?php 
                                            $result_alumnos->data_seek(0);
                                            while($alumno = $result_alumnos->fetch_assoc()): ?>
                                                <tr class="fila-alumno">
                                                    <td class="nombrealumno">
                                                        <?php echo $alumno['nombre'] . ' ' . $alumno['apellido_1'] . ' ' . $alumno['apellido_2']; ?>
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" class="alumno-checkbox form-check-input" name="matricula[]" value="<?php echo $alumno['matricula']; ?>">
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center my-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus mr-2"></i>Asignar Alumnos
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="./bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="./sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="./sweetalert2/dist/sweetalert2.min.css">

    <div class="modal fade" id="miModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Selecciona las fechas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
            <form method="POST">
                    <div class="mb-3">
                        <label for="evaluacion1" class="form-label">Evaluación 1:</label>
                        <input type="date" class="form-control" id="evaluacion1" name="evaluacion1" required>
                    </div>
                    <div class="mb-3">
                        <label for="evaluacion2" class="form-label">Evaluación 2:</label>
                        <input type="date" class="form-control" id="evaluacion2" name="evaluacion2" required>
                    </div>
                    <div class="mb-3">
                        <label for="evaluacion3" class="form-label">Evaluación 3:</label>
                        <input type="date" class="form-control" id="evaluacion3" name="evaluacion3" required>
                        <?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Obtener las fechas del formulario
    $evaluacion1 = $_POST['evaluacion1'];
    $evaluacion2 = $_POST['evaluacion2'];
    $evaluacion3 = $_POST['evaluacion3'];

    // Verificar si ya hay un registro en la tabla
    $query_check = "SELECT COUNT(*) FROM fechas_evaluacion";
    $result_check = $conn->query($query_check);
    $row = $result_check->fetch_array();

    if (isset($_POST['guardar_fechas'])) {
        if ($row[0] > 0) {
            echo "<script>alert('Ya existe un registro de fechas. No puedes registrar nuevamente.');</script>";
        } else {
            // Insertar en la tabla fechas_evaluacion
            $query = "INSERT INTO fechas_evaluacion (evaluacion1, evaluacion2, evaluacion3) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $evaluacion1, $evaluacion2, $evaluacion3);

            if ($stmt->execute()) {
                echo "<script>alert('Fechas registradas correctamente');</script>";
            } else {
                echo "<script>alert('Error al registrar las fechas');</script>";
            }
        }
    }

    // Opción para actualizar fechas si ya existen
    if (isset($_POST['actualizar_fechas'])) {
        if ($row[0] > 0) {
            // Actualizar fechas en la base de datos
            $query_update = "UPDATE fechas_evaluacion SET evaluacion1 = ?, evaluacion2 = ?, evaluacion3 = ?";
            $stmt = $conn->prepare($query_update);
            $stmt->bind_param("sss", $evaluacion1, $evaluacion2, $evaluacion3);

            if ($stmt->execute()) {
                echo "<script>alert('Fechas actualizadas correctamente'); </script>";
            } else {
                echo "<script>alert('Error al actualizar las fechas');</script>";
            }
        } else {
            echo "<script>alert('No hay registros previos para actualizar.');</script>";
        }
    }
}
?>



                    </div>
                    <button type="submit" name="guardar_fechas" class="btn btn-primary">Guardar</button>
                    <button type="submit" name="actualizar_fechas" class="btn btn-warning">Actualizar Fechas</button>
                </form>

            </div>
        </div>
    </div>
</div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Maestros single selection
        const maestro_checkboxes = document.querySelectorAll(".maestro-checkbox");
        maestro_checkboxes.forEach(checkbox => {
            checkbox.addEventListener("change", function() {
                if (this.checked) {
                    maestro_checkboxes.forEach(cb => {
                        if (cb !== this) {
                            cb.checked = false;
                        }
                    });
                }
            });
        });

        // Check/Uncheck all alumnos
        document.getElementById("checkallalumnos").addEventListener("click", function() {
            const alumno_checkboxes = document.querySelectorAll(".alumno-checkbox");
            const filasAlumno = document.querySelectorAll(".fila-alumno");
            const visibleCheckboxes = Array.from(filasAlumno).filter(fila => {
                return fila.style.display !== "none";
            }).map(fila => fila.querySelector(".alumno-checkbox"));
            
            const allChecked = visibleCheckboxes.every(checkbox => checkbox.checked);
            
            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
        });

        // Maestros filter
        const inputNombreMaestro = document.getElementById("nombremaestro");
        const selectCategoriaMaestro = document.getElementById("categoriamaestro");
        const filasMaestro = document.querySelectorAll(".fila-maestro");

        function filtrarTablaMaestro() {
            const filtroNombreMaestro = inputNombreMaestro.value.toLowerCase();
            const filtroCategoriaMaestro = selectCategoriaMaestro.value;

            filasMaestro.forEach(filaMaestro => {
                const nombreMaestro = filaMaestro.querySelector("td:first-child").textContent.toLowerCase();
                const categoriaMaestro = filaMaestro.querySelector("td:nth-child(2)").textContent.toLowerCase();

                const coincideNombreMaestro = nombreMaestro.includes(filtroNombreMaestro);
                const coincideCategoriaMaestro = (filtroCategoriaMaestro === "all" || categoriaMaestro === filtroCategoriaMaestro.toLowerCase());

                filaMaestro.style.display = (coincideNombreMaestro && coincideCategoriaMaestro) ? "" : "none";
            });
        }
        
        inputNombreMaestro.addEventListener("input", filtrarTablaMaestro);
        selectCategoriaMaestro.addEventListener("change", filtrarTablaMaestro);

        // Alumnos filter
        const inputNombreAlumno = document.getElementById("nombrealumno");
        const filasAlumno = document.querySelectorAll(".fila-alumno");

        function filtrarTablaAlumno() {
            const filtroNombreAlumno = inputNombreAlumno.value.toLowerCase();

            filasAlumno.forEach(filaAlumno => {
                const nombreAlumno = filaAlumno.querySelector(".nombrealumno").textContent.toLowerCase();

                filaAlumno.style.display = nombreAlumno.includes(filtroNombreAlumno) ? "" : "none";
            });
        }

        inputNombreAlumno.addEventListener("input", filtrarTablaAlumno);

        // SweetAlert for messages
        <?php if ($mensaje): ?>
        const messageData = JSON.parse('<?php echo $mensaje; ?>');
        
        Swal.fire({
            icon: messageData.error_count > 0 ? 'warning' : 'success',
            title: 'Asignación de Alumnos',
            html: `
                <p>Alumnos asignados exitosamente: <strong>${messageData.success_count}</strong></p>
                ${messageData.error_count > 0 ? `<p>Alumnos no asignados: <strong>${messageData.error_count}</strong></p>` : ''}
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#03855a'
        });then(() => {
            // Recargar la página después de que el usuario cierre el mensaje
            window.location.reload();
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>
