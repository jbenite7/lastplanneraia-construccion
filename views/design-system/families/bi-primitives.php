<article data-bi-candidate="accessible-figure">
    <header><h3>Figura accesible y datos equivalentes</h3><p>La lectura no depende del color ni de una interacción con el gráfico.</p></header>
    <?= \App\View\Components\DesignSystemComponent::biFigure([
        'id' => 'bi',
        'title' => 'PAC vs Programado',
        'summary' => 'El avance ejecutado crece de 45 % a 75 %, pero permanece por debajo del plan.',
        'rows' => [
            ['label' => 'Semana 2', 'plan' => 50, 'executed' => 45],
            ['label' => 'Semana 3', 'plan' => 70, 'executed' => 60],
            ['label' => 'Semana 4', 'plan' => 90, 'executed' => 75],
        ],
    ]) ?>
    <?= \App\View\Components\DesignSystemComponent::biFigure([
        'id' => 'ppc', 'title' => 'PPC por Semana',
        'summary' => 'Los indicadores LPS se muestran como barras comparables y conservan su tabla.',
        'rows' => [
            ['label' => '¿Qué hacer?', 'plan' => 20, 'executed' => 12],
            ['label' => '¿Podemos?', 'plan' => 55, 'executed' => 48],
            ['label' => '¿Se hará?', 'plan' => 80, 'executed' => 64],
        ],
    ]) ?>
    <?= \App\View\Components\DesignSystemComponent::popover([
        'id' => 'bi-point-detail', 'label' => 'Detalle del punto Semana 4',
        'content' => 'Plan 90 %; ejecutado 75 %. La tabla conserva el dato equivalente.',
    ]) ?>
</article>
<div class="ds-bi-gallery">
    <figure class="aia-card aia-bi" aria-labelledby="curve-title">
        <figcaption><h3 id="curve-title">Curva S de ejecución</h3><p>Avance teórico y real por corte.</p></figcaption>
        <label class="aia-switch"><input type="checkbox" role="switch"><span>Proyecciones</span></label>
        <div class="aia-feedback aia-feedback--info aia-bi__guidance">Se requieren al menos 3 cortes con avance real positivo para proyectar.</div>
        <ul class="aia-bi__legend" aria-label="Series"><li>— Teórica</li><li>— Real</li></ul>
        <svg class="aia-bi__plot" viewBox="0 0 100 50" role="img" aria-label="La curva real permanece por debajo de la teórica"><g class="aia-bi__grid" aria-hidden="true"><line class="aia-bi__grid-line" x1="0" y1="10" x2="100" y2="10"></line><line class="aia-bi__grid-line" x1="0" y1="20" x2="100" y2="20"></line><line class="aia-bi__grid-line" x1="0" y1="30" x2="100" y2="30"></line><line class="aia-bi__grid-line" x1="0" y1="40" x2="100" y2="40"></line><line class="aia-bi__grid-line" x1="20" y1="0" x2="20" y2="50"></line><line class="aia-bi__grid-line" x1="40" y1="0" x2="40" y2="50"></line><line class="aia-bi__grid-line" x1="60" y1="0" x2="60" y2="50"></line><line class="aia-bi__grid-line" x1="80" y1="0" x2="80" y2="50"></line></g><polyline class="aia-bi__line aia-bi__line--plan" points="0,48 20,44 40,35 60,22 80,8 100,2"></polyline><polyline class="aia-bi__line aia-bi__line--executed" points="0,48 20,46 40,40 60,32 80,24 100,18"></polyline>
            <g aria-hidden="true"><circle class="aia-bi__point aia-bi__point--plan" cx="0" cy="48" r="1"></circle><circle class="aia-bi__point aia-bi__point--plan" cx="20" cy="44" r="1"></circle><circle class="aia-bi__point aia-bi__point--plan" cx="40" cy="35" r="1"></circle><circle class="aia-bi__point aia-bi__point--plan" cx="60" cy="22" r="1"></circle><circle class="aia-bi__point aia-bi__point--plan" cx="80" cy="8" r="1"></circle><circle class="aia-bi__point aia-bi__point--plan" cx="100" cy="2" r="1"></circle>
            <circle class="aia-bi__point aia-bi__point--executed" cx="0" cy="48" r="1"></circle><circle class="aia-bi__point aia-bi__point--executed" cx="20" cy="46" r="1"></circle><circle class="aia-bi__point aia-bi__point--executed" cx="40" cy="40" r="1"></circle><circle class="aia-bi__point aia-bi__point--executed" cx="60" cy="32" r="1"></circle><circle class="aia-bi__point aia-bi__point--executed" cx="80" cy="24" r="1"></circle><circle class="aia-bi__point aia-bi__point--executed" cx="100" cy="18" r="1"></circle></g></svg>
        <details class="aia-bi__data"><summary>Datos del gráfico</summary><p>Plan 100 %; real 64 %.</p></details>
    </figure>
    <article class="aia-card aia-bi aia-bi--gauge" aria-labelledby="gauge-title">
        <header><h3 id="gauge-title">Gauge de cumplimiento</h3><p>Lectura inmediata del avance frente al umbral operativo.</p></header>
        <div class="aia-bi-gauge__meter aia-bi-gauge__meter--caution" role="img" aria-label="Cumplimiento semanal: 78 por ciento. Umbral: 85 por ciento."><span>78 %</span></div>
        <dl class="aia-bi-gauge__summary"><div><dt>Umbral</dt><dd>85 %</dd></div><div><dt>Brecha</dt><dd>7 pp</dd></div></dl>
        <meter class="aia-visually-hidden" min="0" max="100" low="70" high="85" optimum="100" value="78">78 %</meter>
        <p class="aia-feedback aia-feedback--warning">La brecha requiere proteger los compromisos críticos antes del siguiente corte.</p>
    </article>
    <figure class="aia-card aia-bi aia-bi--funnel" aria-labelledby="funnel-title">
        <figcaption><h3 id="funnel-title">Embudo de compromisos</h3><p>Visibiliza dónde se pierden actividades antes de llegar a ejecución.</p></figcaption>
        <ol class="aia-bi-funnel" aria-label="Embudo de compromisos de la semana">
            <li class="aia-bi-funnel__item aia-bi-funnel__item--committed"><span class="aia-bi-funnel__trigger" tabindex="0" aria-describedby="funnel-committed-detail"><span>Comprometidas</span><strong>48</strong></span><span id="funnel-committed-detail" class="aia-bi-funnel__tooltip" role="tooltip">48 actividades comprometidas al inicio de la semana.</span></li>
            <li class="aia-bi-funnel__item aia-bi-funnel__item--ready"><span class="aia-bi-funnel__trigger" tabindex="0" aria-describedby="funnel-ready-detail"><span>Listas</span><strong>39</strong></span><span id="funnel-ready-detail" class="aia-bi-funnel__tooltip" role="tooltip">9 actividades aún dependen de una restricción o recurso.</span></li>
            <li class="aia-bi-funnel__item aia-bi-funnel__item--active"><span class="aia-bi-funnel__trigger" tabindex="0" aria-describedby="funnel-active-detail"><span>En ejecución</span><strong>31</strong></span><span id="funnel-active-detail" class="aia-bi-funnel__tooltip" role="tooltip">8 actividades listas todavía no iniciaron en el frente de obra.</span></li>
            <li class="aia-bi-funnel__item aia-bi-funnel__item--completed"><span class="aia-bi-funnel__trigger" tabindex="0" aria-describedby="funnel-completed-detail"><span>Terminadas</span><strong>26</strong></span><span id="funnel-completed-detail" class="aia-bi-funnel__tooltip" role="tooltip">5 actividades en ejecución requieren seguimiento para cerrar el compromiso.</span></li>
        </ol>
        <details class="aia-bi__data"><summary>Datos del embudo</summary><table><thead><tr><th scope="col">Etapa</th><th scope="col">Actividades</th></tr></thead><tbody><tr><th scope="row">Comprometidas</th><td>48</td></tr><tr><th scope="row">Listas</th><td>39</td></tr><tr><th scope="row">En ejecución</th><td>31</td></tr><tr><th scope="row">Terminadas</th><td>26</td></tr></tbody></table></details>
    </figure>
    <figure class="aia-card aia-bi aia-bi--radar" aria-labelledby="radar-title">
        <figcaption><h3 id="radar-title">Preparación por dimensión</h3><p>Comparación equilibrada de cinco indicadores.</p></figcaption>
        <svg class="aia-bi-radar" role="img" viewBox="0 0 120 110" aria-label="Radar: planificación 80, restricciones 55, recursos 70, calidad 85 y seguridad 75 por ciento">
            <polygon class="aia-bi-radar__grid" points="60,10 108,45 90,100 30,100 12,45"></polygon><polygon class="aia-bi-radar__grid" points="60,30 88,50 78,82 42,82 32,50"></polygon>
            <path class="aia-bi-radar__axis" d="M60 55V10M60 55L108 45M60 55L90 100M60 55L30 100M60 55L12 45"></path>
            <polygon class="aia-bi-radar__shape" points="60,19 86,50 81,88 38,88 27,49"></polygon>
            <g aria-hidden="true"><circle class="aia-bi-radar__point" cx="60" cy="19" r="1.5"></circle><circle class="aia-bi-radar__point" cx="86" cy="50" r="1.5"></circle><circle class="aia-bi-radar__point" cx="81" cy="88" r="1.5"></circle><circle class="aia-bi-radar__point" cx="38" cy="88" r="1.5"></circle><circle class="aia-bi-radar__point" cx="27" cy="49" r="1.5"></circle></g>
            <g aria-hidden="true"><text class="aia-bi-radar__label" x="60" y="7" text-anchor="middle">Planificación</text><text class="aia-bi-radar__label" x="118" y="43" text-anchor="end">Restricciones</text><text class="aia-bi-radar__label" x="116" y="108" text-anchor="end">Recursos</text><text class="aia-bi-radar__label" x="36" y="108" text-anchor="end">Calidad</text><text class="aia-bi-radar__label" x="1" y="43">Seguridad</text></g>
        </svg>
        <details class="aia-bi__data"><summary>Datos del radar</summary><p>Planificación 80 % · Restricciones 55 % · Recursos 70 % · Calidad 85 % · Seguridad 75 %.</p></details>
    </figure>
    <article class="aia-card aia-bi aia-bi--metrics" aria-labelledby="metrics-title">
        <header><h3 id="metrics-title">Resumen del corte</h3><p>Métricas que permiten decidir si se sostiene el compromiso de la semana.</p></header>
        <dl class="aia-bi-metrics">
            <div><dt>PPC</dt><dd><strong>78 %</strong><span>Meta 85 % · brecha 7 pp</span></dd></div>
            <div><dt>Restricciones</dt><dd><strong>12</strong><span>4 vencen en 48 h</span></dd></div>
            <div><dt>Recuperación</dt><dd><strong>3 días</strong><span>Frente a la Curva S</span></dd></div>
        </dl>
        <p class="aia-feedback aia-feedback--warning">La brecha de PPC requiere revisar los compromisos de redes antes del siguiente corte.</p>
    </article>
    <figure class="aia-card aia-bi aia-bi--ranked" aria-labelledby="ranked-title">
        <figcaption><h3 id="ranked-title">Causas que concentran restricciones</h3><p>Ranking para orientar la conversación de planificación.</p></figcaption>
        <svg class="aia-bi-ranked__plot" role="img" viewBox="0 0 100 68" aria-label="Diseño de redes concentra 12 restricciones, suministro de acero 8 y permisos de izaje 5">
            <g aria-hidden="true"><text class="aia-bi-ranked__label" x="0" y="9">Diseño de redes</text><rect class="aia-bi-ranked__track" x="0" y="13" width="100" height="8" rx="2"></rect><rect class="aia-bi-ranked__bar" x="0" y="13" width="84" height="8" rx="2"></rect><text class="aia-bi-ranked__value" x="88" y="20">12</text><text class="aia-bi-ranked__label" x="0" y="31">Suministro de acero</text><rect class="aia-bi-ranked__track" x="0" y="35" width="100" height="8" rx="2"></rect><rect class="aia-bi-ranked__bar" x="0" y="35" width="56" height="8" rx="2"></rect><text class="aia-bi-ranked__value" x="60" y="42">8</text><text class="aia-bi-ranked__label" x="0" y="53">Permisos de izaje</text><rect class="aia-bi-ranked__track" x="0" y="57" width="100" height="8" rx="2"></rect><rect class="aia-bi-ranked__bar" x="0" y="57" width="35" height="8" rx="2"></rect><text class="aia-bi-ranked__value" x="39" y="64">5</text></g>
        </svg>
        <details class="aia-bi__data"><summary>Datos del ranking</summary><table><thead><tr><th scope="col">Causa</th><th scope="col">Restricciones</th></tr></thead><tbody><tr><th scope="row">Diseño de redes</th><td>12</td></tr><tr><th scope="row">Suministro de acero</th><td>8</td></tr><tr><th scope="row">Permisos de izaje</th><td>5</td></tr></tbody></table></details>
    </figure>
    <article class="aia-card aia-bi aia-bi--matrix" aria-labelledby="matrix-title">
        <header><h3 id="matrix-title">Matriz de decisión semanal</h3><p>La urgencia y la criticidad se leen con acción y responsable, no solo por color.</p></header>
        <div class="aia-table-shell"><table><thead><tr><th scope="col">Prioridad</th><th scope="col">Acción</th><th scope="col">Responsable</th></tr></thead><tbody><tr><th scope="row">Hoy · crítica</th><td>Recuperar diseño de redes</td><td>Director de obra</td></tr><tr><th scope="row">Esta semana · alta</th><td>Confirmar suministro de acero</td><td>Residente</td></tr><tr><th scope="row">Siguiente corte · media</th><td>Validar permiso de izaje</td><td>Seguridad</td></tr></tbody></table></div>
    </article>
</div>
