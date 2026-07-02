<?php

namespace App\Support;

class SemiAutoQualityGate
{
    private ?ListadoFamilySanitizer $familySanitizer = null;

    public function groupingDimensions(array $activity, array $match): array
    {
        $text = $this->plainText($activity['Actividad'] ?? '');
        $deliverable = $this->deliverable($text);
        $context = $this->context($text);
        if ($context === 'sin_contexto' && !empty($activity['__capitulo'])) {
            $context = mb_strtolower($this->plainText($activity['__capitulo']), 'UTF-8');
        }

        return [
            'family' => (string) ($match['familia_codigo'] ?? $match['familia_nombre'] ?? 'sin_familia'),
            'primary_action' => $this->primaryAction($text),
            'deliverable' => $deliverable,
            'modality' => $this->modality($text),
            'stage' => $this->stage($text),
            'context' => $context,
            'definition_status' => $this->definitionStatus($text),
        ];
    }

    public function operationalGroupingKey(array $activity, array $match): string
    {
        return 'family:' . (int) ($match['familia_id'] ?? 0);
    }

    public function listado(array $items, array $match, float $confidence, ?string $reviewReason = null): array
    {
        $sources = [];
        $actions = [];
        $deliverables = [];
        $modalities = [];
        $riskFlags = [];
        foreach ($items as $item) {
            $text = $this->plainText($item['Actividad'] ?? '');
            $dims = $this->groupingDimensions($item, $match);
            $actions[$dims['primary_action']] = true;
            $deliverables[$dims['deliverable']] = true;
            $modalities[$dims['modality']] = true;
            $flags = $this->riskFlags($text, $match);
            foreach ($flags as $flag) {
                $riskFlags[$flag] = true;
            }
            $sources[] = $this->sourceEvidence($item, $match, $dims, $flags, 'Coincide con la familia detectada y la llave operativa de agrupación.');
        }

        if (!empty($match['contractual_only'])) {
            $riskFlags['elemento_contractual'] = true;
        }
        $conflicts = $this->conflicts(array_keys($actions), array_keys($deliverables), array_keys($modalities), array_keys($riskFlags));
        $reviewReasons = [];
        if ($reviewReason !== null && $reviewReason !== '') {
            $reviewReasons[] = $reviewReason;
        }
        if (!empty($match['contractual_only'])) {
            $reviewReasons[] = 'Es un contrato, compra, insumo, material, equipo, suministro o subpaquete; debe gestionarse en Contratos.';
        }
        if (count($actions) > 1) {
            $reviewReasons[] = 'La agrupación mezcla naturalezas de trabajo.';
        }
        if (count($deliverables) > 1) {
            $reviewReasons[] = 'La familia es amplia y contiene entregables distintos.';
        }
        if (count($modalities) > 1) {
            $reviewReasons[] = 'La agrupación mezcla modalidades operativas o contractuales.';
        }
        return $this->result($confidence, $sources, $this->firstDimensions($items, $match), $conflicts, $reviewReasons);
    }

    public function noMatch(array $activity, string $reason): array
    {
        $text = $this->plainText($activity['Actividad'] ?? '');
        return $this->result(
            0,
            [[
                'unique_id' => (int) ($activity['unique_id'] ?? $activity['Consecutivo_en_Programa'] ?? 0),
                'activity' => $text,
                'start_date' => $this->date($activity['Fecha_Inicio'] ?? null),
                'family' => '',
                'matched_by' => '',
                'rule_id' => null,
                'why_included' => 'No hay familia confiable.',
                'risk_flags' => ['sin_familia'],
            ]],
            [
                'family' => 'sin_familia',
                'primary_action' => $this->primaryAction($text),
                'deliverable' => $this->deliverable($text),
                'modality' => $this->modality($text),
                'stage' => $this->stage($text),
                'context' => $this->context($text),
                'definition_status' => $this->definitionStatus($text),
            ],
            ['No hay familia confiable para aplicar automáticamente.'],
            [$reason],
        );
    }

    public function contratos(array $activity, ?array $programActivity, array $match, array $packages, float $confidence, ?string $reviewReason = null): array
    {
        $sourceText = $programActivity !== null
            ? $this->plainText($programActivity['Actividad'] ?? '')
            : $this->plainText($activity['actividad'] ?? '');
        $dimensions = [
            'family' => (string) ($match['familia_codigo'] ?? $match['familia_nombre'] ?? 'sin_familia'),
            'primary_action' => $this->primaryAction($sourceText),
            'deliverable' => $this->deliverable($sourceText),
            'modality' => $this->contractModality($packages, $sourceText),
            'stage' => $this->stage($sourceText),
            'context' => $this->context($sourceText),
            'definition_status' => $this->definitionStatus($sourceText),
        ];
        $risks = $this->riskFlags($sourceText, $match);
        $reviewReasons = [];
        if ($reviewReason !== null && $reviewReason !== '') {
            $reviewReasons[] = $reviewReason;
        }
        if (empty($packages)) {
            $reviewReasons[] = 'La familia no tiene paquetes contractuales configurados.';
        }
        if (in_array('diseno_pendiente', $risks, true)) {
            $reviewReasons[] = 'Hay señales de diseño pendiente.';
        }
        if (($match['reviewRequired'] ?? false) || (int) ($match['siempre_revision'] ?? 0) === 1) {
            $reviewReasons[] = (string) ($match['reviewReason'] ?? 'La familia requiere revisión manual.');
        }

        $sourceActivity = $programActivity ?? [
            'unique_id' => $activity['actividadInicio'] ?? 0,
            'Actividad' => $sourceText,
            'Fecha_Inicio' => $activity['fechaInicio'] ?? null,
        ];

        return $this->result(
            $confidence,
            [$this->sourceEvidence($sourceActivity, $match, $dimensions, $risks, 'Actividad vinculada al Programa General y familia contractual detectada.')],
            $dimensions,
            [],
            $reviewReasons,
        );
    }

    public function pdc(array $activity, array $package, ?array $existing, array $diff, float $confidence, ?string $reviewReason = null): array
    {
        $text = $this->plainText(($package['paqueteNombre'] ?? '') . ' ' . ($activity['actividad'] ?? ''));
        $reviewReasons = [];
        if ($reviewReason !== null && $reviewReason !== '') {
            $reviewReasons[] = $reviewReason;
        }
        if ($existing !== null && $this->diffTouchesFilledFields($existing, $diff)) {
            $reviewReasons[] = 'El paquete ya existe y se modificarían campos con información previa.';
        }
        if ($this->containsAny($text, ['POR CONFIRMAR', 'PENDIENTE', 'DISENO', 'DISEÑO'])) {
            $reviewReasons[] = 'El paquete tiene definición pendiente.';
        }

        return $this->result($confidence, [[
            'unique_id' => (int) ($activity['actividadInicio'] ?? 0),
            'activity' => $this->plainText($activity['actividad'] ?? ''),
            'original_activity' => $this->plainText($activity['actividad'] ?? ''),
            'clean_activity' => $this->plainText($activity['actividad'] ?? ''),
            'start_date' => $this->date($activity['fechaInicio'] ?? null),
            'context' => 'pdc',
            'chapter' => 'pdc',
            'location_hint' => '',
            'intervention_hint' => '',
            'family' => 'pdc',
            'family_id' => 0,
            'matched_by' => 'contratos',
            'matched_rule' => 'contratos',
            'rule_id' => null,
            'confidence' => $confidence,
            'why_included' => 'Paquete derivado de la definición de contratos.',
            'risk_flags' => $this->riskFlags($text, null),
        ]], [
            'family' => 'pdc',
            'primary_action' => $existing === null ? 'crear_paquete' : 'actualizar_paquete',
            'deliverable' => $this->deliverable($text),
            'modality' => $this->modality((string) ($package['tipoPaquete'] ?? '')),
            'stage' => 'compras',
            'context' => 'pdc',
            'definition_status' => $this->definitionStatus($text),
        ], [], $reviewReasons);
    }

    public function description(array $items, array $match, array $gate): string
    {
        $family = mb_strtolower(trim((string) ($match['familia_nombre'] ?? 'actividad')), 'UTF-8');
        if (($gate['quality_gate']['status'] ?? '') !== 'ready') {
            return 'Alcance por revisar: actividades asociadas a ' . $family . '.';
        }

        return 'Alcance común: actividades asociadas a ' . $family . '.';
    }

    public function activityName(array $items, array $match, array $gate, bool $familyRepeats = false): string
    {
        $family = trim((string) ($match['familia_nombre'] ?? 'Actividad detectada'));
        return $this->limitLabel($family, 90);
    }

    public function isSpecificName(string $name, array $match): bool
    {
        $family = trim((string) ($match['familia_nombre'] ?? ''));
        if ($family === '') {
            return true;
        }
        $normalizedFamily = $this->canonicalLabel($family);
        $normalizedName = $this->canonicalLabel($name);
        if ($normalizedName === $normalizedFamily) {
            return false;
        }
        $suffix = trim(str_replace($normalizedFamily, '', $normalizedName));

        return mb_strlen($suffix, 'UTF-8') >= 4;
    }

    public function withReviewReason(array $gate, string $reason): array
    {
        if (($gate['quality_gate']['status'] ?? '') !== 'conflict') {
            $gate['quality_gate']['status'] = 'review';
            $gate['quality_gate']['definition_status'] = 'por_confirmar';
            $gate['quality_gate']['score'] = min((int) ($gate['quality_gate']['score'] ?? 79), 79);
        }
        $gate['quality_gate']['review_reasons'][] = $reason;
        $gate['quality_gate']['review_reasons'] = array_values(array_unique($gate['quality_gate']['review_reasons']));

        return $gate;
    }

    public function startActivityLabel(array $activity): string
    {
        $name = $this->plainText($activity['Actividad'] ?? '');
        $date = $this->date($activity['Fecha_Inicio'] ?? null);
        return trim($name . ' | ' . ($date ?? 'Fecha por confirmar'));
    }

    private function result(float $confidence, array $sources, array $dimensions, array $conflicts, array $reviewReasons): array
    {
        $readyBlockers = [];
        if (empty($sources)) {
            $readyBlockers[] = 'No hay fuentes auditables.';
        }
        if (!empty($conflicts)) {
            $readyBlockers = array_merge($readyBlockers, $conflicts);
        }
        $evidenceComplete = !empty($sources);
        foreach ($sources as $source) {
            if (
                empty($source['unique_id'])
                || empty($source['activity'])
                || empty($source['start_date'])
                || empty($source['family'])
                || empty($source['matched_rule'])
                || !isset($source['confidence'])
                || (float) $source['confidence'] <= 0
                || empty($source['why_included'])
                || empty($source['context'])
                || $source['context'] === 'sin_contexto'
            ) {
                $evidenceComplete = false;
                $readyBlockers[] = 'La evidencia de fuentes está incompleta.';
                break;
            }
        }

        $status = 'ready';
        if (!empty($readyBlockers)) {
            $status = 'conflict';
        } elseif (!empty($reviewReasons)) {
            $status = 'review';
        }

        return [
            'quality_gate' => [
                'status' => $status,
                'score' => $this->score($confidence, $status, $reviewReasons, $readyBlockers),
                'start_activity_label' => $this->sourceStartLabel($sources),
                'definition_status' => $status === 'ready' ? 'confirmado' : 'por_confirmar',
                'dimensions' => $dimensions,
                'conflicts' => array_values(array_unique($conflicts)),
                'review_reasons' => array_values(array_unique($reviewReasons)),
                'ready_blockers' => array_values(array_unique($readyBlockers)),
                'source_count' => count($sources),
                'evidence_complete' => $evidenceComplete,
            ],
            'sources' => $sources,
        ];
    }

    private function firstDimensions(array $items, array $match): array
    {
        $first = $items[0] ?? [];
        return $this->groupingDimensions($first, $match);
    }

    private function sourceEvidence(array $item, array $match, array $dimensions, array $riskFlags, string $whyIncluded): array
    {
        $text = $this->plainText($item['Actividad'] ?? $item['activity'] ?? '');
        $context = (string) ($dimensions['context'] ?? $this->context($text));
        if (($context === '' || $context === 'sin_contexto') && !empty($item['__capitulo'])) {
            $context = mb_strtolower($this->plainText($item['__capitulo']), 'UTF-8');
        }

        return [
            'unique_id' => (int) ($item['unique_id'] ?? $item['Consecutivo_en_Programa'] ?? 0),
            'activity' => $text,
            'original_activity' => $text,
            'clean_activity' => $this->cleanSourceActivity($text),
            'start_date' => $this->date($item['Fecha_Inicio'] ?? $item['start_date'] ?? null),
            'context' => $context,
            'chapter' => $context,
            'location_hint' => $this->locationHint($text),
            'intervention_hint' => $this->interventionHint($text),
            'family' => (string) ($match['familia_nombre'] ?? ''),
            'family_id' => (int) ($match['familia_id'] ?? 0),
            'matched_by' => (string) ($match['matchedBy'] ?? ''),
            'matched_rule' => (string) ($match['matchedBy'] ?? $match['id'] ?? ''),
            'rule_id' => $match['id'] ?? null,
            'confidence' => (float) ($match['confidence'] ?? $match['confianza'] ?? 0),
            'why_included' => $whyIncluded,
            'risk_flags' => $riskFlags,
        ];
    }

    private function sourceStartLabel(array $sources): string
    {
        $first = $sources[0] ?? [];
        return trim((string) ($first['activity'] ?? '') . ' | ' . ((string) ($first['start_date'] ?? 'Fecha por confirmar')));
    }

    private function score(float $confidence, string $status, array $reviewReasons, array $readyBlockers): int
    {
        $score = (int) round($confidence);
        $score -= count($reviewReasons) * 8;
        $score -= count($readyBlockers) * 20;
        if ($status === 'conflict') {
            $score = min($score, 35);
        } elseif ($status === 'review') {
            $score = min($score, 79);
        }

        return max(0, min(100, $score));
    }

    private function conflicts(array $actions, array $deliverables, array $modalities, array $riskFlags): array
    {
        $conflicts = [];
        if (in_array('retiro', $actions, true) && in_array('instalacion', $actions, true)) {
            $conflicts[] = 'Mezcla retiro/desmonte/demolición con instalación.';
        }
        if (in_array('retiro_e_instalacion', $riskFlags, true)) {
            $conflicts[] = 'Mezcla retiro/desmonte/demolición con instalación.';
        }
        if (in_array('provisional', $riskFlags, true) && in_array('definitivo', $riskFlags, true)) {
            $conflicts[] = 'Mezcla alcance provisional con definitivo.';
        }
        if (in_array('diseno_pendiente', $riskFlags, true)) {
            $conflicts[] = 'Incluye actividades con diseño pendiente.';
        }
        if (in_array('revoque_humedo', $riskFlags, true) && in_array('revoque_seco', $riskFlags, true)) {
            $conflicts[] = 'Mezcla revoque húmedo con revoque seco.';
        }
        if (in_array('deteccion_incendio', $riskFlags, true) && in_array('extincion_incendio', $riskFlags, true)) {
            $conflicts[] = 'Mezcla detección y extinción contra incendio.';
        }
        if (in_array('aparatos_o_griferias', $riskFlags, true) && in_array('redes_o_tuberias', $riskFlags, true)) {
            $conflicts[] = 'Mezcla aparatos/griferías con redes o tuberías.';
        }
        if (in_array('compra', $modalities, true) && in_array('contrato', $modalities, true)) {
            $conflicts[] = 'Mezcla pedido/orden de compra con contrato.';
        }
        if (in_array('elemento_contractual', $riskFlags, true)) {
            $conflicts[] = 'Un elemento contractual no puede quedar listo como familia de actividades.';
        }

        return $conflicts;
    }

    private function riskFlags(string $text, ?array $match): array
    {
        $normalized = $this->normalize($text);
        $flags = [];
        if ($this->containsAny($normalized, ['FALLBACK']) || (!empty($match['reviewRequired']) && str_contains((string) ($match['reviewReason'] ?? ''), 'fallback'))) {
            $flags[] = 'fallback';
        }
        if (
            $this->containsAny($normalized, ['RETIRO', 'DESMONTE', 'DEMOLICION', 'DEMOLICIÓN'])
            && $this->containsAny($normalized, ['INSTALACION', 'INSTALACIÓN', 'MONTAJE'])
        ) {
            $flags[] = 'retiro_e_instalacion';
        }
        if ($this->containsAny($normalized, ['PROVISIONAL', 'TEMPORAL'])) {
            $flags[] = 'provisional';
        }
        if ($this->containsAny($normalized, ['DEFINITIV'])) {
            $flags[] = 'definitivo';
        }
        if ($this->containsAny($normalized, ['DISENO', 'DISEÑO', 'PENDIENTE'])) {
            $flags[] = 'diseno_pendiente';
        }
        if ($this->containsAny($normalized, ['REVOQUE HUMEDO', 'REVOQUE TRADICIONAL'])) {
            $flags[] = 'revoque_humedo';
        }
        if ($this->containsAny($normalized, ['REVOQUE SECO', 'DRYWALL'])) {
            $flags[] = 'revoque_seco';
        }
        if ($this->containsAny($normalized, ['DETECCION'])) {
            $flags[] = 'deteccion_incendio';
        }
        if ($this->containsAny($normalized, ['EXTINCION', 'ROCIADOR', 'GABINETE'])) {
            $flags[] = 'extincion_incendio';
        }
        if ($this->containsAny($normalized, ['APARATO SANITARIO', 'GRIFER', 'INCRUSTACION'])) {
            $flags[] = 'aparatos_o_griferias';
        }
        if ($this->containsAny($normalized, ['TUBERIA', 'RED ', 'REDES ', 'DESAGUE'])) {
            $flags[] = 'redes_o_tuberias';
        }

        return array_values(array_unique($flags));
    }

    private function primaryAction(string $text): string
    {
        $text = $this->normalize($text);
        if ($this->containsAny($text, ['RETIRO', 'DESMONTE', 'DEMOLICION', 'DEMOLICIÓN'])) {
            return 'retiro';
        }
        if ($this->containsAny($text, ['SUMINISTRO E INSTALACION', 'SUMINISTRO E INSTALACIÓN', 'SUMINISTRO E INSTAL'])) {
            return 'suministro_instalacion';
        }
        if ($this->containsAny($text, ['INSTALACION', 'INSTALACIÓN', 'MONTAJE'])) {
            return 'instalacion';
        }
        if ($this->containsAny($text, ['SUMINISTRO', 'COMPRA', 'PEDIDO', 'ORDEN DE COMPRA'])) {
            return 'suministro';
        }
        if ($this->containsAny($text, ['FABRICACION', 'FABRICACIÓN'])) {
            return 'fabricacion';
        }
        if ($this->containsAny($text, ['PRUEBA', 'ENSAYO', 'PUESTA EN MARCHA'])) {
            return 'prueba';
        }
        if ($this->containsAny($text, ['ALQUILER', 'MALACATE', 'TORRE GRUA', 'TORRE GRÚA', 'EQUIPO'])) {
            return 'alquiler_equipo';
        }
        if ($this->containsAny($text, ['ASEO', 'LIMPIEZA'])) {
            return 'aseo';
        }
        if ($this->containsAny($text, ['INFORME', 'TRAMITE', 'TRÁMITE', 'LICENCIA', 'PMT', 'GESTION'])) {
            return 'gestion';
        }

        return 'ejecucion';
    }

    private function deliverable(string $text): string
    {
        $text = $this->normalize($text);
        $patterns = [
            'cabinas de baño' => ['CABINA'],
            'enchapes cerámicos' => ['ENCHAPE'],
            'red eléctrica' => ['ELECTRIC', 'TABLERO', 'CABLE', 'CANALIZACION', 'CANALIZACIÓN'],
            'red hidrosanitaria' => ['HIDROSANITARIA', 'TUBERIA', 'TUBERÍA', 'DESAGUE'],
            'telecomunicaciones' => ['TELECOM', 'VOZ Y DATOS', 'DATOS'],
            'revoque húmedo' => ['REVOQUE HUMEDO', 'REVOQUE TRADICIONAL'],
            'revoque seco' => ['REVOQUE SECO', 'DRYWALL'],
            'detección contra incendio' => ['DETECCION'],
            'extinción contra incendio' => ['EXTINCION', 'ROCIADOR', 'GABINETE'],
            'aparatos sanitarios' => ['APARATO SANITARIO', 'SANITARIO'],
            'griferías e incrustaciones' => ['GRIFER', 'INCRUSTACION'],
            'carpintería madera' => ['CARPINTERIA MADERA'],
            'carpintería metálica' => ['CARPINTERIA METALICA', 'VENTANERIA', 'BARANDA', 'PASAMANO', 'ESPEJO'],
            'pintura' => ['PINTURA'],
            'impermeabilización' => ['IMPERMEABILIZ'],
            'acero' => ['ACERO'],
            'concreto' => ['CONCRETO'],
            'pdc' => ['PLAN DE COMPRAS'],
        ];
        foreach ($patterns as $name => $needles) {
            if ($this->containsAny($text, $needles)) {
                return $name;
            }
        }

        $clean = preg_replace('/\[CAPITULO:\s*[^\]]+\]/u', ' ', $text) ?? $text;
        $clean = preg_replace('/\b(RETIRO|DESMONTE|DEMOLICION|DEMOLICIÓN|INSTALACION|INSTALACIÓN|SUMINISTRO|FABRICACION|FABRICACIÓN|PRUEBA|ALQUILER|COMPRA|ORDEN|PEDIDO)\b/u', '', $clean) ?? $clean;
        $clean = preg_replace('/\s+/', ' ', $clean) ?? $clean;
        return mb_strtolower(trim(mb_substr($clean, 0, 60)) ?: 'alcance general');
    }

    private function sourceScopeLabel(string $text): string
    {
        $clean = $this->plainText($text);
        $clean = preg_replace('/\[Cap[ií]tulo:\s*[^\]]+\]/iu', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\([^)]*\)/u', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\b(RETIRO|DESMONTE|DEMOLICION|DEMOLICIÓN|INSTALACION|INSTALACIÓN|SUMINISTRO|FABRICACION|FABRICACIÓN|PRUEBA|ALQUILER|COMPRA|ORDEN|PEDIDO|DE|DEL|LA|EL|LOS|LAS)\b/iu', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s*,\s*/u', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
        $clean = trim($clean, " \t\n\r\0\x0B-.,");

        return $this->titleLabel($this->limitLabel($clean, 58));
    }

    private function operationalSuffix(string $family, array $dimensions): string
    {
        $action = (string) ($dimensions['primary_action'] ?? 'ejecucion');
        $deliverable = (string) ($dimensions['deliverable'] ?? '');
        $status = (string) ($dimensions['definition_status'] ?? 'confirmado');
        $parts = [];

        if (!in_array($action, ['ejecucion', 'instalacion'], true)) {
            $parts[] = str_replace('_', ' ', $action);
        }
        if ($deliverable !== '' && !$this->sameMeaning($family, $deliverable)) {
            $parts[] = $deliverable;
        }
        if ($status !== 'confirmado') {
            $parts[] = str_replace('_', ' ', $status);
        }

        return $this->titleLabel(implode(' - ', array_values(array_unique(array_filter($parts)))));
    }

    private function cleanSourceActivity(string $text): string
    {
        $clean = preg_replace('/\[Cap[ií]tulo:\s*[^\]]+\]/iu', ' ', $this->plainText($text)) ?? $text;
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;

        return trim($clean);
    }

    private function locationHint(string $text): string
    {
        $contextHint = $this->familySanitizer()->contextHint($text);
        if ($contextHint !== '') {
            return $contextHint;
        }

        $clean = $this->cleanSourceActivity($text);
        $patterns = [
            '/\b(S[oó]tano\s*\d+)\b/iu',
            '/\b(Piso\s*\d+)\b/iu',
            '/\b(Eje(?:s)?\s*[A-Z0-9,\sY-]+)\b/iu',
            '/\b(Zona\s*[A-Z0-9]+)\b/iu',
            '/\b(Pasarela\s*\d*)\b/iu',
            '/\b(Torre\s*[A-Z0-9]+)\b/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean, $matches)) {
                return trim($matches[1]);
            }
        }

        return '';
    }

    private function familySanitizer(): ListadoFamilySanitizer
    {
        if ($this->familySanitizer === null) {
            $this->familySanitizer = new ListadoFamilySanitizer();
        }

        return $this->familySanitizer;
    }

    private function interventionHint(string $text): string
    {
        $clean = $this->plainText($text);
        $patterns = [
            '/\((\d+[A-Z])\)/iu',
            '/\b(Sub[- ]?obra\s*[A-Z0-9]+)\b/iu',
            '/\b(Intervenci[oó]n\s*[A-Z0-9]+)\b/iu',
            '/\b(Edificaci[oó]n\s+nueva)\b/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean, $matches)) {
                return trim($matches[1]);
            }
        }

        return '';
    }

    private function titleLabel(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        if ($text === '') {
            return '';
        }

        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    private function sameMeaning(string $left, string $right): bool
    {
        return $this->canonicalLabel($left) === $this->canonicalLabel($right);
    }

    private function canonicalLabel(string $text): string
    {
        $text = $this->normalize($text);
        $text = preg_replace('/\b(DE|DEL|LA|EL|LOS|LAS|EN|Y)\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/[^A-Z0-9]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function limitLabel(string $text, int $limit): string
    {
        $text = trim($text);
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8')) . '…';
    }

    private function modality(string $text): string
    {
        $text = $this->normalize($text);
        if ($this->containsAny($text, ['ORDEN DE COMPRA', 'PEDIDO', 'COMPRA'])) {
            return 'compra';
        }
        if ($this->containsAny($text, ['ALQUILER', 'EQUIPO', 'MALACATE', 'TORRE GRUA'])) {
            return 'alquiler';
        }
        if ($this->containsAny($text, ['ADMINISTRACION', 'ADMINISTRACIÓN'])) {
            return 'administracion';
        }
        if ($this->containsAny($text, ['SUMINISTRO E INSTALACION', 'SUMINISTRO E INSTALACIÓN'])) {
            return 'suministro_instalacion';
        }
        if ($this->containsAny($text, ['SUMINISTRO'])) {
            return 'suministro';
        }
        if ($this->containsAny($text, ['MANO DE OBRA', 'INSTALACION', 'INSTALACIÓN'])) {
            return 'contrato';
        }

        return 'por_definir';
    }

    private function definitionStatus(string $text): string
    {
        $text = $this->normalize($text);
        if ($this->containsAny($text, ['DISENO', 'DISEÑO'])) {
            return 'pendiente_diseno';
        }
        if ($this->containsAny($text, ['PENDIENTE', 'POR CONFIRMAR'])) {
            return 'por_confirmar';
        }
        if ($this->containsAny($text, ['COTIZACION', 'COTIZACIÓN'])) {
            return 'pendiente_cotizacion';
        }

        return 'confirmado';
    }

    private function contractModality(array $packages, string $text): string
    {
        $types = [];
        foreach ($packages as $package) {
            $types[$this->modality((string) ($package['tipoPaquete'] ?? ''))] = true;
        }
        if (!empty($types)) {
            return implode('+', array_keys($types));
        }

        return $this->modality($text);
    }

    private function stage(string $text): string
    {
        $action = $this->primaryAction($text);
        return match ($action) {
            'retiro' => 'preparacion',
            'prueba', 'aseo' => 'cierre',
            'gestion' => 'gestion',
            default => 'ejecucion',
        };
    }

    private function context(string $text): string
    {
        if (preg_match('/\[Cap[ií]tulo:\s*([^\]]+)\]/iu', $text, $matches)) {
            return mb_strtolower(trim($matches[1]));
        }

        return 'sin_contexto';
    }

    private function diffTouchesFilledFields(array $existing, array $diff): bool
    {
        foreach ($diff as $change) {
            $field = (string) ($change['field'] ?? '');
            $old = $existing[$field] ?? ($change['from'] ?? null);
            if ((string) ($old ?? '') !== '') {
                return true;
            }
        }

        return false;
    }

    private function plainText($value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtoupper($this->plainText($text), 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        return $ascii !== false ? $ascii : $text;
    }

    private function containsAny(string $text, array $needles): bool
    {
        $normalized = $this->normalize($text);
        foreach ($needles as $needle) {
            if (str_contains($normalized, $this->normalize($needle))) {
                return true;
            }
        }

        return false;
    }

    private function date($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $timestamp = strtotime((string) $value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
