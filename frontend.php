<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CelStore</title>
    <!-- Icono -->
    <link rel="icon" type="image/x-icon" href="/img/phone.ico">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <style>
        .epic-title {font-size: 3.5rem; font-weight: 800; background: linear-gradient(90deg, #6f42c1, #e83e8c, #dc3545); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
                        animation: shine 4s linear infinite; text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.5);}
        @keyframes shine {0% {background-position: -200%;} 100% {background-position: 200%;}}
    </style>
</head>
<body class="container mx-auto p-4 bg-dark text-light" data-bs-theme="dark">
    <h1 class="epic-title">CelStore</h1>
    <p class="lead fst-italic mt-3">Tecnología que te conecta con el futuro.</p>

    <div class="d-flex justify-content-between align-items-center mt-5 mb-4">
        <div>
            <!-- Formulario de filtrado -->
            <form method="get" class="d-flex gap-2 align-items-center">
                <input type="text" name="filter_brand" placeholder="Filtrar por marca" class="form-control">
                <input type="text" name="filter_model" placeholder="Filtrar por modelo" class="form-control">
                
                <!-- Botón Filtrar -->
                <button type="submit" class="btn btn-primary" title="Filtrar">
                    <i class="fas fa-filter"></i>
                </button>
                
                <!-- Botón Limpiar -->
                <a href="?" class="btn btn-secondary" title="Limpiar">
                    <i class="fas fa-eraser"></i>
                </a>
            </form>
        </div>
        <div>
            <!-- Botón para añadir libro: limpia la URL y resetea el formulario -->
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exampleModal">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>

    <!-- Tabla de celulares -->
    <table class="table table-striped table-hover" >
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
                <th>Bateria</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <script>
        // Esperamos a que el DOM esté completamente cargado
        document.addEventListener("DOMContentLoaded", function () {
            const tbody = document.querySelector("table tbody");

            fetch("http://localhost:8000/backend.php?accion=listar")
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        data.data.forEach(smartphone => {
                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td>${smartphone.id}</td>
                                <td>${smartphone.brand}</td>
                                <td>${smartphone.model}</td>
                                <td>${smartphone.screen}</td>
                                <td>${smartphone.processor}</td>
                                <td>${smartphone.ram}</td>
                                <td>${smartphone.rom}</td>
                                <td>${smartphone.frontcamera}</td>
                                <td>${smartphone.rearcamera}</td>
                                <td>${smartphone.battery}</td>
                                <td>
                                    <!--Enlace de editar: se muestra el modal con la información correspondiente -->
                                    <a href="?edit=${smartphone.id}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- Enlace de eliminar -->
                                    <a href="?delete=${smartphone.id}" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        console.error("Error al listar celulares:", data.message);
                    }
                })
                .catch(error => {
                    console.error("Error en la solicitud:", error);
                });
                
        });
    </script>
</body>
</html>