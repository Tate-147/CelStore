<?php

// Cargar dependencias de Composer
require 'vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor: configuración no encontrada.']);
    exit;
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
    exit;
}

// Obtener las credenciales de la base de datos desde el archivo .env
$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? null;
$dbUser = $_ENV['DB_USERNAME'] ?? null;
$dbPass = $_ENV['DB_PASSWORD'] ?? null;

// Verificar credenciales esenciales
if (!$dbName || !$dbUser) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de configuración: DB_DATABASE y DB_USERNAME son requeridos.']);
    exit;
}

// Conectar a la base de datos
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, (int)$dbPort);
    mysqli_set_charset($conn, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a BD: ' . $e->getMessage()]);
    exit;
}

// Crear tabla 'smartphones' si no existe
$sqlCreateTable = "
CREATE TABLE IF NOT EXISTS smartphones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    screen VARCHAR(50) NULL,
    processor VARCHAR(50) NULL,
    ram VARCHAR(50) NULL,
    rom VARCHAR(50) NULL,
    frontcamera VARCHAR(50) NULL,
    rearcamera VARCHAR(50) NULL,
    battery VARCHAR(50) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
try {
    mysqli_query($conn, $sqlCreateTable);
} catch (mysqli_sql_exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error creando tabla: ' . $e->getMessage()]);
    exit;
}

// Definir la respuesta por defecto
$response = ['status' => 'error', 'message' => 'Acción no válida o no especificada.'];
$data = null; // Para contener datos como la lista de libros
$statusCode = 400; // Bad Request por defecto

// Determinar la acción a realizar
// Usamos $_REQUEST para aceptar tanto GET como POST para 'accion'
$accion = $_REQUEST['accion'] ?? null;

try {
    switch ($accion) {
        // --- OBTENER UN CELULAR ---
        case 'obtener':
            if (!isset($_GET['id'])) {
                $response = ['status' => 'error', 'message' => 'Falta el parámetro id.'];
                $statusCode = 400; // Bad Request
                break;
            }
        
            $id = intval($_GET['id']); // Sanitizar
        
            $sql = "SELECT * FROM smartphones WHERE id = $id LIMIT 1";
            $result = mysqli_query($conn, $sql);
        
            if ($result && mysqli_num_rows($result) > 0) {
                $smartphone = mysqli_fetch_assoc($result);
                $response = ['status' => 'success', 'message' => 'Celular encontrado.', 'data' => $smartphone];
                $statusCode = 200; // OK
            } else {
                $response = ['status' => 'error', 'message' => 'Celular no encontrado.'];
                $statusCode = 404; // Not Found
            }
        
            mysqli_free_result($result);
            break;

        // --- LISTAR CELULARES ---
        case 'listar':
            $allowedSortColumns = ['id', 'brand', 'model'];
            $sortColumn = 'id';
            $sortDir = 'ASC';
        
            // Ordenamiento si está presente
            if (isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortColumns)) {
                $sortColumn = $_GET['sort'];
            }
            if (isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC'])) {
                $sortDir = strtoupper($_GET['dir']);
            }
        
            // Filtros si están presentes
            $filters = [];
            $params = [];
        
            if (!empty($_GET['brand'])) {
                $filters[] = "brand LIKE ?";
                $params[] = "%" . $_GET['brand'] . "%";
            }
            if (!empty($_GET['model'])) {
                $filters[] = "model LIKE ?";
                $params[] = "%" . $_GET['model'] . "%";
            }
        
            $whereClause = count($filters) > 0 ? "WHERE " . implode(" AND ", $filters) : "";
        
            // Preparar SQL
            $sqlSelect = "SELECT * FROM smartphones $whereClause ORDER BY $sortColumn $sortDir";
        
            // Preparar y ejecutar consulta
            $stmt = mysqli_prepare($conn, $sqlSelect);
            if (count($params) > 0) {
                // Tipos de datos (todos string: 's')
                $types = str_repeat('s', count($params));
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
        
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        
            $smartphones = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $smartphones[] = $row;
            }
            mysqli_free_result($result);
            mysqli_stmt_close($stmt);
        
            $response = ['status' => 'success', 'message' => 'Celulares listados correctamente.'];
            $data = $smartphones;
            $statusCode = 200;
            break;

        // --- CREAR CELULAR ---
        case 'crear':
            // Validar que el método sea POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                 $response['message'] = 'Método no permitido para crear (se requiere POST).';
                 $statusCode = 405; // Method Not Allowed
                 break;
            }
            $brand = trim($_POST['brand'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $screen = trim($_POST['screen'] ?? '');
            $processor = trim($_POST['processor'] ?? '');
            $ram = trim($_POST['ram'] ?? '');
            $rom = trim($_POST['rom'] ?? '');
            $frontcamera = trim($_POST['frontcamera'] ?? '');
            $rearcamera = trim($_POST['rearcamera'] ?? '');
            $battery = trim($_POST['battery'] ?? '');

            if (!empty($brand) && !empty($model)) {
                $sql = "INSERT INTO smartphones (brand, model, screen, processor, ram, rom, frontcamera, rearcamera, battery) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssssss", $brand, $model, $screen, $processor, $ram, $rom, $frontcamera, $rearcamera, $battery);
                if (mysqli_stmt_execute($stmt)) {
                    $newId = mysqli_insert_id($conn); // Obtener el ID del celular recién creado
                    $response = ['status' => 'success', 'message' => "Celular '$titulo' creado exitosamente."];
                    $data = ['id' => $newId, 'brand' => $brand, 'model' => $model, 'screen' => $screen, 'processor' => $processor, 'ram' => $ram, 'rom' => $rom, 'frontcamera' => $frontcamera, 'rearcamera' => $rearcamera, 'battery' => $battery];
                    $statusCode = 201; // Created
                } else {
                     throw new mysqli_sql_exception(mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);
            } else {
                $response['message'] = 'Error: Marca y modelo son obligatorios.';
                $statusCode = 400; // Bad Request
            }
            break;

        // --- ACTUALIZAR CELULAR ---
        case 'actualizar':
            // Validar que el método sea POST
             if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                 $response['message'] = 'Método no permitido para actualizar (se requiere POST).';
                 $statusCode = 405;
                 break;
            }
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $brand = trim($_POST['brand'] ?? '');
            $model = trim($_POST['model'] ?? '');
            $screen = trim($_POST['screen'] ? $_POST['screen'] : null);
            $processor = trim($_POST['processor'] ? $_POST['processor'] : null);
            $ram = trim($_POST['ram'] ? $_POST['ram'] : null);
            $rom = trim($_POST['rom'] ? $_POST['rom'] : null);
            $frontcamera = trim($_POST['frontcamera'] ? $_POST['frontcamera'] : null);
            $rearcamera = trim($_POST['rearcamera'] ? $_POST['rearcamera'] : null);
            $battery = trim($_POST['battery'] ? $_POST['battery'] : null);

            if ($id > 0 && !empty($brand) && !empty($model)) {
                $sql = "UPDATE smartphones SET brand = ?, model = ?, screen = ?, processor = ?, ram = ?, rom = ?, frontcamera = ?, rearcamera = ?, battery = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssssssi", $brand, $model, $screen, $processor, $ram, $rom, $frontcamera, $rearcamera, $battery, $id);
                 if (mysqli_stmt_execute($stmt)) {
                     if(mysqli_stmt_affected_rows($stmt) > 0) {
                         $response = ['status' => 'success', 'message' => "Celular ID $id actualizado exitosamente."];
                         $data = ['id' => $id, 'brand' => $brand, 'model' => $model, 'screen' => $screen, 'processor' => $processor, 'ram' => $ram, 'rom' => $rom, 'frontcamera' => $frontcamera, 'rearcamera' => $rearcamera, 'battery' => $battery];
                         $statusCode = 200; // OK
                     } else {
                         $response = ['status' => 'success', 'message' => "Celular ID $id no encontrado o sin cambios."];
                         $statusCode = 404; // Not Found (or 200 if no changes is ok)
                     }
                 } else {
                     throw new mysqli_sql_exception(mysqli_stmt_error($stmt));
                 }
                mysqli_stmt_close($stmt);
            } else {
                $response['message'] = 'Error: ID, Marca y Modelo son obligatorios para actualizar.';
                 $statusCode = 400;
            }
            break;

        // --- BORRAR LIBRO ---
        case 'borrar':
            $id_a_borrar = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

            if ($id_a_borrar > 0) {
                $sql = "DELETE FROM smartphones WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id_a_borrar);
                if (mysqli_stmt_execute($stmt)) {
                     if(mysqli_stmt_affected_rows($stmt) > 0) {
                        $response = ['status' => 'success', 'message' => "Celular ID $id_a_borrar eliminado exitosamente."];
                        $statusCode = 200; // OK (o 204 No Content)
                     } else {
                         $response = ['status' => 'error', 'message' => "Celular ID $id_a_borrar no encontrado."];
                         $statusCode = 404; // Not Found
                     }
                } else {
                    throw new mysqli_sql_exception(mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);
            } else {
                $response['message'] = 'Error: ID inválido para borrar.';
                $statusCode = 400;
            }
            break;

        default:
            // $response ya tiene el mensaje de error por defecto
             $statusCode = 400;
            break;
    }

} catch (mysqli_sql_exception $e) {
    // Captura errores generales de BD que no se manejaron antes
    $response = ['status' => 'error', 'message' => 'Error de Base de Datos: ' . $e->getMessage()];
    $statusCode = 500; // Internal Server Error
} catch (Exception $e) {
     // Captura otros errores inesperados
     $response = ['status' => 'error', 'message' => 'Error inesperado: ' . $e->getMessage()];
     $statusCode = 500;
}

// Enviar la respuesta JSON
mysqli_close($conn); // Cerrar la conexión antes de enviar la respuesta

// Añadir los datos a la respuesta si existen
if ($data !== null) {
    $response['data'] = $data;
}

header('Content-Type: application/json');
http_response_code($statusCode);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // JSON_PRETTY_PRINT para que sea legible en terminal

exit;
?>