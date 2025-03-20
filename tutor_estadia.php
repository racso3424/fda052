<?php
session_start();
require 'db_conexion.php';

if (!isset($_SESSION['correo_tutor'])) {
    header("Location: index.php");
    exit();
}

$correo_tutor = $_SESSION['correo_tutor'];

// Fetch teacher name and email for personalization
$teacher_query = "SELECT nombre_tutor, correo_tutor FROM alumnos_estadia WHERE correo_tutor = ?";
$teacher_stmt = $conn->prepare($teacher_query);
$teacher_stmt->bind_param("s", $correo_tutor);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher_info = $teacher_result->fetch_assoc();

// Fetch students assigned to the tutor based on their email
$query = "SELECT 
            alumnos.matricula, 
            alumnos.nombre, 
            alumnos.apellido_1, 
            alumnos.apellido_2
          FROM alumnos_estadia a
          INNER JOIN alumnos ON a.matricula = alumnos.matricula
          WHERE a.correo_tutor = ?
          ORDER BY alumnos.apellido_1, alumnos.apellido_2";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $correo_tutor);
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
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="./bootstrap-5.3.3-dist/css/bootstrap.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="./sweetalert2/dist/sweetalert2.min.css">
    
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-primary': '#2C3E50',
                        'brand-secondary': '#34495E',
                        'brand-accent': '#3498DB',
                        'brand-background': '#ECF0F1'
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
        }
        
        .estadia-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out;
            background-color: #f8f9fa;
        }
        
        .estadia-details.show {
            max-height: 500px;
            transition: max-height 0.5s ease-in;
        }
        
        tr.selected-row {
            background-color: rgba(52, 152, 219, 0.1);
        }
    </style>
</head>
<body class="bg-brand-background min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-brand-primary text-white py-4 shadow-md">
        <div class="container mx-auto px-4 flex justify-between items-center">
                <div class="d-flex align-items-center">
                    <img src="UTC_logo.png" alt="Institution Logo" class="mr-3" style="height: 50px;">
                    <h2 class="text-xl font-bold text-gray-800 mb-0">Panel de Tutores</h2>
                </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm">
                    <i class="fas fa-user-circle mr-2"></i>
                    <?php echo htmlspecialchars($teacher_info['nombre_tutor'] . ' '); ?>
                </span>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-md transition-colors flex items-center">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 flex-grow">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Students Overview -->
            <div class="bg-brand-secondary text-white p-6 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold">
                        <i class="fas fa-users mr-3"></i>
                        Alumnos Asignados
                    </h2>
                </div>
            </div>

            <!-- Student Table -->
            <?php if ($student_count > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full" id="studentTable">
                    <thead class="bg-gray-100 border-b">
    <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matrícula</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Apellidos</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evaluación</th> <!-- Nueva columna -->
    </tr>
</thead>
<tbody class="bg-white divide-y divide-gray-200">
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr class="hover:bg-gray-50 transition-colors student-row" data-matricula="<?php echo htmlspecialchars($row['matricula']); ?>">
            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['matricula']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['nombre']); ?></td>
            <td class="px-6 py-4 whitespace-nowrap">
                <?php echo htmlspecialchars($row['apellido_1'] . ' ' . $row['apellido_2']); ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center space-x-2">
                    <button class="text-blue-500 hover:text-blue-700 view-estadia" data-matricula="<?php echo $row['matricula']; ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="text-green-500 hover:text-green-700 edit-student" data-matricula="<?php echo $row['matricula']; ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap"> <!-- Nueva columna para evaluación -->
                <a href="evaluacion_1.php?matricula=<?php echo urlencode($row['matricula']); ?>" 
                   class="text-purple-500 hover:text-purple-700">
                    <i class="fas fa-file-alt"></i>
                </a>

                <a href="evaluacion_2.php?matricula=<?php echo urlencode($row['matricula']); ?>" 
                   class="text-purple-500 hover:text-purple-700">
                    <i class="fas fa-file-alt"></i>
                </a>
                <a href="evaluacion_3.php?matricula=<?php echo urlencode($row['matricula']); ?>" 
                   class="text-purple-500 hover:text-purple-700">
                    <i class="fas fa-file-alt"></i>
                </a>
            </td>
            
        </tr>
    <?php endwhile; ?>
</tbody>

                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-10 bg-yellow-50">
                    <p class="text-yellow-700 text-lg">
                        <i class="fas fa-info-circle mr-2"></i>
                        No tienes alumnos asignados actualmente.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-brand-primary text-white py-4">
        <div class="container mx-auto px-4 text-center">
            <p class="text-sm">
                © <?php echo date('Y'); ?> Sistema de Gestión Académica | 
                <a href="#" class="hover:text-brand-accent">Soporte</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="./bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="./sweetalert2/dist/sweetalert2.min.js"></script>

    <!-- Modal -->
    <div class="modal fade" id="evaluationModal" tabindex="-1" aria-labelledby="evaluationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evaluationModalLabel">Seleccionar Evaluación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Selecciona la evaluación que deseas ver:</p>
                <button class="btn btn-primary w-full" id="evaluation1Btn">Evaluación 1</button>
                <button class="btn btn-secondary w-full mt-2" id="evaluation2Btn">Evaluación 2</button>
                <button class="btn btn-success w-full mt-2" id="evaluation3Btn">Evaluación 3</button>
            </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Obtener los botones de vista
            document.querySelectorAll('.view-estadia').forEach(button => {
                button.addEventListener('click', function () {
                    const matricula = this.getAttribute('data-matricula');
                    
                    // Abrir el modal
                    const modal = new bootstrap.Modal(document.getElementById('evaluationModal'));
                    modal.show();

                    // Añadir los listeners a los botones del modal
                    document.getElementById('evaluation1Btn').onclick = function () {
                        // Redirige a la descarga del archivo de Evaluación 1 para la matrícula específica
                        window.location.href = `uploads/${matricula}_evaluacion1.pdf`;
                    };

                    document.getElementById('evaluation2Btn').onclick = function () {
                        // Redirige a la descarga del archivo de Evaluación 2 para la matrícula específica
                        window.location.href = `uploads/${matricula}_evaluacion2.pdf`;
                    };

                    document.getElementById('evaluation3Btn').onclick = function () {
                        // Redirige a la descarga del archivo de Evaluación 3 para la matrícula específica
                        window.location.href = `uploads/${matricula}_evaluacion3.pdf`;
                    };
                });
            });
        });
    </script>

</body>
</html>

<?php
$stmt->close();
$teacher_stmt->close();
$conn->close();
?>
