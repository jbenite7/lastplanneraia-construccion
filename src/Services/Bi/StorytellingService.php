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
    // Per-report brief composers
    // -----------------------------------------------------------------

    private function briefOverview(array $data, string $role): array
    {
        $s = $data[0] ?? [];
        $toDo   = (int)($s['activities_to_do_count'] ?? 0);
        $canDo  = (int)($s['activities_can_do_count'] ?? 0);
        $willDo = (int)($s['activities_will_do_count'] ?? 0);
        $blocked = (int)($s['hard_restriction_blocked_count'] ?? 0);
        $atRisk  = (int)($s['weekly_commitments_at_risk_count'] ?? 0);

        $level = $blocked > 10 || $atRisk > 5 ? 'alto' : ($blocked > 3 ? 'medio' : 'bajo');
        $cause = $blocked > 0
            ? "{$blocked} actividades bloqueadas por restricciones duras"
            : "sin bloqueos críticos detectados";
        $impact = $atRisk > 0
            ? "si no se liberan, {$atRisk} compromisos están en riesgo de incumplimiento"
            : "el plan semanal tiene viabilidad aceptable";

        $action = $role === 'R'
            ? "Revisa las {$blocked} actividades bloqueadas antes del cierre de compromisos"
            : "Priorizar la liberación de restricciones duras en las próximas 48 horas";

        return [
            'status'          => "La obra está en nivel {$level}.",
            'root_cause'      => "El principal driver es {$cause}.",
            'impact'          => ucfirst($impact) . '.',
            'priority_action' => "{$action}.",
            'confidence'      => 'Media-alta: los datos de restricciones están actualizados.',
        ];
    }

    private function briefPG(array $data, string $role): array
    {
        $criticalLate = count(array_filter($data, fn($r) => ($r['is_critical_late'] ?? 0) == 1));
        $inWindow = count(array_filter($data, fn($r) => ($r['is_lookahead_window'] ?? 0) == 1));
        $notReady = count(array_filter($data, fn($r) => ($r['hard_restrictions_ready'] ?? 0) == 0 && ($r['is_lookahead_window'] ?? 0) == 1));

        if ($criticalLate > 0) {
            $status = "La obra tiene {$criticalLate} actividades críticas atrasadas";
            $cause = "actividades en ruta crítica con fecha vencida";
            $impact = "el forecast de cierre puede desplazarse si no se recuperan";
            $action = $role === 'R'
                ? "Revisa y recupera las actividades críticas atrasadas esta semana"
                : "Asignar recursos a las {$criticalLate} actividades críticas atrasadas";
        } elseif ($notReady > 0) {
            $status = "{$inWindow} actividades deben moverse en las próximas 6 semanas pero {$notReady} no están listas";
            $cause = "restricciones duras incompletas en actividades próximas";
            $impact = "pueden entrar a Programación Semanal sin capacidad real de cumplimiento";
            $action = $role === 'R'
                ? "No programes todavía las actividades con restricciones incompletas"
                : "Escalar la liberación de restricciones antes del próximo ciclo";
        } else {
            $status = "El programa general está al día, sin bloqueos críticos detectados";
            $cause = "todas las actividades en ventana tienen restricciones listas";
            $impact = "el plan semanal puede construirse con confianza";
            $action = $role === 'R'
                ? "Continúa con la programación semanal normal"
                : "Mantener el ritmo de liberación de restricciones";
        }

        return [
            'status'          => "{$status}.",
            'root_cause'      => "El principal driver es {$cause}.",
            'impact'          => ucfirst($impact) . '.',
            'priority_action' => "{$action}.",
            'confidence'      => 'Alta: fechas y restricciones completas.',
        ];
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
        $topType = array_key_first($byType) ?? 'restricciones';
        $topCount = $byType[$topType] ?? 0;

        $status = $hardNotReady > 0
            ? "{$hardNotReady} restricciones duras no están listas en la ventana de 6 semanas"
            : "Todas las restricciones duras están liberadas";

        $cause = $hardNotReady > 0
            ? "{$topType} explica la mayoría de bloqueos ({$topCount} actividades)"
            : "el Lookahead está completamente liberado";

        $impact = $hardNotReady > 5
            ? "si no se liberan antes de la semana de inicio, el PAC esperado se degrada"
            : "el impacto sobre el plan semanal es manejable";

        $action = $role === 'R'
            ? "Cierra {$topType} en las actividades bloqueadas antes del jueves"
            : "Escalar {$topType} al dueño de la restricción";

        return [
            'status'          => "{$status}.",
            'root_cause'      => "{$cause}.",
            'impact'          => ucfirst($impact) . '.',
            'priority_action' => "{$action}.",
            'confidence'      => $hardNotReady > 0 ? 'Media: depende de la actualización de restricciones' : 'Alta',
        ];
    }

    private function briefPS(array $data, string $role): array
    {
        $total = count($data);
        $atRisk = count(array_filter($data, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1));
        $pacOk = count(array_filter($data, fn($r) => ($r['PAC'] ?? 0) == 1));
        $pacPct = $total > 0 ? round($pacOk / $total * 100) : 0;

        $status = $pacPct < 60 ? "El plan semanal tiene riesgo alto de incumplimiento (PAC {$pacPct}%)"
            : ($pacPct < 80 ? "El plan semanal tiene riesgo medio (PAC {$pacPct}%)"
            : "El plan semanal está en buen estado (PAC {$pacPct}%)");

        $cause = $atRisk > 0
            ? "{$atRisk} compromisos tienen fulfillment_alert activo"
            : "todos los compromisos tienen datos completos";

        $impact = $pacPct < 60
            ? "la probabilidad de cumplir el plan semanal es baja"
            : "el plan semanal tiene viabilidad aceptable";

        $action = $role === 'R'
            ? "Revisa los {$atRisk} compromisos en riesgo antes de confirmar"
            : "Exigir plan de recuperación para los compromisos en riesgo";

        return [
            'status'          => ucfirst($status) . '.',
            'root_cause'      => ucfirst($cause) . '.',
            'impact'          => ucfirst($impact) . '.',
            'priority_action' => "{$action}.",
            'confidence'      => $atRisk > 0 ? 'Media-alta' : 'Alta',
        ];
    }

    private function briefPDC(array $data, string $role): array
    {
        $notReady = count(array_filter($data, fn($r) => ($r['listo_para_iniciar'] ?? 1) == 0));
        $status = $notReady > 2 ? "Compras puede bloquear {$notReady} actividades próximas a iniciar"
            : ($notReady > 0 ? "{$notReady} paquetes PDC no están listos" : "Todos los paquetes PDC están listos");
        $cause = $notReady > 0 ? "paquetes con fechas de insumos posteriores al inicio requerido" : "sin bloqueos detectados";
        $impact = $notReady > 0 ? "actividades asociadas pueden pasar a CNP o incumplir compromisos" : "sin impacto previsto";
        $action = $role === 'R' ? "Escala los paquetes no listos a compras" : "Revisar paquetes PDC en comité de compras";

        return [
            'status'          => "{$status}.",
            'root_cause'      => "El principal driver es {$cause}.",
            'impact'          => ucfirst($impact) . '.',
            'priority_action' => "{$action}.",
            'confidence'      => $notReady > 0 ? 'Media: verificar fechas reales de PDC' : 'Alta',
        ];
    }

    private function briefCIC(array $data, string $role): array
    {
        $atRisk = count(array_filter($data, fn($r) => ($r['alert_contractor_future_risk'] ?? 0) == 1));
        $status = $atRisk > 0 ? "{$atRisk} contratistas están en alerta de riesgo futuro" : "Todos los contratistas tienen desempeño aceptable";
        $cause = $atRisk > 0 ? "contratistas con Cal_Integral_Acum por debajo del umbral de 50" : "sin alertas activas";
        $impact = $atRisk > 0 ? "alto riesgo de repetir incumplimientos en próximas asignaciones" : "sin riesgo de contratistas";
        $action = $role === 'R' ? "Revisa los contratistas en alerta antes de asignar nuevos compromisos" : "Generar alerta para comité de contratistas";

        return [
            'status'          => "{$status}.",
            'root_cause'      => ucfirst($cause) . '.',
            'impact'          => ucfirst($impact) . '.',
            'priority_action' => "{$action}.",
            'confidence'      => $atRisk > 0 ? 'Media-alta' : 'Alta',
        ];
    }

    private function briefCIP(array $data, string $role): array
    {
        $atRisk = count(array_filter($data, fn($r) => ($r['fulfillment_alert'] ?? 0) == 1));
        $status = $atRisk > 0 ? "{$atRisk} responsables tienen alerta de cumplimiento" : "Todos los responsables están cumpliendo";
        $cause = $atRisk > 0 ? "responsables con PAC bajo o compromisos críticos incumplidos" : "sin alertas de cumplimiento";
        $impact = $atRisk > 0 ? "riesgo de saturación y baja predictividad semanal" : "sin riesgo de responsables";
        $action = $role === 'R' ? "Redistribuye carga de los responsables en alerta" : "Revisar distribución de carga crítica";

        return [
            'status'          => "{$status}.",
            'root_cause'      => ucfirst($cause) . '.',
            'impact'          => ucfirst($impact) . '.',
            'priority_action' => "{$action}.",
            'confidence'      => 'Media-alta',
        ];
    }

    private function briefCurvaS(array $data, string $role): array
    {
        $s = $data[0] ?? [];
        $real = round((float)($s['pct_avance_real'] ?? 0) * 100, 1);
        $teorico = round((float)($s['pct_avance_teorico'] ?? 0) * 100, 1);
        $desv = round((float)($s['pct_desviacion'] ?? 0) * 100, 1);
        $crit = (int)($s['critical_late'] ?? 0);

        if ($desv < -5 && $crit > 0) {
            $status = "La obra está {$desv} puntos desviada de la curva teórica con {$crit} críticas atrasadas";
            $cause = "actividades críticas concentran la mayor parte de la brecha";
            $impact = "si el ritmo actual se mantiene, la fecha probable de cierre se desplaza";
            $action = $role === 'R'
                ? "Ejecuta plan de recuperación sobre actividades de mayor duración crítica"
                : "Aprobar recovery plan para las {$crit} actividades críticas";
        } elseif ($desv < -2) {
            $status = "La obra está {$desv} puntos por debajo de la curva (avance real {$real}% vs teórico {$teorico}%)";
            $cause = "desviación leve, posiblemente por retrasos puntuales";
            $impact = "recuperable si se acelera el ritmo en las próximas semanas";
            $action = $role === 'R' ? "Revisa las actividades con mayor desviación" : "Monitorear tendencia de la curva";
        } else {
            $status = "La obra va al ritmo del plan (avance real {$real}% vs teórico {$teorico}%)";
            $cause = "todas las actividades están dentro del rango esperado";
            $impact = "sin impacto previsto sobre la fecha de cierre";
            $action = $role === 'R' ? "Mantén el ritmo actual" : "Sin acción requerida";
        }

        return [
            'status'          => "{$status}.",
            'root_cause'      => "El principal driver es {$cause}.",
            'impact'          => ucfirst($impact) . '.',
            'priority_action' => "{$action}.",
            'confidence'      => $crit > 0 ? 'Media: depende de recuperación de críticas' : 'Alta',
        ];
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
