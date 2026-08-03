<?php

declare(strict_types=1);

namespace App\Services\Bi;

/**
 * BI Storytelling Service.
 *
 * Composes executive briefs following the 5-sentence template (Doc Section 10.3):
 *   Estado → Causa principal → Impacto → Acción prioritaria → Confianza
 *
 * Role-aware: 'R' (Residente) gets imperative action language, day/week horizon.
 *              'D' (Director) gets executive language, week/month horizon.
 *
 * Regla de redacción: cada rama devuelve la frase completa. No se anteponen
 * prefijos fijos ("El principal driver es …") a fragmentos, porque con datos
 * vacíos producían frases agramaticales. Tampoco se interpola un conteo sin
 * ramificar en el caso cero: nadie revisa "0 actividades bloqueadas".
 */
class StorytellingService
{
    /**
     * Compose an executive brief for a report.
     *
     * @param string $reportKey One of: overview, programa-general, intermedia, semanal, pdc, cic, cip, curva-s
     * @param array  $data      Raw data from the corresponding bi_* view
     * @param string $role      Project role or MULTI for consolidated scope
     * @return array            { status, root_cause, impact, priority_action, confidence }
     */
    public function composeExecutiveBrief(string $reportKey, array $data, string $role = 'R'): array
    {
        // Consolidated reports are portfolio-level views, so their actions use
        // executive wording instead of instructions for a single resident.
        $audienceRole = $role === 'MULTI' ? 'D' : $role;

        return match ($reportKey) {
            'overview'            => $this->briefOverview($data, $audienceRole),
            'programa-general'    => $this->briefPG($data, $audienceRole),
            'intermedia'          => $this->briefPI($data, $audienceRole),
            'semanal'             => $this->briefPS($data, $audienceRole),
            'pdc'                 => $this->briefPDC($data, $audienceRole),
            'cic'                 => $this->briefCIC($data, $audienceRole),
            'cip'                 => $this->briefCIP($data, $audienceRole),
            'curva-s'             => $this->briefCurvaS($data, $audienceRole),
            default               => $this->emptyBrief(),
        };
    }

    // -----------------------------------------------------------------
    // Helpers de redacción
    // -----------------------------------------------------------------

    /** "1 actividad bloqueada" / "3 actividades bloqueadas". */
    private function cuenta(int $n, string $singular, string $plural): string
    {
        return $n . ' ' . ($n === 1 ? $singular : $plural);
    }

    /** Concordancia del verbo ser con un sujeto contado. */
    private function esSon(int $n): string
    {
        return $n === 1 ? 'es' : 'son';
    }

    /** Ensambla el brief cerrando cada frase con punto y mayúscula inicial. */
    private function brief(string $status, string $cause, string $impact, string $action, string $confidence): array
    {
        return [
            'status'          => $this->frase($status),
            'root_cause'      => $this->frase($cause),
            'impact'          => $this->frase($impact),
            'priority_action' => $this->frase($action),
            'confidence'      => $confidence,
        ];
    }

    private function frase(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }
        // ucfirst() es byte-wise; las frases arrancan con ASCII o con un número.
        $texto = ucfirst($texto);

        return str_ends_with($texto, '.') ? $texto : $texto . '.';
    }

    // -----------------------------------------------------------------
    // Per-report brief composers
    // -----------------------------------------------------------------

    private function briefOverview(array $data, string $role): array
    {
        $s = $data[0] ?? [];
        $blocked = (int)($s['hard_restriction_blocked_count'] ?? 0);
        $atRisk  = (int)($s['weekly_commitments_at_risk_count'] ?? 0);

        $level = $blocked > 10 || $atRisk > 5 ? 'alto' : ($blocked > 3 ? 'medio' : 'bajo');

        $status = "El nivel de riesgo de la obra es {$level}";

        $cause = $blocked > 0
            ? 'La causa principal ' . $this->esSon($blocked) . ' '
                . $this->cuenta($blocked, 'actividad bloqueada', 'actividades bloqueadas')
                . ' por restricciones duras'
            : 'No hay bloqueos críticos por restricciones duras';

        $impact = $atRisk > 0
            ? 'Si no se liberan, ' . $this->cuenta($atRisk, 'compromiso queda', 'compromisos quedan')
                . ' en riesgo de incumplimiento'
            : 'El plan semanal mantiene una viabilidad aceptable';

        if ($blocked > 0) {
            $action = $role === 'R'
                ? 'Revisa ' . ($blocked === 1 ? 'la actividad bloqueada' : "las {$blocked} actividades bloqueadas")
                    . ' antes del cierre de compromisos'
                : 'Priorizar la liberación de restricciones duras en las próximas 48 horas';
        } else {
            $action = $role === 'R'
                ? 'Mantén el seguimiento de restricciones antes del cierre de compromisos'
                : 'Mantener el ritmo de liberación de restricciones';
        }

        return $this->brief($status, $cause, $impact, $action, 'Media-alta: los datos de restricciones están actualizados.');
    }

    private function briefPG(array $data, string $role): array
    {
        $criticalLate = count(array_filter($data, fn($r) => ($r['is_critical_late'] ?? 0) == 1));
        $inWindow = count(array_filter($data, fn($r) => ($r['is_lookahead_window'] ?? 0) == 1));
        $notReady = count(array_filter($data, fn($r) => ($r['hard_restrictions_ready'] ?? 0) == 0 && ($r['is_lookahead_window'] ?? 0) == 1));

        if ($criticalLate > 0) {
            $status = 'La obra tiene ' . $this->cuenta($criticalLate, 'actividad crítica atrasada', 'actividades críticas atrasadas');
            $cause = 'La causa principal está en la ruta crítica: hay actividades con fecha vencida';
            $impact = 'El forecast de cierre puede desplazarse si no se recuperan';
            $action = $role === 'R'
                ? 'Recupera esta semana las actividades críticas atrasadas'
                : ($criticalLate === 1
                    ? 'Asignar recursos a la actividad crítica atrasada'
                    : "Asignar recursos a las {$criticalLate} actividades críticas atrasadas");
        } elseif ($notReady > 0) {
            $status = $this->cuenta($inWindow, 'actividad debe moverse', 'actividades deben moverse')
                . ' en las próximas 6 semanas, y ' . $this->cuenta($notReady, 'no está lista', 'no están listas');
            $cause = 'La causa principal son las restricciones duras incompletas en actividades próximas';
            $impact = 'Pueden entrar a Programación Semanal sin capacidad real de cumplimiento';
            $action = $role === 'R'
                ? 'No programes todavía las actividades con restricciones incompletas'
                : 'Escalar la liberación de restricciones antes del próximo ciclo';
        } else {
            $status = 'El programa general está al día, sin bloqueos críticos detectados';
            $cause = 'Todas las actividades en ventana tienen sus restricciones listas';
            $impact = 'El plan semanal puede construirse con confianza';
            $action = $role === 'R'
                ? 'Continúa con la programación semanal normal'
                : 'Mantener el ritmo de liberación de restricciones';
        }

        return $this->brief($status, $cause, $impact, $action, 'Alta: fechas y restricciones completas.');
    }

    private function briefPI(array $data, string $role): array
    {
        $hardNotReady = count(array_filter($data, fn($r) => ($r['is_hard'] ?? 0) == 1 && ($r['is_ready'] ?? 0) == 0));
        $byType = [];
        foreach ($data as $row) {
            if (($row['is_hard'] ?? 0) == 1 && ($row['is_ready'] ?? 0) == 0) {
                $t = $row['restriction_type'] ?? 'Otra';
                $byType[$t] = ($byType[$t] ?? 0) + 1;
            }
        }
        arsort($byType);
        $topType = array_key_first($byType) ?? '';
        $topCount = $topType !== '' ? $byType[$topType] : 0;

        if ($hardNotReady > 0) {
            $status = $this->cuenta($hardNotReady, 'restricción dura no está lista', 'restricciones duras no están listas')
                . ' en la ventana de 6 semanas';
            $cause = "El tipo «{$topType}» explica la mayoría de los bloqueos ("
                . $this->cuenta($topCount, 'actividad', 'actividades') . ')';
            $impact = $hardNotReady > 5
                ? 'Si no se liberan antes de la semana de inicio, el PAC esperado se degrada'
                : 'El impacto sobre el plan semanal es manejable';
            $action = $role === 'R'
                ? "Cierra las restricciones de tipo «{$topType}» en las actividades bloqueadas antes del jueves"
                : "Escalar las restricciones de tipo «{$topType}» a su dueño";
            $confidence = 'Media: depende de la actualización de restricciones';
        } else {
            $status = 'Todas las restricciones duras están liberadas';
            $cause = 'El Lookahead está completamente liberado';
            $impact = 'El impacto sobre el plan semanal es manejable';
            $action = $role === 'R'
                ? 'Mantén el ritmo de liberación de restricciones'
                : 'Mantener el ritmo de liberación de restricciones';
            $confidence = 'Alta';
        }

        return $this->brief($status, $cause, $impact, $action, $confidence);
    }

    private function briefPS(array $data, string $role): array
    {
        $total = count($data);
        $atRisk = count(array_filter($data, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1));
        $pacOk = count(array_filter($data, fn($r) => ($r['PAC'] ?? 0) == 1));

        if ($total === 0) {
            return $this->brief(
                'El plan semanal no tiene compromisos cargados',
                'No hay compromisos registrados para esta semana',
                'Sin compromisos no se puede evaluar la viabilidad del plan',
                $role === 'R' ? 'Carga los compromisos de la semana' : 'Exigir la carga del plan semanal',
                'Baja: sin compromisos cargados.'
            );
        }

        $pacPct = (int)round($pacOk / $total * 100);

        $status = $pacPct < 60 ? "El plan semanal tiene riesgo alto de incumplimiento (PAC {$pacPct}%)"
            : ($pacPct < 80 ? "El plan semanal tiene riesgo medio (PAC {$pacPct}%)"
            : "El plan semanal está en buen estado (PAC {$pacPct}%)");

        $cause = $atRisk > 0
            ? 'La causa principal ' . $this->esSon($atRisk) . ' '
                . $this->cuenta($atRisk, 'compromiso con alerta de cumplimiento', 'compromisos con alerta de cumplimiento')
            : 'Todos los compromisos tienen datos completos';

        $impact = $pacPct < 60
            ? 'La probabilidad de cumplir el plan semanal es baja'
            : 'El plan semanal tiene una viabilidad aceptable';

        if ($atRisk > 0) {
            $action = $role === 'R'
                ? 'Revisa ' . ($atRisk === 1 ? 'el compromiso en riesgo' : "los {$atRisk} compromisos en riesgo") . ' antes de confirmar'
                : 'Exigir plan de recuperación para los compromisos en riesgo';
        } else {
            $action = $role === 'R'
                ? 'Confirma el plan semanal: ningún compromiso está en riesgo'
                : 'Mantener el seguimiento del plan semanal';
        }

        return $this->brief($status, $cause, $impact, $action, $atRisk > 0 ? 'Media-alta' : 'Alta');
    }

    private function briefPDC(array $data, string $role): array
    {
        $notReady = count(array_filter($data, fn($r) => ($r['listo_para_iniciar'] ?? 1) == 0));

        if ($notReady > 2) {
            $status = 'Compras puede bloquear ' . $this->cuenta($notReady, 'actividad próxima a iniciar', 'actividades próximas a iniciar');
        } elseif ($notReady > 0) {
            $status = $this->cuenta($notReady, 'paquete PDC no está listo', 'paquetes PDC no están listos');
        } else {
            $status = 'Todos los paquetes PDC están listos';
        }

        if ($notReady > 0) {
            $cause = 'La causa principal son los paquetes con fechas de insumos posteriores al inicio requerido';
            $impact = 'Las actividades asociadas pueden pasar a CNP o incumplir compromisos';
            $action = $role === 'R'
                ? 'Escala a compras los paquetes que no están listos'
                : 'Revisar los paquetes PDC en el comité de compras';
            $confidence = 'Media: verificar fechas reales de PDC';
        } else {
            $cause = 'No se detectaron bloqueos de compras';
            $impact = 'No hay impacto previsto sobre el plan';
            $action = $role === 'R'
                ? 'Mantén el seguimiento de los paquetes de compras'
                : 'Mantener el seguimiento en el comité de compras';
            $confidence = 'Alta';
        }

        return $this->brief($status, $cause, $impact, $action, $confidence);
    }

    private function briefCIC(array $data, string $role): array
    {
        $atRisk = count(array_filter($data, fn($r) => ($r['alert_contractor_future_risk'] ?? 0) == 1));

        if ($atRisk > 0) {
            $status = $this->cuenta($atRisk, 'contratista está en alerta', 'contratistas están en alerta') . ' de riesgo futuro';
            $cause = 'La causa principal es la calificación integral acumulada por debajo del umbral de 50';
            $impact = 'Hay alto riesgo de repetir incumplimientos en las próximas asignaciones';
            $action = $role === 'R'
                ? 'Revisa los contratistas en alerta antes de asignar nuevos compromisos'
                : 'Generar alerta para el comité de contratistas';
        } else {
            $status = 'Todos los contratistas tienen un desempeño aceptable';
            $cause = 'No hay alertas de contratistas activas';
            $impact = 'No hay riesgo previsto por desempeño de contratistas';
            $action = $role === 'R'
                ? 'Mantén el seguimiento del desempeño de contratistas'
                : 'Mantener el seguimiento en el comité de contratistas';
        }

        return $this->brief($status, $cause, $impact, $action, $atRisk > 0 ? 'Media-alta' : 'Alta');
    }

    private function briefCIP(array $data, string $role): array
    {
        $atRisk = count(array_filter($data, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1));

        if ($atRisk > 0) {
            $status = $this->cuenta($atRisk, 'responsable tiene alerta', 'responsables tienen alerta') . ' de cumplimiento';
            $cause = 'La causa principal es el PAC bajo o los compromisos críticos incumplidos de esos responsables';
            $impact = 'Hay riesgo de saturación y de baja predictibilidad semanal';
            $action = $role === 'R'
                ? 'Redistribuye la carga de los responsables en alerta'
                : 'Revisar la distribución de la carga crítica';
        } else {
            $status = 'Todos los responsables están cumpliendo';
            $cause = 'No hay alertas de cumplimiento activas';
            $impact = 'No hay riesgo previsto por carga de responsables';
            $action = $role === 'R'
                ? 'Mantén el seguimiento de la carga por responsable'
                : 'Mantener el seguimiento de la distribución de carga';
        }

        return $this->brief($status, $cause, $impact, $action, 'Media-alta');
    }

    private function briefCurvaS(array $data, string $role): array
    {
        $s = $data[0] ?? [];
        $real = round((float)($s['pct_avance_real'] ?? 0) * 100, 1);
        $teorico = round((float)($s['pct_avance_teorico'] ?? 0) * 100, 1);
        $desv = round((float)($s['pct_desviacion'] ?? 0) * 100, 1);
        $crit = (int)($s['critical_late'] ?? 0);
        // El signo va en la palabra ("por debajo"), no en el número.
        $brecha = abs($desv);

        if ($desv < -5 && $crit > 0) {
            $status = "La obra está {$brecha} puntos por debajo de la curva teórica, con "
                . $this->cuenta($crit, 'actividad crítica atrasada', 'actividades críticas atrasadas');
            $cause = 'La causa principal son las actividades críticas, que concentran la mayor parte de la brecha';
            $impact = 'Si el ritmo actual se mantiene, la fecha probable de cierre se desplaza';
            $action = $role === 'R'
                ? 'Ejecuta un plan de recuperación sobre las actividades de mayor duración crítica'
                : ($crit === 1
                    ? 'Aprobar un plan de recuperación para la actividad crítica atrasada'
                    : "Aprobar un plan de recuperación para las {$crit} actividades críticas atrasadas");
        } elseif ($desv < -2) {
            $status = "La obra está {$brecha} puntos por debajo de la curva (avance real {$real}% vs. teórico {$teorico}%)";
            $cause = 'La desviación es leve, posiblemente por retrasos puntuales';
            $impact = 'Es recuperable si se acelera el ritmo en las próximas semanas';
            $action = $role === 'R' ? 'Revisa las actividades con mayor desviación' : 'Monitorear la tendencia de la curva';
        } else {
            $status = "La obra va al ritmo del plan (avance real {$real}% vs. teórico {$teorico}%)";
            $cause = 'Todas las actividades están dentro del rango esperado';
            $impact = 'No hay impacto previsto sobre la fecha de cierre';
            $action = $role === 'R' ? 'Mantén el ritmo actual' : 'Sin acción requerida';
        }

        return $this->brief($status, $cause, $impact, $action, $crit > 0 ? 'Media: depende de la recuperación de las críticas' : 'Alta');
    }

    // -----------------------------------------------------------------
    // Fallback
    // -----------------------------------------------------------------

    private function emptyBrief(): array
    {
        return [
            'status'          => 'Sin datos suficientes para generar el brief.',
            'root_cause'      => 'No se encontraron registros para este proyecto y semana.',
            'impact'          => 'No se puede evaluar el impacto sin datos.',
            'priority_action' => 'Verificar que el proyecto y la semana tengan datos cargados.',
            'confidence'      => 'Baja: sin datos.',
        ];
    }
}
