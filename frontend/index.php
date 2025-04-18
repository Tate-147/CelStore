<?php
// Función para obtener datos usando cURL
function getFromBackend($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Leer filtros desde la URL
$filterBrand = $_GET['brand'] ?? '';
$filterModel = $_GET['model'] ?? '';

// Construir URL del backend
$url = "http://localhost:8001/index.php?accion=listar";
if ($filterBrand !== '') $url .= "&brand=" . urlencode($filterBrand);
if ($filterModel !== '') $url .= "&model=" . urlencode($filterModel);

// Obtener celulares
$response = getFromBackend($url);
$celulares = ($response && $response["status"] === "success") ? $response["data"] : [];

// Procesar alta/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = http_build_query($_POST);
    $ch = curl_init('http://localhost:8001/index.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
    ]);
    curl_exec($ch);
    curl_close($ch);
    header("Location: index.php");
    exit;
}

// Procesar eliminación
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $deleteUrl = "http://localhost:8001/index.php";
    $postFields = http_build_query([
        'accion' => 'borrar',
        'id' => $deleteId,
    ]);
    $ch = curl_init($deleteUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
    ]);
    curl_exec($ch);
    curl_close($ch);
    header("Location: index.php");
    exit;
}

// Preparar datos para edición si se pasa ?edit=ID
$idEdit = $_GET['edit'] ?? null;
$smartphoneToEdit = [
    'brand' => '',
    'model' => '',
    'screen' => '',
    'processor' => '',
    'ram' => '',
    'rom' => '',
    'frontcamera' => '',
    'rearcamera' => '',
    'battery' => '',
];

if ($idEdit) {
    $editUrl = "http://localhost:8001/index.php?accion=obtener&id=" . urlencode($idEdit);
    $response = getFromBackend($editUrl);
    if ($response && $response["status"] === "success") {
        $smartphoneToEdit = $response["data"];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CelStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../img/phone.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- CSS personalizado -->
    <style>
        .epic-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(90deg, #6f42c1, #e83e8c, #dc3545);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shine 4s linear infinite;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.5);
        }
        @keyframes shine {
            0% { background-position: -200%; }
            100% { background-position: 200%; }
        }
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
                    <h1 class="modal-title fs-5"><?php echo $idEdit ? 'Editar celular' : 'Añadir celular'; ?></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="<?= $idEdit ? 'actualizar' : 'crear' ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($idEdit ?? '') ?>">
                    <?php
                    $fields = [
                        'brand' => 'Marca', 'model' => 'Modelo', 'screen' => 'Pantalla', 'processor' => 'Procesador',
                        'ram' => 'RAM', 'rom' => 'ROM', 'frontcamera' => 'Camara frontal',
                        'rearcamera' => 'Camara trasera', 'battery' => 'Batería'
                    ];
                    foreach ($fields as $field => $label): ?>
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
            <input type="text" name="brand" value="<?= htmlspecialchars($filterBrand) ?>" class="form-control" placeholder="Marca">
            <input type="text" name="model" value="<?= htmlspecialchars($filterModel) ?>" class="form-control" placeholder="Modelo">
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
                <th>ID</th><th>Marca</th><th>Modelo</th><th>Pantalla</th><th>Procesador</th>
                <th>RAM</th><th>ROM</th><th>Cam F</th><th>Cam T</th><th>Batería</th><th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($celulares)): ?>
                <?php foreach ($celulares as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['id']) ?></td>
                        <td><?= htmlspecialchars($s['brand']) ?></td>
                        <td><?= htmlspecialchars($s['model']) ?></td>
                        <td><?= htmlspecialchars($s['screen']) ?></td>
                        <td><?= htmlspecialchars($s['processor']) ?></td>
                        <td><?= htmlspecialchars($s['ram']) ?></td>
                        <td><?= htmlspecialchars($s['rom']) ?></td>
                        <td><?= htmlspecialchars($s['frontcamera']) ?></td>
                        <td><?= htmlspecialchars($s['rearcamera']) ?></td>
                        <td><?= htmlspecialchars($s['battery']) ?></td>
                        <td>
                            <a href="?edit=<?= $s['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este celular?');">
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
        const modal = document.getElementById('exampleModal');

        modal.addEventListener('hidden.bs.modal', () => {
            // Redirige al home (index.php) cuando se cierra el modal
            window.location.href = 'index.php';
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