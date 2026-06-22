/**
 * Módulo de Registro de Decisiones de Mapeo
 * Fire-and-forget logger for decision audit trail
 * Vanilla JS — no dependencies
 */

window.DecisionLogger = (function() {

    // ── Private helpers ──────────────────────────────────────────────

    var ACCENT_MAP = {
        'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u',
        'Á': 'A', 'É': 'E', 'Í': 'I', 'Ó': 'O', 'Ú': 'U',
        'ñ': 'n', 'Ñ': 'N', 'ü': 'u', 'Ü': 'U'
    };

    function stripTags(str) {
        if (!str) return '';
        return String(str).replace(/<[^>]*>/g, '');
    }

    function removeAccents(str) {
        if (!str) return '';
        return String(str).replace(/[áéíóúñÁÉÍÓÚÑüÜ]/g, function(ch) {
            return ACCENT_MAP[ch] || ch;
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        var s = String(str);
        return s
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function tokenize(str) {
        if (!str) return [];
        var cleaned = stripTags(str);
        cleaned = removeAccents(cleaned);
        cleaned = cleaned.toLowerCase();
        cleaned = cleaned.replace(/[^\w\s]/g, ' ');
        var parts = cleaned.split(/\s+/);
        var tokens = [];
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].length > 0) {
                tokens.push(parts[i]);
            }
        }
        return tokens;
    }

    function safeValue(el, fallback) {
        if (!el) return fallback;
        return el.value || fallback;
    }

    // ── Core payload builder ─────────────────────────────────────────

    function buildPayload(actividad, sugerencia, decisionUsuario) {
        actividad = actividad || {};
        sugerencia = sugerencia || {};
        decisionUsuario = decisionUsuario || {};

        var proyectoId = safeValue(document.getElementById('baseDatos'), '');
        var semanaObjetivo = safeValue(document.getElementById('semanaObjetivoActualizacion'), 0);
        var usuarioId = safeValue(document.getElementById('permiso_canonico'), '');

        var nombre = stripTags(actividad.nombre || '');
        var tokens = tokenize(actividad.nombre || '');

        var params = new URLSearchParams();
        params.append('proyecto_id', proyectoId);
        params.append('semana_objetivo', semanaObjetivo);
        params.append('actividad_consecutivo', nombre + '-' + (actividad.posicion_pg || ''));
        params.append('actividad_nombre', nombre);
        params.append('actividad_tokens', JSON.stringify(tokens));
        params.append('actividad_posicion_pg', actividad.posicion_pg || '');
        params.append('actividad_vecinos', JSON.stringify(actividad.vecinos || []));
        params.append('actividad_capitulo', actividad.capitulo || '');
        params.append('engine_usado', sugerencia.engine || 'rule_engine');
        params.append('proceso_sugerido', sugerencia.proceso || '');
        params.append('confianza', sugerencia.confianza || 0);
        params.append('regla_aplicada', sugerencia.regla || '');
        params.append('candidatos_alternativos', JSON.stringify(sugerencia.candidatos || []));
        params.append('explicacion', sugerencia.explicacion || '');
        params.append('decision_usuario', decisionUsuario.accion || '');
        params.append('proceso_final', decisionUsuario.proceso_elegido || '');
        params.append('proceso_final_id', decisionUsuario.proceso_elegido_id || '');

        return params;
    }

    // ── Public interface ─────────────────────────────────────────────

    function log(decision) {
        var params = buildPayload(
            decision.actividad,
            decision.sugerencia,
            decision.decisionUsuario
        );

        fetch('/api/general/decision-log', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        }).then(function(r) {
            if (!r.ok) console.error('[DecisionLogger] POST failed:', r.status);
        }).catch(function(err) {
            console.error('[DecisionLogger] Network error:', err.message);
        });
    }

    return {
        log: log,
        buildPayload: buildPayload
    };

})();
