/**
 * Chip de estado de guardado, compartido por las cuatro rejillas de la cascada.
 *
 * Antes del 2026-08-10 este comportamiento vivia solo dentro de
 * `programa_actualizar/hot_actualizar.js`: PG, PI y PS senalaban unicamente al
 * terminar, asi que entre la tecla y el badge habia una ida y vuelta de red sin
 * ningun acuse de recibo — muy por encima de los ~0,1 s que la manipulacion
 * directa admite. Se extrae en vez de copiarse: cuatro copias de estas 30 lineas
 * serian cuatro sitios donde arreglar el proximo defecto.
 *
 * La clase de ocultamiento y la etiqueta son parametros a proposito: PI usa
 * `pi-status-badge-hidden` y PGA dice «Auto-Guardado», no «Guardado».
 *
 * @param {{ selector?: string, etiquetaGuardado?: string, claseOculta?: string }} [opciones]
 * @returns {{ pendiente: (n: number) => void, guardado: () => void, error: (mensaje?: string) => void }}
 */
export function crearSaveStatus({
  selector = '#save-status',
  etiquetaGuardado = 'Guardado',
  claseOculta = 'badge-badge-hidden',
} = {}) {
  const nodo = () => document.querySelector(selector);

  const pintar = (texto, severidad) => {
    const el = nodo();
    if (!el) return;
    el.classList.remove(claseOculta, 'aia-chip--success', 'aia-chip--warning', 'aia-chip--danger');
    if (severidad) el.classList.add(`aia-chip--${severidad}`);
    el.textContent = texto;
    el.hidden = false;
  };

  return {
    // n = cuantas filas hay en cola. El contador importa: guardar tres filas
    // seguidas debe leerse como una sola operacion con tres pendientes, no
    // como tres parpadeos.
    pendiente(n) {
      pintar(`Guardando... (${n})`, 'warning');
    },
    guardado() {
      pintar(etiquetaGuardado, 'success');
    },
    error(mensaje) {
      pintar(mensaje || 'No se pudo guardar', 'danger');
    },
  };
}
