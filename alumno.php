<?php
session_start();
require 'db_conexion.php';

// Procesar el formulario solo cuando se haya enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar'])) {
    // Verificar si la matrícula está disponible en la sesión
    if (!isset($_SESSION['matricula'])) {
        echo "<script>
            Swal.fire({
                title: 'Error',
                text: 'No has iniciado sesión.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        </script>";
        exit();
    }

    // Obtener la matrícula del alumno logueado desde la sesión
    $matricula = $_SESSION['matricula'];
    $empresa = $_POST['empresa'];
    $departamento = $_POST['departamento'];
    $proyecto = $_POST['proyecto'];
    $nombre_tutor = $_POST['nombre_tutor'];
    $correo_tutor = $_POST['correo_tutor'];
    $telefono_tutor = $_POST['telefono_tutor'];

    // Verificar si el alumno ya ha registrado su estancia en la tabla
    $query_check = "SELECT * FROM alumnos_estadia WHERE matricula = ?";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bind_param("s", $matricula);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // El alumno ya ha registrado, mostrar una alerta
        echo "<script>
            Swal.fire({
                title: 'Advertencia',
                text: 'Ya has realizado un registro previamente.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        </script>";
    } else {
        // Insertar los datos en la tabla alumnos_estadia
        $query_insert = "INSERT INTO alumnos_estadia (matricula, empresa, departamento, proyecto, nombre_tutor, correo_tutor, telefono_tutor) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($query_insert);
        $stmt_insert->bind_param("sssssss", $matricula, $empresa, $departamento, $proyecto, $nombre_tutor, $correo_tutor, $telefono_tutor);

        if ($stmt_insert->execute()) {
            // Mostrar una alerta de éxito
            echo "<script>
                Swal.fire({
                    title: 'Éxito',
                    text: 'Registro exitoso.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
            </script>";
        } else {
            // Mostrar alerta de error si falla la inserción
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'Hubo un error al registrar los datos.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }
}

// Obtener la información del alumno
if (isset($_SESSION['matricula'])) {
    $matricula = $_SESSION['matricula'];
    
    // Consulta para obtener la información del alumno
    $query_alumno = "SELECT * FROM alumnos WHERE matricula = ?";
    $stmt_alumno = $conn->prepare($query_alumno);
    $stmt_alumno->bind_param("s", $matricula);
    $stmt_alumno->execute();
    $alumno = $stmt_alumno->get_result()->fetch_assoc();
    
    // Consulta para obtener las estadías del alumno
    $query_estadias = "SELECT * FROM alumnos_estadia WHERE matricula = ?";
    $stmt_estadias = $conn->prepare($query_estadias);
    $stmt_estadias->bind_param("s", $matricula);
    $stmt_estadias->execute();
    $result_estadias = $stmt_estadias->get_result();
    $tiene_estadia = ($result_estadias->num_rows > 0);
    
    // Obtener los promedios de las tablas evaluacion_1 y evaluacion_maestro_1
    $query_calificaciones = "SELECT e.promedio_1 AS promedio_1_evaluacion, em.promedio_1 AS promedio_1_maestro
                             FROM evaluacion_1 e
                             JOIN evaluacion_maestro_1 em ON e.matricula = em.matricula
                             WHERE e.matricula = ?";
    $stmt_calificaciones = $conn->prepare($query_calificaciones);
    $stmt_calificaciones->bind_param("s", $matricula);
    $stmt_calificaciones->execute();
    $result_calificaciones = $stmt_calificaciones->get_result();
    
    if ($result_calificaciones->num_rows > 0) {
        $row = $result_calificaciones->fetch_assoc();
        $promedio_1_evaluacion = $row['promedio_1_evaluacion'];
        $promedio_1_maestro = $row['promedio_1_maestro'];

        // Calcular el promedio final
        $promedio_final = ($promedio_1_evaluacion + $promedio_1_maestro) / 2;
    } else {
        $promedio_final = "No disponible"; // Si no hay registros de evaluación
    }
    // Obtener los promedios de las tablas evaluacion_2 y evaluacion_maestro_2
    $query_calificaciones_2 = "SELECT e.promedio_2 AS promedio_2_evaluacion, em.promedio_2 AS promedio_2_maestro
                               FROM evaluacion_2 e
                               JOIN evaluacion_maestro_2 em ON e.matricula = em.matricula
                               WHERE e.matricula = ?";
    $stmt_calificaciones_2 = $conn->prepare($query_calificaciones_2);
    $stmt_calificaciones_2->bind_param("s", $matricula);
    $stmt_calificaciones_2->execute();
    $result_calificaciones_2 = $stmt_calificaciones_2->get_result();

    if ($result_calificaciones_2->num_rows > 0) {
    $row_2 = $result_calificaciones_2->fetch_assoc();
    $promedio_2_evaluacion = $row_2['promedio_2_evaluacion'];
    $promedio_2_maestro = $row_2['promedio_2_maestro'];

    // Calcular el promedio final de la evaluación 2
    $promedio_2_final = ($promedio_2_evaluacion + $promedio_2_maestro) / 2;
    } else {
    $promedio_2_final = "No disponible"; // Si no hay registros de evaluación
    }

    // Obtener los promedios de las tablas evaluacion_3 y evaluacion_maestro_3
    $query_calificaciones_3 = "SELECT e.promedio_3 AS promedio_3_evaluacion, em.promedio_3 AS promedio_3_maestro
                               FROM evaluacion_3 e
                               JOIN evaluacion_maestro_3 em ON e.matricula = em.matricula
                               WHERE e.matricula = ?";
    $stmt_calificaciones_3 = $conn->prepare($query_calificaciones_3);
    $stmt_calificaciones_3->bind_param("s", $matricula);
    $stmt_calificaciones_3->execute();
    $result_calificaciones_3 = $stmt_calificaciones_3->get_result();

    if ($result_calificaciones_3->num_rows > 0) {
    $row_3 = $result_calificaciones_3->fetch_assoc();
    $promedio_3_evaluacion = $row_3['promedio_3_evaluacion'];
    $promedio_3_maestro = $row_3['promedio_3_maestro'];

    // Calcular el promedio final de la evaluación 3
    $promedio_3_final = ($promedio_3_evaluacion + $promedio_3_maestro) / 2;
    } else {
    $promedio_3_final = "No disponible"; // Si no hay registros de evaluación
    }
}

// Obtener las fechas de evaluación
$query = "SELECT evaluacion1, evaluacion2, evaluacion3 FROM fechas_evaluacion LIMIT 1";
$resultado = $conn->query($query);
$fechas = $resultado->fetch_assoc();

// Obtener la fecha actual en formato YYYY-MM-DD
$fecha_actual = date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Alumno - Estadías</title>
    <link rel="stylesheet" href="./bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="sweetalert2/dist/sweetalert2.min.css">
    <script src="./bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .bg-primary-custom {
            background-color: #1e40af;
        }
        .bg-secondary-custom {
            background-color: #f3f4f6;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-md">
        <div class="container">
            <a class="navbar-brand font-bold" href="#">Portal del Alumno</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Inicio</a>
                    </li>
                    <!-- Opción Ver calificaciones -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#calificacionesModal">Ver Calificaciones</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">Bienvenido, <?php echo isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Alumno'; ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container py-5">
        <!-- Información del Alumno -->
        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mx-auto mb-4 rounded-circle bg-primary text-white" style="width: 80px; height: 80px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                <path fill-rule="evenodd" d="M0 13a5 5 0 0 1 10 0H0z"/>
                            </svg>
                        </div>
                        <h5 class="card-title fw-bold"><?php echo isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Nombre del Alumno'; ?></h5>
                        <p class="card-text text-muted">Matrícula: <?php echo isset($_SESSION['matricula']) ? $_SESSION['matricula'] : 'No disponible'; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <h5 class="card-title font-bold border-b pb-2 mb-3">Información Personal</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="text-sm text-gray-600">Carrera:</p>
                                <p class="font-medium"><?php echo isset($alumno['carrera']) ? $alumno['carrera'] : 'T.S.U. Desarrollo de Software Multiplataforma'; ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="text-sm text-gray-600">Correo:</p>
                                <p class="font-medium"><?php echo isset($alumno['correo']) ? $alumno['correo'] : 'ejemplo@alumno.utc.edu.mx'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Estadías -->
        <div class="card shadow-lg mb-5">
            <div class="card-header bg-primary-custom text-white py-3">
                <h3 class="mb-0 font-bold">Información de Estadías</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="bg-gray-200">
                            <tr>
                                <th>Empresa</th>
                                <th>Departamento</th>
                                <th>Proyecto</th>
                                <th>Tutor</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($result_estadias) && $result_estadias->num_rows > 0): ?>
                                <?php while ($estadia = $result_estadias->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $estadia['empresa']; ?></td>
                                        <td><?php echo $estadia['departamento']; ?></td>
                                        <td><?php echo $estadia['proyecto']; ?></td>
                                        <td><?php echo $estadia['nombre_tutor']; ?></td>
                                        <td>
                                            <span class="badge bg-success">Activa</span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No hay estadías registradas</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!isset($tiene_estadia) || !$tiene_estadia): ?>
                <div class="d-grid gap-2 col-md-4 mx-auto mt-4">
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#formEstadia" aria-expanded="false" aria-controls="formEstadia">
                        Registrar Nueva Estadía
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Formulario Colapsable -->
                <div class="collapse mt-4" id="formEstadia">
                    <div class="card card-body bg-secondary-custom">
                        <h4 class="text-center mb-4">Registro de Estadía</h4>
                        <form action="" method="POST" id="formRegistroEstadia" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="empresa" class="form-label font-medium">Empresa</label>
                                    <input type="text" class="form-control" id="empresa" name="empresa" required>
                                    <div class="invalid-feedback">Por favor ingresa el nombre de la empresa.</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="departamento" class="form-label font-medium">Departamento</label>
                                    <input type="text" class="form-control" id="departamento" name="departamento" required>
                                    <div class="invalid-feedback">Por favor ingresa el departamento.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="proyecto" class="form-label font-medium">Proyecto</label>
                                <input type="text" class="form-control" id="proyecto" name="proyecto" required>
                                <div class="invalid-feedback">Por favor ingresa el nombre del proyecto.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="nombre_tutor" class="form-label font-medium">Nombre del Tutor</label>
                                    <input type="text" class="form-control" id="nombre_tutor" name="nombre_tutor" required>
                                    <div class="invalid-feedback">Por favor ingresa el nombre del tutor.</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="correo_tutor" class="form-label font-medium">Correo del Tutor</label>
                                    <input type="email" class="form-control" id="correo_tutor" name="correo_tutor" required>
                                    <div class="invalid-feedback">Por favor ingresa un correo válido.</div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="telefono_tutor" class="form-label font-medium">Teléfono del Tutor</label>
                                    <input type="tel" class="form-control" id="telefono_tutor" name="telefono_tutor" required>
                                    <div class="invalid-feedback">Por favor ingresa un teléfono válido.</div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 col-md-6 mx-auto mt-4">
                                <button type="submit" class="btn btn-primary" name="registrar">Guardar Información</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección de Recursos y Documentos -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <h5 class="card-title font-bold border-b pb-2 mb-3">Documentos para evaluar</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Primera evaluación de Estadía
                                <button class="btn btn-sm btn-outline-primary" onclick="verificarFecha('<?= $fechas['evaluacion1'] ?>')">Subir archivo</button>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Segunda evaluación de Estadía
                                <button class="btn btn-sm btn-outline-primary" onclick="verificarFecha('<?= $fechas['evaluacion2'] ?>')">Subir archivo</button>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Tercera evaluación de Estadía
                                <button class="btn btn-sm btn-outline-primary" onclick="verificarFecha('<?= $fechas['evaluacion3'] ?>')">Subir archivo</button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <h5 class="card-title font-bold border-b pb-2 mb-3">Calendario de Actividades</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="font-medium">Entrega de Carta de Aceptación</span>
                                    <p class="text-sm text-gray-600 mb-0">Fecha límite: 30/04/2025</p>
                                </div>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="font-medium">Primer Reporte de Avance</span>
                                    <p class="text-sm text-gray-600 mb-0">Fecha límite: 15/05/2025</p>
                                </div>
                                <span class="badge bg-secondary">Próximo</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="font-medium">Entrega Final de Documento</span>
                                    <p class="text-sm text-gray-600 mb-0">Fecha límite: 20/06/2025</p>
                                </div>
                                <span class="badge bg-info">Programado</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver calificaciones -->
    <div class="modal fade" id="calificacionesModal" tabindex="-1" aria-labelledby="calificacionesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="calificacionesModalLabel">Ver Calificaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Mostrar el promedio calculado -->
                    <div class="mt-3">
                        <h6>Promedio Final Evaluación 1: <?php echo $promedio_final; ?></h6>
                        <h6>Promedio Final Evaluación 2: <?php echo $promedio_2_final; ?></h6>
                        <h6>Promedio Final Evaluación 3: <?php echo $promedio_3_final; ?></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal para subir el archivo -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Subir Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Formulario para subir el archivo PDF -->
                    <form action="procesar_subida.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="pdfFile" class="form-label">Selecciona un archivo PDF</label>
                            <input class="form-control" type="file" id="pdfFile" name="pdfFile" accept=".pdf" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Subir Archivo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- Footer -->
    <footer class="bg-primary-custom text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-1">Portal de Estadías - Sistema Universitario</p>
            <p class="mb-0 text-white-50">© 2025 Todos los derechos reservados</p>
        </div>
    </footer>

    
    <script>
        // Validación de formularios de Bootstrap
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>

    <script>
        // Función para verificar si la fecha de evaluación ya ha pasado
        function verificarFecha(fechaEvaluacion) {
            // Obtener la fecha actual en formato YYYY-MM-DD
            var fechaActual = new Date().toISOString().split('T')[0]; // YYYY-MM-DD

            // Compara la fecha actual con la fecha de evaluación
            if (fechaActual >= fechaEvaluacion) {
                // Si la fecha actual es igual o posterior a la fecha de evaluación
                let modal = new bootstrap.Modal(document.getElementById('uploadModal'));
                modal.show();
            } else {
                // Si la fecha actual es anterior a la fecha de evaluación
                Swal.fire({
                    title: 'Fecha no disponible',
                    text: 'Aún no es la fecha para realizar esta evaluación.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        }
    </script>

<script src="/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="sweetalert2/dist/sweetalert2.all.min.js"></script>

</body>
</html>
