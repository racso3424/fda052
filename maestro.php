<?php
session_start();
require 'db_conexion.php';

if (!isset($_SESSION['no_empleado'])) {
    header("Location: index.php");
    exit();
}

$no_empleado = $_SESSION['no_empleado'];

// Fetch teacher name for personalization
$teacher_query = "SELECT nombre, apellido_1 FROM empleados WHERE no_empleado = ?";
$teacher_stmt = $conn->prepare($teacher_query);
$teacher_stmt->bind_param("s", $no_empleado);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher_info = $teacher_result->fetch_assoc();

// Fetch students with additional JOIN to alumnos_estadias table
$query = "SELECT 
            a.matricula, 
            a.nombre, 
            a.apellido_1, 
            a.apellido_2,
            ae.empresa,
            ae.departamento,
            ae.proyecto,
            ae.nombre_tutor,
            ae.correo_tutor,
            ae.telefono_tutor
          FROM asignaciones asig
          INNER JOIN alumnos a ON asig.matricula = a.matricula
          LEFT JOIN alumnos_estadia ae ON a.matricula = ae.matricula
          WHERE asig.no_empleado = ?
          ORDER BY a.apellido_1, a.apellido_2";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $no_empleado);
$stmt->execute();
$result = $stmt->get_result();
$student_count = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Profesor</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="./bootstrap-5.3.3-dist/css/bootstrap.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="./sweetalert2/dist/sweetalert2.min.css">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #34495E;
            --accent-color: #3498DB;
            --light-accent: #d4e6f1;
            --light-gray: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            background-color: #ECF0F1;
        }
        
        .navbar-brand img {
            height: 50px;
            margin-right: 15px;
        }
        
        .bg-primary-custom {
            background-color: var(--primary-color);
        }
        
        .bg-secondary-custom {
            background-color: var(--secondary-color);
        }
        
        .text-accent {
            color: var(--accent-color);
        }
        
        .btn-accent {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }
        
        .btn-accent:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background-color: var(--light-accent);
        }
        
        .card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            padding: 1.25rem;
            font-weight: 600;
        }
        
        /* Expanded row styling */
        .student-details {
            display: none;
            background-color: var(--light-gray);
            padding: 0;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .student-details.show {
            display: table-row;
        }
        
        .student-details-content {
            padding: 20px;
        }
        
        .btn-document {
            width: 100%;
            margin-bottom: 10px;
            text-align: left;
            padding: 8px 15px;
            transition: all 0.2s;
        }
        
        .evaluation-icon {
            font-size: 0.9rem;
            margin-left: 5px;
            transition: all 0.2s;
        }
        
        .evaluation-icon:hover {
            transform: scale(1.2);
        }
        
        .evaluation-icon-1 { color: #3498db; } /* Blue */
        .evaluation-icon-2 { color: #2ecc71; } /* Green */
        .evaluation-icon-3 { color: #e74c3c; } /* Red */
        
        .selected-row {
            background-color: var(--light-accent) !important;
        }
        
        .info-card {
            border-left: 4px solid var(--accent-color);
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .info-card-heading {
            font-weight: 600;
            font-size: 1rem;
            color: var(--primary-color);
            margin-bottom: 12px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        
        .info-card p {
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .empty-info {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="UTC_logo.png" alt="Institution Logo" class="d-inline-block">
                <span class="ms-2 fw-bold">Panel de Maestros</span>
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-4">
                    <i class="fas fa-user-circle me-2"></i>
                    <?php echo htmlspecialchars($teacher_info['nombre'] . ' ' . $teacher_info['apellido_1']); ?>
                </span>
                <a href="logout.php" class="btn btn-danger d-flex align-items-center">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4 flex-grow-1">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary-custom text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Alumnos Asignados
                </h5>
                <span class="badge bg-light text-dark"><?php echo $student_count; ?> alumnos</span>
            </div>

            <div class="card-body p-0">
                <?php if ($student_count > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="studentTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3">Matrícula</th>
                                    <th class="px-4 py-3">Nombre</th>
                                    <th class="px-4 py-3">Apellidos</th>
                                    <th class="px-4 py-3 text-center">Evaluaciones</th>
                                    <th class="px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): 
                                    $matricula = htmlspecialchars($row['matricula']);
                                    $nombre_completo = htmlspecialchars($row['nombre'] . ' ' . $row['apellido_1'] . ' ' . $row['apellido_2']);
                                ?>
                                    <tr class="student-row" data-matricula="<?php echo $matricula; ?>">
                                        <td class="px-4 py-3"><?php echo $matricula; ?></td>
                                        <td class="px-4 py-3"><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td class="px-4 py-3"><?php echo htmlspecialchars($row['apellido_1'] . ' ' . $row['apellido_2']); ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="evaluacion_maestro_1.php?matricula=<?php echo urlencode($matricula); ?>" 
                                               class="evaluation-icon evaluation-icon-1" title="Evaluación 1">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                            <a href="evaluacion_maestro_2.php?matricula=<?php echo urlencode($matricula); ?>" 
                                               class="evaluation-icon evaluation-icon-2 mx-2" title="Evaluación 2">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                            <a href="evaluacion_maestro_3.php?matricula=<?php echo urlencode($matricula); ?>" 
                                               class="evaluation-icon evaluation-icon-3" title="Evaluación 3">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button class="btn btn-sm btn-outline-primary toggle-details me-1" data-matricula="<?php echo $matricula; ?>" title="Ver detalles">
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success edit-student" data-matricula="<?php echo $matricula; ?>" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="student-details" id="details-<?php echo $matricula; ?>">
                                        <td colspan="5" class="p-0">
                                            <div class="student-details-content">
                                                <div class="row">
                                                    <!-- Personal Information -->
                                                    <div class="col-md-4 mb-4">
                                                        <div class="info-card p-3">
                                                            <h6 class="info-card-heading">
                                                                <i class="fas fa-user me-2"></i>
                                                                Información Personal
                                                            </h6>
                                                            <p><span class="info-label">Matrícula:</span> <span class="info-value"><?php echo $matricula; ?></span></p>
                                                            <p><span class="info-label">Nombre completo:</span> <span class="info-value"><?php echo $nombre_completo; ?></span></p>
                                                            <p><span class="info-label">Estado:</span> <span class="badge bg-success">Activo</span></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Internship Information -->
                                                    <div class="col-md-4 mb-4">
                                                        <div class="info-card p-3">
                                                            <h6 class="info-card-heading">
                                                                <i class="fas fa-building me-2"></i>
                                                                Información de Estadía
                                                            </h6>
                                                            <p>
                                                                <span class="info-label">Empresa:</span> 
                                                                <span class="info-value">
                                                                    <?php echo !empty($row['empresa']) ? htmlspecialchars($row['empresa']) : '<span class="empty-info">No asignada</span>'; ?>
                                                                </span>
                                                            </p>
                                                            <p>
                                                                <span class="info-label">Departamento:</span> 
                                                                <span class="info-value">
                                                                    <?php echo !empty($row['departamento']) ? htmlspecialchars($row['departamento']) : '<span class="empty-info">No asignado</span>'; ?>
                                                                </span>
                                                            </p>
                                                            <p>
                                                                <span class="info-label">Proyecto:</span> 
                                                                <span class="info-value">
                                                                    <?php echo !empty($row['proyecto']) ? htmlspecialchars($row['proyecto']) : '<span class="empty-info">No asignado</span>'; ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Tutor Information -->
                                                    <div class="col-md-4 mb-4">
                                                        <div class="info-card p-3">
                                                            <h6 class="info-card-heading">
                                                                <i class="fas fa-user-tie me-2"></i>
                                                                Información del Tutor
                                                            </h6>
                                                            <p>
                                                                <span class="info-label">Nombre:</span> 
                                                                <span class="info-value">
                                                                    <?php echo !empty($row['nombre_tutor']) ? htmlspecialchars($row['nombre_tutor']) : '<span class="empty-info">No asignado</span>'; ?>
                                                                </span>
                                                            </p>
                                                            <p>
                                                                <span class="info-label">Correo:</span> 
                                                                <span class="info-value">
                                                                    <?php if (!empty($row['correo_tutor'])): ?>
                                                                        <a href="mailto:<?php echo htmlspecialchars($row['correo_tutor']); ?>" class="text-accent">
                                                                            <?php echo htmlspecialchars($row['correo_tutor']); ?>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <span class="empty-info">No asignado</span>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </p>
                                                            <p>
                                                                <span class="info-label">Teléfono:</span> 
                                                                <span class="info-value">
                                                                    <?php if (!empty($row['telefono_tutor'])): ?>
                                                                        <a href="tel:<?php echo htmlspecialchars($row['telefono_tutor']); ?>" class="text-accent">
                                                                            <?php echo htmlspecialchars($row['telefono_tutor']); ?>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <span class="empty-info">No asignado</span>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Document Management Section -->
                                                    <div class="col-12">
                                                        <div class="info-card p-3">
                                                            <h6 class="info-card-heading">
                                                                <i class="fas fa-file-alt me-2"></i>
                                                                Gestión de Documentos
                                                            </h6>
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <a href="uploads/<?php echo $matricula; ?>__evaluacion1.pdf" class="btn btn-outline-primary">
                                                                        <i class="fas fa-upload me-2"></i> Ver Archivo 1
                                                                    </a>
                                                                    
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <a href="uploads/<?php echo $matricula; ?>__evaluacion2.pdf" class="btn btn-outline-primary">
                                                                        <i class="fas fa-upload me-2"></i> Ver Archivo 2
                                                                    </a>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <a href="uploads/<?php echo $matricula; ?>__evaluacion3.pdf" class="btn btn-outline-primary">
                                                                        <i class="fas fa-upload me-2"></i> Ver Archivo 3
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning m-4">
                        <i class="fas fa-info-circle me-2"></i>
                        No tienes alumnos asignados actualmente.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-3 bg-primary-custom text-white mt-auto">
        <div class="container text-center">
            <p class="small mb-0">
                © <?php echo date('Y'); ?> Sistema de Gestión Académica | 
                <a href="#" class="text-white text-decoration-none">Soporte</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="./bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="./sweetalert2/dist/sweetalert2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle student details
            document.querySelectorAll('.toggle-details').forEach(button => {
                button.addEventListener('click', function() {
                    const matricula = this.getAttribute('data-matricula');
                    const detailsRow = document.getElementById('details-' + matricula);
                    
                    // Toggle icon
                    if (this.querySelector('i').classList.contains('fa-chevron-down')) {
                        this.querySelector('i').classList.replace('fa-chevron-down', 'fa-chevron-up');
                    } else {
                        this.querySelector('i').classList.replace('fa-chevron-up', 'fa-chevron-down');
                    }
                    
                    // Toggle row selection
                    const parentRow = this.closest('tr');
                    parentRow.classList.toggle('selected-row');
                    
                    // Toggle details visibility
                    detailsRow.classList.toggle('show');
                });
            });
            
            // View documents functionality
            document.querySelectorAll('.evaluation-icon').forEach(icon => {
                icon.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const url = this.getAttribute('href');
                    const title = this.getAttribute('title');
                    
                    Swal.fire({
                        title: title,
                        text: 'Selecciona una opción:',
                        icon: 'info',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Ver documento',
                        denyButtonText: 'Descargar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#3498db',
                        denyButtonColor: '#2ecc71',
                        cancelButtonColor: '#6c757d',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open(url, '_blank');
                        } else if (result.isDenied) {
                            const downloadLink = document.createElement('a');
                            downloadLink.href = url;
                            downloadLink.download = title + '.pdf';
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            document.body.removeChild(downloadLink);
                        }
                    });
                });
            });
            
            // Edit student functionality
            document.querySelectorAll('.edit-student').forEach(button => {
                button.addEventListener('click', function() {
                    const matricula = this.getAttribute('data-matricula');
                    const studentRow = this.closest('tr');
                    const nombre = studentRow.cells[1].textContent.trim();
                    const apellidos = studentRow.cells[2].textContent.trim();
                    
                    // Get the corresponding details row element
                    const detailsRow = document.getElementById('details-' + matricula);
                    
                    // Extract internship data from the details row
                    let empresa = '', departamento = '', proyecto = '', 
                        nombre_tutor = '', correo_tutor = '', telefono_tutor = '';
                    
                    if (detailsRow.classList.contains('show')) {
                        // If details are already showing, extract values from the visible elements
                        const infoElements = detailsRow.querySelectorAll('.info-value');
                        if (infoElements.length >= 9) { // Make sure we have all the expected fields
                            empresa = infoElements[3].textContent.trim();
                            departamento = infoElements[4].textContent.trim();
                            proyecto = infoElements[5].textContent.trim();
                            nombre_tutor = infoElements[6].textContent.trim();
                            
                            // For email and phone, we need to check if they contain links
                            const emailElement = infoElements[7].querySelector('a');
                            correo_tutor = emailElement ? emailElement.textContent.trim() : '';
                            
                            const phoneElement = infoElements[8].querySelector('a');
                            telefono_tutor = phoneElement ? phoneElement.textContent.trim() : '';
                        }
                    }
                    
                    // Remove "No asignado" placeholder text
                    empresa = empresa === 'No asignada' ? '' : empresa;
                    departamento = departamento === 'No asignado' ? '' : departamento;
                    proyecto = proyecto === 'No asignado' ? '' : proyecto;
                    nombre_tutor = nombre_tutor === 'No asignado' ? '' : nombre_tutor;
                    
                    Swal.fire({
                        title: 'Editar información del alumno',
                        html: `
                            <form id="editStudentForm" class="text-start">
                                <div class="mb-3">
                                    <label for="studentMatricula" class="form-label fw-bold">Matrícula</label>
                                    <input type="text" class="form-control" id="studentMatricula" value="${matricula}" readonly>
                                </div>
                                
                                <h6 class="mt-4 mb-3 fw-bold border-bottom pb-2">Información Personal</h6>
                                <div class="mb-3">
                                    <label for="studentName" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="studentName" value="${nombre}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="studentLastName" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="studentLastName" value="${apellidos}" required>
                                </div>
                                
                                <h6 class="mt-4 mb-3 fw-bold border-bottom pb-2">Información de Estadía</h6>
                                <div class="mb-3">
                                    <label for="studentCompany" class="form-label">Empresa</label>
                                    <input type="text" class="form-control" id="studentCompany" value="${empresa}">
                                </div>
                                <div class="mb-3">
                                    <label for="studentDepartment" class="form-label">Departamento</label>
                                    <input type="text" class="form-control" id="studentDepartment" value="${departamento}">
                                </div>
                                <div class="mb-3">
                                    <label for="studentProject" class="form-label">Proyecto</label>
                                    <input type="text" class="form-control" id="studentProject" value="${proyecto}">
                                </div>
                                
                                <h6 class="mt-4 mb-3 fw-bold border-bottom pb-2">Información del Tutor</h6>
                                <div class="mb-3">
                                    <label for="tutorName" class="form-label">Nombre del Tutor</label>
                                    <input type="text" class="form-control" id="tutorName" value="${nombre_tutor}">
                                </div>
                                <div class="mb-3">
                                    <label for="tutorEmail" class="form-label">Correo del Tutor</label>
                                    <input type="email" class="form-control" id="tutorEmail" value="${correo_tutor}">
                                </div>
                                <div class="mb-3">
                                    <label for="tutorPhone" class="form-label">Teléfono del Tutor</label>
                                    <input type="tel" class="form-control" id="tutorPhone" value="${telefono_tutor}">
                                </div>
                            </form>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Guardar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#2ecc71',
                        cancelButtonColor: '#6c757d',
                        width: '600px',
                        customClass: {
                            popup: 'swal-wide'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cambios guardados',
                                text: 'La información del alumno ha sido actualizada.'
                            }).then(() => {
                                // In a real application, you would save the data via AJAX
                                // and then refresh the UI. For now, we'll just simulate it
                                // by forcing a page reload.
                                location.reload();
                            });
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>