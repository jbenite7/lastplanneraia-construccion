<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DataTables Editable con Filtros</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>

<button id="exportarJSON">Exportar JSON</button>

<table id="miTabla" class="display">
    <thead>
        <!-- Primera fila (encabezado) -->
        <tr>
            <th>Nombre</th>
            <th>Fecha</th>
            <th>Ciudad</th>
        </tr>
        <!-- Segunda fila (filtros) -->
        <tr>
            <th><input type="text" placeholder="Buscar Nombre"></th>
            <th><input type="date"></th>
            <th>
                <select>
                    <option value="">Todas</option>
                    <option value="Madrid">Madrid</option>
                    <option value="Barcelona">Barcelona</option>
                    <option value="Valencia">Valencia</option>
                </select>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td contenteditable="true">Juan</td>
            <td><input type="date" value="2025-02-01"></td>
            <td>
                <select>
                    <option value="Madrid" selected>Madrid</option>
                    <option value="Barcelona">Barcelona</option>
                    <option value="Valencia">Valencia</option>
                </select>
            </td>
        </tr>
        <tr>
            <td contenteditable="true">Ana</td>
            <td><input type="date" value="2025-02-02"></td>
            <td>
                <select>
                    <option value="Madrid">Madrid</option>
                    <option value="Barcelona" selected>Barcelona</option>
                    <option value="Valencia">Valencia</option>
                </select>
            </td>
        </tr>
        <tr>
            <td contenteditable="true">Luis</td>
            <td><input type="date" value="2025-02-08"></td>
            <td>
                <select>
                    <option value="Madrid">Madrid</option>
                    <option value="Barcelona">Barcelona</option>
                    <option value="Valencia" selected>Valencia</option>
                </select>
            </td>
        </tr>
    </tbody>
</table>

<script>
    $(document).ready(function() {
        let table = $('#miTabla').DataTable({
            orderCellsTop: true, 
            fixedHeader: true
        });

        // Filtro por nombre
        $('#miTabla thead tr:eq(1) th input[type="text"]').on('keyup', function() {
            let columnIndex = $(this).parent().index();
            table.column(columnIndex).search(this.value).draw();
        });

        // Filtro por fecha
        $('#miTabla thead tr:eq(1) th input[type="date"]').on('change', function() {
            table.draw(); // Redibujar la tabla al cambiar la fecha
        });

        // Filtro por ciudad
        $('#miTabla thead tr:eq(1) th select').on('change', function() {
            table.draw(); // Redibujar la tabla al cambiar la ciudad
        });

        // Filtro personalizado para fecha y ciudad
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            let filtroFecha = $('#miTabla thead tr:eq(1) th input[type="date"]').val();
            let filtroCiudad = $('#miTabla thead tr:eq(1) th select').val();

            let fechaEnTabla = $(table.row(dataIndex).node()).find('input[type="date"]').val();
            let ciudadEnTabla = $(table.row(dataIndex).node()).find('select').val();

            let fechaCoincide = !filtroFecha || fechaEnTabla === filtroFecha;
            let ciudadCoincide = !filtroCiudad || ciudadEnTabla === filtroCiudad;

            return fechaCoincide && ciudadCoincide;
        });

        // Exportar JSON
        $('#exportarJSON').on('click', function() {
            let data = [];

            $('#miTabla tbody tr').each(function() {
                let nombre = $(this).find('td:eq(0)').text().trim();
                let fecha = $(this).find('td:eq(1) input[type="date"]').val();
                let ciudad = $(this).find('td:eq(2) select').val();

                data.push({ nombre, fecha, ciudad });
            });

            let jsonData = JSON.stringify(data, null, 4);
            console.log(jsonData);
            alert("Datos exportados a JSON (ver consola)");
        });

        // Evento para capturar cambios en las celdas editables (Nombre)
        $('#miTabla tbody').on('blur', 'td[contenteditable="true"]', function() {
            let cell = table.cell(this);
            cell.data($(this).text()).draw();
        });

        // Evento para capturar cambios en el selector de fecha dentro de la tabla
        $('#miTabla tbody').on('change', 'input[type="date"]', function() {
            let cell = table.cell($(this).closest('td'));
            cell.data('<input type="date" value="' + $(this).val() + '">').draw();
        });

        // Evento para capturar cambios en la lista desplegable dentro de la tabla
        $('#miTabla tbody').on('change', 'select', function() {
            let cell = table.cell($(this).closest('td'));
            let selectedValue = $(this).val();
            cell.data('<select>' +
                '<option value="Madrid" ' + (selectedValue === 'Madrid' ? 'selected' : '') + '>Madrid</option>' +
                '<option value="Barcelona" ' + (selectedValue === 'Barcelona' ? 'selected' : '') + '>Barcelona</option>' +
                '<option value="Valencia" ' + (selectedValue === 'Valencia' ? 'selected' : '') + '>Valencia</option>' +
            '</select>').draw();
        });
    });
</script>

</body>
</html>