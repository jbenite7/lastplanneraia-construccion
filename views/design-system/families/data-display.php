<article data-data-display-candidate="responsive-shell">
    <header><p class="ds-lab__eyebrow">Patrón aprobado</p><h3>Tabla compacta y tarjetas Touch</h3><p>Una misma colección alimenta ambas representaciones responsive.</p></header>
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
