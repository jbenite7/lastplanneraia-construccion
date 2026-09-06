/**
 * Primitivas puras del escalamiento SOS: texto del mensaje, rol superior derivado del nivel
 * actual y las URLs de los canales externos (WhatsApp/correo). Puerto 1:1 de `triggerEscalate()`
 * (`public/js/modules/lps_drawer.js:1174-1246`), sin DOM, `fetch`, `window.open` ni
 * `navigator.clipboard` — esos efectos quedan en `AccionesSos.tsx`, inyectados como funciones
 * (D-T02-01, brief Tarea 6 §Paso 3: "adapters externos son funciones inyectadas").
 *
 * Quirk preservado a propósito (puerto exacto, no se corrige aquí): el nivel siguiente se calcula
 * como `min(nivelActual + 1, 5)`, así que los niveles 3, 4 y 5 producen el mismo trigger `SOS-GER`
 * — "Gerente de Construcción" (nivel 4) y "Gerente General" (nivel 5) comparten las tres primeras
 * letras. El original tiene la misma ambigüedad (lps_drawer.js:1190/1210).
 */

export type NivelEscalamiento = 1 | 2 | 3 | 4 | 5;

/** Puerto de `rolesNombres` (lps_drawer.js:1190). */
const ROLES_POR_NIVEL: Readonly<Record<number, string>> = {
  1: 'Residente',
  2: 'Director',
  3: 'Coordinador de Integración',
  4: 'Gerente de Construcción',
  5: 'Gerente General',
};

/** Puerto de `siguienteNivel = Math.min(nivelActual + 1, 5)` (lps_drawer.js:1192). */
export function nivelSuperior(nivelActual: number): number {
  return Math.min(nivelActual + 1, 5);
}

/** Puerto de `rolSuperior` (lps_drawer.js:1193). */
export function rolSuperior(nivelActual: number): string {
  return ROLES_POR_NIVEL[nivelSuperior(nivelActual)] ?? 'Desconocido';
}

/**
 * `trigger` que espera POST /api/lps/crisis/register (T02-AC-109): `SOS-` + las tres primeras
 * letras del rol superior en mayúsculas. Puerto de
 * `` `SOS-${rolSuperior.substring(0, 3).toUpperCase()}` `` (lps_drawer.js:1210).
 */
export function triggerSos(nivelActual: number): string {
  return `SOS-${rolSuperior(nivelActual).substring(0, 3).toUpperCase()}`;
}

export interface DatosSos {
  consecutivo: number | string;
  actividad: string;
  subcontratista: string;
  restriccion: string;
  nivelActual: number;
}

/** Puerto exacto de la plantilla `text` (lps_drawer.js:1195), incluidos emojis y saltos de línea. */
export function construirTextoSos(datos: DatosSos): string {
  const rol = rolSuperior(datos.nivelActual);

  return (
    `🚨 [ALERTA SOS - CRISIS AIA] 🚨\n\n`
    + `Estimado superior en calidad de ${rol}, se notifica bloqueo crítico en la obra.\n`
    + `• Actividad: #${String(datos.consecutivo)} - ${datos.actividad}\n`
    + `• Subcontratista: ${datos.subcontratista}\n`
    + `• Restricción/Causa: ${datos.restriccion}\n\n`
    + `Se solicita intervención jerárquica urgente para liberar el frente y evitar retrasos `
    + `acumulados en la línea base teórica. - Last Planner AIA`
  );
}

/** Puerto de la URL de WhatsApp (lps_drawer.js:1234): espacios fuera, resto del número intacto. */
export function urlWhatsapp(telefono: string, texto: string): string {
  return `https://api.whatsapp.com/send?phone=${telefono.replace(/\s+/g, '')}&text=${encodeURIComponent(texto)}`;
}

/**
 * Puerto de la URL de `mailto:` (lps_drawer.js:1242). El asunto conserva el typo del original
 * ("Requeria" sin tilde, en vez de "Requerida"): es texto visible que un destinatario real ve hoy
 * en producción, así que corregirlo sería un cambio de producto fuera del alcance de un puerto.
 */
export function urlCorreo(correo: string, texto: string): string {
  const asunto = encodeURIComponent('[SOS CRISIS LPS] Intervención Jerárquica Requeria');

  return `mailto:${correo}?subject=${asunto}&body=${encodeURIComponent(texto)}`;
}
