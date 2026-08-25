---
capa: fuente
tipo: biblia
estado: vigente
fecha: 2026-03-09
areas: [rbac]
fuente: docs/rbac_cargos_roles_dictionary.md
resumen: Este documento centraliza la correspondencia estándar entre los cargos profesionales (títulos elegibles en la plataforma) y el permiso (Rol RBAC) que el…
---

# Diccionario de Cargos y Roles Sugeridos (RBAC)

Este documento centraliza la correspondencia estándar entre los cargos profesionales (títulos elegibles en la plataforma) y el permiso (Rol RBAC) que el sistema de Inteligencia (`role_intelligence`) sugerirá por defecto al crear o editar un usuario.

Esta lista es producto del proceso de unificación y depuración de la base de datos de usuarios (eliminación de ruido, correos electrónicos, pruebas y resolución de variaciones de género/gramática).

## 1. Dirección y Gerencia

| Cargo Unificado | Rol RBAC Sugerido | Código | Descripción del Rol |
| :--- | :---: | :---: | :--- |
| **Director de Proyecto** | Administrador / Director | `A` | Control total del proyecto y administración del sistema. |
| **Director de Obra** | Director Funcional | `D` | Control total operativo (reportes, planeación), sin permisos de administración de usuarios. |

## 2. Coordinación y Planeación

| Cargo Unificado | Rol RBAC Sugerido | Código | Descripción del Rol |
| :--- | :---: | :---: | :--- |
| **Coordinador de Obra** | Director Funcional | `D` | Nivel equivalente a Director de Obra en la toma de decisiones in-app. |
| **Coordinador de Diseños** | Director Funcional | `D` | Lectura y actualización de todos los módulos. |
| **Coordinador de Planeación** | Director Funcional | `D` | Enfoque en la planificación maestra, intermedia y semanal. |

## 3. Residentes y Ejecución Técnica

| Cargo Unificado | Rol RBAC Sugerido | Código | Descripción del Rol |
| :--- | :---: | :---: | :--- |
| **Residente de Obra** | Residente de Obra | `R` | Operación diaria, actualización del LPS y restricciones. |
| **Auxiliar de Ingeniería** | Residente de Obra | `R` | Soporte al Residente, mismos permisos operativos en módulos de producción. |
| **Residente de Programación** | Residente de Obra | `R` | Centrado en la ejecución y reporte del control de producción. |
| **Residente de Acabados** | Residente de Obra | `R` | Especialidad técnica en la fase de obra, reporta al LPS. |
| **Residente de Interventoría** | Visualizador | `V` | Aprobación externa o seguimiento, solo lectura por defecto. |

## 4. Área Técnica, Costos y Compras

| Cargo Unificado | Rol RBAC Sugerido | Código | Descripción del Rol |
| :--- | :---: | :---: | :--- |
| **Oficina Técnica / Compras** | Oficina Técnica | `OT` | Editor en Contratos y PDC. Lector en el resto. |
| **Profesional de Compras / Aprovisionamiento** | Oficina Técnica | `OT` | Gestión de contratos y provisiones. |
| **Residente de Control de Costos** | Oficina Técnica | `OT` | Equivalente operativo para control presupuestal. |

## 5. HSEQ (Salud, Seguridad, Ambiente y Calidad)

| Cargo Unificado | Rol RBAC Sugerido | Código | Descripción del Rol |
| :--- | :---: | :---: | :--- |
| **Profesional Ambiental** | Ambiental | `G` | Rol especialista, edita el módulo CIC ambiental. |
| **Profesional SST** | Seguridad (SST) | `S` | Rol especialista, edita el módulo CIC de seguridad. |
| **Profesional SST + Ambiental** | SST + Ambiental | `SG` | Rol híbrido, capacidades combinadas de `S` y `G`. |
| **Coordinador de Calidad** | Visualizador | `V` | Sin módulo activo directo, acceso de lectura trazable. |

## 6. Roles Especiales y Terceros

| Cargo Unificado | Rol RBAC Sugerido | Código | Descripción del Rol |
| :--- | :---: | :---: | :--- |
| **Profesional DCV** | Profesional DCV | `DCV` | Módulo de Desarrollo de Cadena de Valor. |
| **Profesional BIM** | Visualizador | `V` | Integración de modelos, solo lectura en flujos LPS. |
| **Profesional PI** | Visualizador | `V` | Lector general de analíticas de productividad. |
| **Subcontratista** | Subcontratista | `C` | Acceso restringido para contratistas (sin acciones en la fase actual). |
| **Invitado** | Visualizador | `V` | Acceso de visita o temporal para revisión. |

---
*Nota: Estos roles sugeridos actúan como valor por defecto en los formularios (basado en `role_intelligence`), pero el Administrador (`A`) tiene la libertad de cambiar el nivel asignado (Permiso) antes de guardar el usuario.*
