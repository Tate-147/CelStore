<?php
// Leer filtros desde la URL como brand y model
$filterBrand = $_GET['brand'] ?? '';
$filterModel = $_GET['model'] ?? '';

// Construir URL del backend
$url = "http://localhost:8001/index.php?accion=listar";
if ($filterBrand !== '') $url .= "&brand=" . urlencode($filterBrand);
if ($filterModel !== '') $url .= "&model=" . urlencode($filterModel);

// Obtener datos del backend
$response = file_get_contents($url);
$json = json_decode($response, true);
$celulares = [];

if ($json && $json["status"] === "success") {
    $celulares = $json["data"];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CelStore</title>
    <link rel="icon" type="image/x-icon" href="/img/phone.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            0% {background-position: -200%;}
            100% {background-position: 200%;}
        }
    </style>
</head>
<body class="container mx-auto p-4 bg-dark text-light" data-bs-theme="dark">
    <h1 class="epic-title">CelStore</h1>
    <p class="lead fst-italic mt-3">Tecnología que te conecta con el futuro.</p>

    <div class="d-flex justify-content-between align-items-center mt-5 mb-4">
        <div>
        <form method="get" class="d-flex gap-2 align-items-center">
            <input type="text" name="brand" value="<?= htmlspecialchars($filterBrand) ?>" placeholder="Filtrar por marca" class="form-control">
            <input type="text" name="model" value="<?= htmlspecialchars($filterModel) ?>" placeholder="Filtrar por modelo" class="form-control">
            <button type="submit" class="btn btn-primary" title="Filtrar">
                <i class="fas fa-filter"></i>
            </button>
            <a href="/" class="btn btn-secondary" title="Limpiar">
                <i class="fas fa-eraser"></i>
            </a>
        </form>
        </div>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exampleModal">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Id</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Pantalla</th>
                <th>Procesador</th>
                <th>RAM</th>
                <th>ROM</th>
                <th>Cam F</th>
                <th>Cam T</th>
                <th>Batería</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($celulares)): ?>
                <?php foreach ($celulares as $smartphone): ?>
                    <tr>
                        <td><?= htmlspecialchars($smartphone['id']) ?></td>
                        <td><?= htmlspecialchars($smartphone['brand']) ?></td>
                        <td><?= htmlspecialchars($smartphone['model']) ?></td>
                        <td><?= htmlspecialchars($smartphone['screen']) ?></td>
                        <td><?= htmlspecialchars($smartphone['processor']) ?></td>
                        <td><?= htmlspecialchars($smartphone['ram']) ?></td>
                        <td><?= htmlspecialchars($smartphone['rom']) ?></td>
                        <td><?= htmlspecialchars($smartphone['frontcamera']) ?></td>
                        <td><?= htmlspecialchars($smartphone['rearcamera']) ?></td>
                        <td><?= htmlspecialchars($smartphone['battery']) ?></td>
                        <td>
                            <a href="?edit=<?= $smartphone['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?= $smartphone['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
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
</body>
</html>
