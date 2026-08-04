<article data-data-display-candidate="responsive-shell">
    <header><h3>Tabla compacta y tarjetas Touch</h3><p>Una misma colección alimenta ambas representaciones responsive.</p></header>
    <?= \App\View\Components\DesignSystemComponent::dataDisplay([
        'label' => 'Actividades',
        'records' => [
            [
                'id' => 'ACT-001', 'title' => 'Cimentación', 'status' => 'A tiempo',
                'tone' => 'success', 'progress' => '50 %',
            ],
            [
                'id' => 'ACT-002', 'title' => 'Instalación de redes hidrosanitarias',
                'status' => 'Por comprometer', 'tone' => 'warning', 'progress' => '20 %',
            ],
        ],
    ]) ?>
</article>

<article data-data-display-candidate="table-borders-abproto">
    <header>
        <h3>Task 18 · Prototipo A/B — tabla sin bordes de columna</h3>
        <p>Exploración exclusiva de laboratorio (spec §Adenda T-3). Misma colección, mismas 10 filas y anchos
            de columna dispares en ambas variantes; no toca el contrato <code>--ds-table-*</code> de producción.
            Variante A es el patrón vigente (bordes de columna y fila). Variante B propone quitar los bordes de
            columna y sostener la lectura con borde de fila, zebra sutil y una cabecera des-enfatizada.</p>
    </header>
    <?php
    $abProtoRows = [
        ['id' => 'ACT-101', 'title' => 'Cimentación', 'responsable' => 'Ana Torres', 'avance' => '50 %', 'estado' => 'A tiempo', 'tone' => 'success'],
        ['id' => 'ACT-102', 'title' => 'Instalación de redes hidrosanitarias y sanitarias del bloque 3', 'responsable' => 'Carlos Ruiz', 'avance' => '20 %', 'estado' => 'Por comprometer', 'tone' => 'warning'],
        ['id' => 'ACT-103', 'title' => 'Acero de refuerzo', 'responsable' => 'Laura Gómez', 'avance' => '80 %', 'estado' => 'A tiempo', 'tone' => 'success'],
        ['id' => 'ACT-104', 'title' => 'Mampostería de fachada oriental, niveles 1 a 4', 'responsable' => 'Diego Salazar', 'avance' => '5 %', 'estado' => 'Bloqueado', 'tone' => 'critical'],
        ['id' => 'ACT-105', 'title' => 'Impermeabilización de cubierta', 'responsable' => 'María Peña', 'avance' => '35 %', 'estado' => 'En riesgo', 'tone' => 'warning'],
        ['id' => 'ACT-106', 'title' => 'Instalaciones eléctricas provisionales de obra', 'responsable' => 'Jorge Nieto', 'avance' => '60 %', 'estado' => 'A tiempo', 'tone' => 'success'],
        ['id' => 'ACT-107', 'title' => 'Pañete', 'responsable' => 'Sandra Ríos', 'avance' => '0 %', 'estado' => 'Por comprometer', 'tone' => 'warning'],
        ['id' => 'ACT-108', 'title' => 'Fundida de placa de entrepiso nivel 5', 'responsable' => 'Pedro Álvarez', 'avance' => '92 %', 'estado' => 'A tiempo', 'tone' => 'success'],
        ['id' => 'ACT-109', 'title' => 'Enchape de baños sociales', 'responsable' => 'Camila Rojas', 'avance' => '15 %', 'estado' => 'En riesgo', 'tone' => 'warning'],
        ['id' => 'ACT-110', 'title' => 'Cerramiento perimetral en malla eslabonada', 'responsable' => 'Andrés León', 'avance' => '70 %', 'estado' => 'A tiempo', 'tone' => 'success'],
    ];
    $abProtoRenderRows = static function (array $rows): string {
        $out = '';
        foreach ($rows as $row) {
            $out .= '<tr><td>' . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['responsable'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['avance'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td><span class="aia-chip aia-chip--' . htmlspecialchars($row['tone'], ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($row['estado'], ENT_QUOTES, 'UTF-8') . '</span></td></tr>';
        }
        return $out;
    };
    $abProtoHead = '<tr><th scope="col">ID</th><th scope="col">Actividad</th><th scope="col">Responsable</th>'
        . '<th scope="col">Avance</th><th scope="col">Estado<button class="aia-table-filter-trigger" type="button"'
        . ' aria-label="Filtrar por estado" data-abproto-filter-trigger></button></th></tr>';
    ?>
    <div class="ds-table-abproto" data-ab-proto="handsontable-shape">
        <div class="ds-table-abproto__variant" data-ab-proto-variant="a">
            <h4>Variante A · actual (bordes de columna + fila)</h4>
            <div class="ds-table-abproto__table-shell ds-table-abproto__table-shell--a">
                <table>
                    <caption>Actividades · vista A, con bordes de columna</caption>
                    <thead><?= $abProtoHead ?></thead>
                    <tbody><?= $abProtoRenderRows($abProtoRows) ?></tbody>
                </table>
            </div>
        </div>
        <div class="ds-table-abproto__variant" data-ab-proto-variant="b">
            <h4>Variante B · propuesta (sin bordes de columna)</h4>
            <div class="ds-table-abproto__table-shell ds-table-abproto__table-shell--b">
                <table>
                    <caption>Actividades · vista B, sin bordes de columna</caption>
                    <thead><?= $abProtoHead ?></thead>
                    <tbody><?= $abProtoRenderRows($abProtoRows) ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="aia-copy">DataTables comparte el mismo <code>&lt;table&gt;</code> semántico que Handsontable (celdas
        <code>&lt;td&gt;</code>/<code>&lt;th&gt;</code> planas), así que la variante B de arriba también representa
        cómo se vería un DataTable sin bordes de columna: el adaptador vendor no añade separadores propios entre
        columnas, los toma de este mismo contrato de celda. No se construyó una malla Handsontable aparte para este
        prototipo porque duplicaría el mismo marcado de tabla sin aportar una lectura distinta del borde.</p>
</article>
