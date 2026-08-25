---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-01
areas: [lps]
fuente: docs/superpowers/specs/2026-08-01-ui-audit-core-lps-ops-design.md
resumen: Aplicar los estándares de la skill impeccable (audit, harden, polish) de manera sistemática a todo el frontend heredado (Legacy) de la aplicación, abarcando…
---

# Impeccable Audit & Refactor Design: Core LPS & Ops

## Objetivo
Aplicar los estándares de la skill `impeccable` (audit, harden, polish) de manera sistemática a todo el frontend heredado (Legacy) de la aplicación, abarcando las pantallas de Autenticación, Core LPS y Operaciones. El objetivo es alcanzar un Audit Health Score de 10/10 en coherencia visual con el Design System (`.aia-*`) y calidad HTML.

## Alcance
- **Incluido:** Auth (`login`, `password-forgot`, `password-reset`), Selector de Proyectos, Core LPS (`programa-general`, `programacion-intermedia`, `programacion-semanal` y submódulos), Operaciones (`profesionales`, `subcontratistas`, `indicadores`, `control-cambios`).
- **Excluido:** Módulo "Plan de Compras" (React), Panel de Administración (`/admin/` con AdminLTE) y cualquier otra superficie fuera del Core LPS y Ops.
- **Entorno Válido:** Desktop (≥ 1180 × 820 px) en Dark Mode exclusivamente.

## Arquitectura de la Solución (Refactorización Vertical)
Se opta por un enfoque de **Refactorización Vertical (por módulo/pantalla)** para minimizar riesgos. Cada pantalla se auditará, modificará y probará individualmente antes de avanzar a la siguiente.

### Reglas de Transformación Visual (Polish)
En cada superficie de trabajo, se ejecutarán los siguientes reemplazos de componentes heredados de Bootstrap hacia el Design System AIA:
- **Botones:** Reemplazar `.btn` y sus variantes semánticas por `.aia-btn` (`.aia-btn--primary`, `.aia-btn--secondary`, etc.).
- **Formularios:** Reemplazar `.form-control` por `.aia-input`.
- **Modales:** Transformar contenedores `.modal` clásicos a `.aia-modal`, asegurando el uso de `.aia-modal__eyebrow`, `.aia-modal__title` y eliminando `.modal-header` heredado si entra en conflicto.
- **Etiquetas/Badges:** Cambiar `.badge` por `.aia-chip`.

### Reglas de Endurecimiento (Harden)
- **Limpieza de DOM:** Eliminar atributos de ID duplicados (e.g. múltiples llamadas al mismo modal inyectado repetidamente).
- **Limpieza de CSS:** Eliminar todo rastro de estilos inline (`style="..."`).
- **Limpieza de Código Zombi:** Eliminar bloques de código HTML o PHP comentados que ya no cumplen función alguna.

## Estrategia de Verificación y Testing
Dado el alto grado de acoplamiento de las vistas legadas, la verificación se hará por cada pantalla:
1. **Verificación Estática:** Ejecución de `php -l <archivo>` para confirmar integridad sintáctica.
2. **Verificación en Runtime:** Uso de la *Puerta Dev Local* (`/dev/entrar?u=test.A&p=<proyecto>`) para renderizar el módulo modificado en navegador y comprobar que el layout y las integraciones JavaScript heredadas (como DataTables o Handsontable) no se han roto.

## Fases de Ejecución

1. **Fase 1: Auth & Core Navigation**
   - Vistas: Login, Recuperar Contraseña, Selector de Proyectos, Nav Sidebar.
2. **Fase 2: Programación Core (LPS)**
   - Vistas: Programa General y Programación Intermedia.
3. **Fase 3: Programación Semanal**
   - Vistas: Programación Semanal y sus submódulos inyectables (CNP, CNC, CIC).
4. **Fase 4: Operaciones**
   - Vistas: Profesionales, Subcontratistas, Indicadores y Control de Cambios.

## Cierre

**DEROGADA el 2026-08-24, en el mismo acto que su spec hermana**
[[docs/superpowers/specs/2026-07-31-ui-audit-and-repair-plan-design]]. Superada como vehículo, no
ejecutada.

El veredicto es del **2026-08-20** ([[docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria]]
§2): «los dos planes viejos quedan superados como vehículo». La evidencia completa —qué sustituye a
cada pieza, y las mediciones de `/indicadores` y CNP/CNC/CIC re-verificadas hoy sobre el árbol
actual— está escrita **una sola vez**, en el `## Cierre` de la spec hermana, para no duplicarla.

### Lo propio de esta spec

Las dos se solapaban casi por completo (mismo alcance, mismas superficies, un día de diferencia), y
esta añadía sobre todo el **método**: refactorización vertical pantalla por pantalla, con reglas de
sustitución Bootstrap → `aia-*` y de endurecimiento (limpieza de DOM, de estilos inline y de código
zombi).

Ese método **no se pierde al derogar la spec**: es lo que hace la skill `impeccable` (`audit`,
`harden`, `polish`), que sigue disponible y es la que se invocó el 2026-08-20 para emitir el
veredicto. Lo que se retira es el compromiso de aplicarlo *desde este documento*, sin dueño y con un
alcance —«Audit Health Score de 10/10» sobre todo el frontend legado— que DS-F0 sustituyó por un
censo con severidad declarada.

### El error de diagnóstico que las dos compartían

Ambas listaban `indicadores` como superficie a refactorizar (§Fase 4 aquí, §Fase 2 allá). Medido:
`/indicadores` es un shell `aia-*` con un iframe de Power BI dentro — **no hay tarjetas KPI en el
repo que migrar**. Dos planes prometieron el mismo trabajo inexistente durante 23 días, y ninguno lo
detectó porque ninguno llegó a ejecutarse.

Es un caso más de [[memoria/trampas/el-trabajo-hecho-no-vuelve-solo-al-documento]] en su variante
inversa: aquí el documento no arrastraba trabajo ya hecho, sino trabajo que **nunca tuvo dónde
hacerse**, y sobrevivió por lo mismo — nada confronta un plan con el estado real hasta que alguien
lo mide.
