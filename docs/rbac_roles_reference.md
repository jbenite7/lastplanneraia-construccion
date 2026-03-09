# Referencia de Roles y Permisos RBAC (Moderno)

Este documento establece la definición oficial de los roles canónicos del sistema Last Planner AIA tras la migración a la arquitectura RBAC moderna (Fase 1 completada en Base de Datos).

## Nuevos Roles Canónicos Definidos (La Meta Final)

El nuevo sistema abandona las letras legacy para adoptar una nomenclatura semántica y estandarizada por tipo de perfil profesional en la obra:

| Código  | Perfil Canónico               | Mapeo Legacy Principal | Descripción Funcional                                                                                                                                                                                      |
| :------ | :---------------------------- | :--------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **A**   | **Administrador de Sistema**  | Root (Exclusivo)       | Rol de superusuario reservado exclusivamente para la administración del sistema Core (ej. jbenitez). Acceso total sin restricciones comerciales o de proyecto.                                             |
| **D**   | **Director de Proyecto**      | `P` (Platform/Admin)   | **Reemplaza al `A` operativo y absorbe al `P` legacy.** Es el responsable y administrador principal de la obra. Tiene permisos completos de lectura, escritura y configuración sobre el proyecto asignado. |
| **R**   | **Residente de Obra**         | `R`                    | Profesional encargado de la ejecución directa técnica. Permisos de escritura enfocados en programación semanal, intermedia y restricciones.                                                                |
| **DCV** | **Profesional DCV**           | `DCV`                  | Perfil especializado de Dirección de Construcción Virtual. Permisos de escritura enfocados en programación semanal, intermedia y restricciones.                                                            |
| **OT**  | **Oficina Técnica**           | `OT`                   | Perfil administrativo de obra enfocado en planeamiento a largo plazo, compras (PDC) y control documental.                                                                                                  |
| **G**   | **Residente Ambiental**       | `G`                    | Especialista enfocado en gestión de restricciones ambientales y certificaciones P/A.                                                                                                                       |
| **S**   | **Residente SST**             | `S`                    | Especialista en Seguridad y Salud en el Trabajo. Gestiona restricciones tipo SST y documentación asociada.                                                                                                 |
| **SG**  | **Residente Socio-Ambiental** | `SG`                   | Perfil híbrido o específico para gestión social comunitaria y cruce con gestión ambiental.                                                                                                                 |
| **C**   | **Subcontratista**            | `C`                    | Entidad externa. Inicialmente (Fase 0 y 1) sin acciones `in-app` permitidas, perfil destinado exclusivamente para recepción de notificaciones y reportes automáticos.                                      |
| **V**   | **Visitante**                 | `U` (Usuario Legacy)   | **Reemplaza al `U` legacy.** Perfil estricto de **solo lectura**. Puede consultar programas y reportes, pero no puede modificar ninguna variable ni ejecutar acciones de negocio.                          |

---

## Equivalencias y Alias de Transición Segura (Fallback)

Durante la ventana de transición (Fase 4 - donde la UI aún envía letras legacy viejas), el `RbacService` en el backend aplica un mecanismo de _fallback_ para interceptar los códigos obsoletos y tratarlos como roles modernos, garantizando la estabilidad operativa:

1. **Si llega un alias legacy `P`:** El servicio intercepta la letra `P` enviada por el frontend y automáticamente le otorga el paquete de permisos granulares correspondiente al rol **`D` (Director)**.
2. **Si llega un alias legacy `U`:** El servicio intercepta la letra `U` enviada por el frontend y automáticamente le otorga el paquete de permisos correspondiente al rol **`V` (Visitante)**.

_Cualquier otro rol no mapeado que intente acceder sin estar registrado en `project_members` caerá al rol por defecto de seguridad (V - Solo Lectura)._
