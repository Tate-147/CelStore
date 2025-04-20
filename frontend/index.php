<?php
// Constante con la URL del backend
define('BACKEND_URL', 'http://localhost:8001/index.php');

// Función general para interactuar con el backend
function callBackend($urlParams = [], $method = 'GET') {
    $method = strtoupper($method);
    $ch = curl_init();

    $options = [
        CURLOPT_RETURNTRANSFER => true,
    ];

    if ($method === 'GET') {
        $options[CURLOPT_URL] = BACKEND_URL . (!empty($urlParams) ? '?' . http_build_query($urlParams) : '');
    } else {
        $options[CURLOPT_URL] = BACKEND_URL;
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($urlParams);
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Función auxiliar para generar URLs con sort y order
function sortUrl($field) {
    $params = $_GET;
    $params['sort'] = $field;
    $params['order'] = ($_GET['sort'] ?? '') === $field && ($_GET['order'] ?? 'asc') === 'asc' ? 'desc' : 'asc';
    return 'index.php?' . http_build_query($params);
}

// Función para mostrar íconos
function sortIcon($field) {
    if (($_GET['sort'] ?? '') === $field) {
        return ($_GET['order'] ?? 'asc') === 'asc' ? ' <i class="fas fa-arrow-up"></i>' : ' <i class="fas fa-arrow-down"></i>';
    }
    return ' <i class="fas fa-sort"></i>';
}

// Leer filtros desde la URL
$get = array_map('trim', $_GET);

$params = ['accion' => 'listar'];
foreach (['brand', 'model', 'sort', 'order'] as $key) {
    if (!empty($get[$key])) $params[$key] = $get[$key];
}

// Obtener celulares
$response = callBackend($params, 'GET');
$celulares = ($response && $response["status"] === "success") ? $response["data"] : [];

// Procesar alta/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    callBackend($_POST, 'POST');
    header("Location: index.php");
    exit;
}

// Procesar eliminación
if (isset($_GET['delete'])) {
    callBackend(['accion' => 'borrar', 'id' => $_GET['delete']], 'POST');
    header("Location: index.php");
    exit;
}

// Preparar datos para edición si se pasa ?edit=ID
$idEdit = $_GET['edit'] ?? null;
$smartphoneToEdit = array_fill_keys([
    'brand', 'model', 'screen', 'processor', 'ram', 'rom', 'frontcamera', 'rearcamera', 'battery'
], '');

// Obtener datos para edición
if ($idEdit) {
    $response = callBackend(['accion' => 'obtener', 'id' => $idEdit], 'GET');
    if ($response && $response["status"] === "success") {
        $smartphoneToEdit = $response["data"];
    }
}

// Campos comunes para formulario y tabla
$fields = [
    'brand' => 'Marca', 'model' => 'Modelo', 'screen' => 'Pantalla',
    'processor' => 'Procesador', 'ram' => 'RAM', 'rom' => 'ROM',
    'frontcamera' => 'Cam F', 'rearcamera' => 'Cam T', 'battery' => 'Batería'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CelStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="phone.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- CSS personalizado -->
    <style>
        .epic-title {font-size: 3.5rem; font-weight: 800; background: linear-gradient(90deg, #6f42c1, #e83e8c, #dc3545); -webkit-background-clip: text;
                     -webkit-text-fill-color: transparent; animation: shine 4s linear infinite; text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.5);}
        @keyframes shine {0% { background-position: -200%; } 100% { background-position: 200%; }}
    </style>
</head>
<body class="container bg-dark text-light p-4" data-bs-theme="dark">
    <h1 class="epic-title">CelStore</h1>
    <p class="lead fst-italic">Tecnología que te conecta con el futuro.</p>
    
    <!-- Modal agregar/editar -->
    <div class="modal fade" id="exampleModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="smartphoneForm" class="modal-content" method="POST">
                <div class="modal-header">
                    <h1 class="modal-title fs-5"><?= $idEdit ? 'Editar celular' : 'Añadir celular' ?></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="<?= $idEdit ? 'actualizar' : 'crear' ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($idEdit ?? '') ?>">
                    <?php foreach ($fields as $field => $label): ?>
                        <div class="mb-3">
                            <label for="<?= $field ?>" class="form-label"><?= $label ?></label>
                            <input type="text" name="<?= $field ?>" id="<?= $field ?>" class="form-control"
                                   value="<?= htmlspecialchars($smartphoneToEdit[$field] ?? '') ?>" placeholder="<?= $label ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtros -->
    <div class="d-flex justify-content-between align-items-center mt-5 mb-4">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="brand" value="<?= htmlspecialchars($get['brand'] ?? '') ?>" class="form-control" placeholder="Marca">
            <input type="text" name="model" value="<?= htmlspecialchars($get['model'] ?? '') ?>" class="form-control" placeholder="Modelo">
            <button type="submit" class="btn btn-primary" title="Filtrar"><i class="fas fa-filter"></i></button>
            <a href="index.php" class="btn btn-secondary" title="Limpiar"><i class="fas fa-eraser"></i></a>
        </form>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <!-- Tabla de celulares -->
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <?php foreach ($fields as $key => $label): ?>
                <th><a href="<?= sortUrl($key) ?>"><?= $label ?><?= sortIcon($key) ?></a></th>
            <?php endforeach; ?>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
            <?php if (!empty($celulares)): ?>
                <?php foreach ($celulares as $s): ?>
                    <tr>
                        <?php foreach ($fields as $key => $label): ?>
                            <td><?= htmlspecialchars($s[$key]) ?></td>
                        <?php endforeach; ?>
                        <td>
                            <a href="?edit=<?= htmlspecialchars($s['id']) ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?= htmlspecialchars($s['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este celular?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="11" class="text-center">No se encontraron celulares.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <script>
        // Redirige al home (index.php) cuando se cierra el modal
        const modal = document.getElementById('exampleModal');
        modal.addEventListener('hidden.bs.modal', () => {
            window.location.href = 'index.php';
            document.getElementById('smartphoneForm').reset();
        });
        // Abrir modal automáticamente si venís con ?edit=...
        const hasEdit = new URLSearchParams(window.location.search).has('edit');
        if (hasEdit) {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        }
    </script>
</body>
</html>
