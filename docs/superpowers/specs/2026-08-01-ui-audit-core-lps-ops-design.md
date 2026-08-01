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
