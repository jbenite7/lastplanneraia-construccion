# GEMINI.md - Constitución de IA para el Proyecto

Este documento establece las reglas y configuraciones específicas para que los asistentes de IA operen de forma segura y eficiente en este entorno.

## 🐳 Entorno de Ejecución (Docker)

El proyecto se ejecuta exclusivamente sobre Docker. No se debe utilizar MAMP, XAMPP ni servidores locales nativos.

- **Servicios Principales**:
  - `app`: PHP 8.3 + Apache. Referencia para comandos: `docker-compose exec app ...`
  - `db`: MySQL 8.0. Host interno: `db`. Puerto externo: `3307`.
  - `adminer`: Gestión de DB. Puerto externo: `8082`.

- **Comandos Frecuentes**:
  - Iniciar entorno: `docker-compose up -d`
  - Apagar entorno: `docker-compose down`
  - Ejecutar PHP CLI: `docker-compose exec app php [archivo]`
  - Instalar dependencias: `docker-compose exec app composer install`

- **Base de Datos**:
  - **Host**: `db` (desde la app), `127.0.0.1` (desde el host, puerto 3307).
  - **Credenciales**: Ver archivo `.env`.

## 🏗️ Arquitectura Híbrida

El proyecto combina código legado con una nueva arquitectura moderna. Es crucial identificar dónde se está trabajando.

### 1. Legacy (`/src/Legacy/`)

- **Acceso**: Antiguos endpoints redireccionados a través del Front Controller.
- **Estructura**: Archivos planos que sobrevivieron a la purga de la carpeta original `/construccion/`.
- **Regla**: **Prohibido desarrollar nuevas características aquí.** Solo mantenimiento temporal hasta refactor total al `src/Controllers/` y `LpsService`.

### 2. Moderno (`/src/`, `/public/`, `/views/`)

- **Acceso**: Front Controller en `/public/index.php`.
- **Estructura**: MVC, Router, Dependency Injection (básico).
- **Componentes**:
  - `src/Controllers`: Orquestación de peticiones.
  - `src/Services`: Lógica de negocio compleja.
  - `src/Core`: Router, Database, Helpers.
  - `views/`: Plantillas (PHP nativo o motor de templates si se implementa).

## 🤖 Workflows de Antigravity (Comandos de IA)

Los comandos de operación del agente ahora están definidos como workflows formales en `.agents/workflows/`:

| Comando     | Workflow  | Acción de la IA                                                       |
| :---------- | :-------- | :-------------------------------------------------------------------- |
| **`/plan`** | `plan.md` | Inicia análisis en modo **PLANNING** y crea `implementation_plan.md`. |
| **`/run`**  | `run.md`  | Ejecuta el plan aprobado en modo **EXECUTION**.                       |
| **`/fast`** | `fast.md` | Ejecución rápida para tareas pequeñas (Modo **FAST**).                |
| **`/ask`**  | `ask.md`  | Responde dudas sobre el código sin editar (Modo **CONSULTA**).        |
| **`/fix`**  | `fix.md`  | Diagnóstico y reparación de errores (Modo **DEBUG**).                 |

## 📜 Reglas de Oro

1. **Mobile First**: Todo desarrollo debe pensarse y probarse primero para dispositivos móviles.
2. **Ediciones Atómicas**: **Nunca** generar bloques de código de más de 20 líneas en una sola edición. Dividir en pasos lógicos.
3. **Conexiones DB**: Siempre utilizar la clase `Database.php` (Singleton) ubicada en `construccion/src/Database.php` (o su alias en `src/Core/`).
4. **Normalización de Cargos**: Toda lógica relacionada con cargos debe pasar por `Admin\Core\RoleManager::cleanCargo()`.
5. **Scripts Críticos**: Antes de ejecutar scripts masivos, mostrar resumen y pedir confirmación explícita.
6. **Control de Versiones**: NO hacer git push sin aprobación explícita.
7. **Prohibición de Herramientas**: **NO USES EL BROWSER AGENT** bajo ninguna circunstancia. Las pruebas deben ser manuales o mediante scripts de servidor.
8. **Terminología**: Consultar siempre el [GLOSARIO.md](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/GLOSARIO.md) para asegurar el uso correcto de términos técnicos y de negocio (LPS/Lean).
9. **Protocolo Sniper**: Durante la ejecución, se prohíben refactorizaciones "de cortesía". El agente se limitará estrictamente a los cambios aprobados en el plan.
10. **Kill Switch**: El agente tiene un límite de 5 intentos consecutivos para corregir errores de validación antes de abortar la tarea.
11. **Validación Unificada**: Las pruebas y validaciones de sintaxis se deben realizar en un bloque unificado al final de todos los cambios de archivos.
12. **Auto* Functions Branding**: Toda función "Auto*..." (auto-generate, auto-assign, auto-associate, auto-program, etc.) DEBE incluir:
    - **Ícono**: `<i class="fas fa-magic"></i>` (wizard) consistente en todos los botones/acciones Auto*.
    - **Color**: Validado contra el manual de marca AIA (`brand-manual` skill). Usar variables CSS del sistema de tokens (`--aia-*`) según la línea temática del módulo. No usar clases Bootstrap genéricas (`btn-info`, `btn-warning`) sin sobreescribir con colores de marca.

## 📄 Documentación de Referencia

- [GLOSARIO.md](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/GLOSARIO.md): Diccionario de 100 términos esenciales del proyecto.
- [ROADMAP.md](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/ROADMAP.md): Seguimiento de hitos técnicos y tareas.
- [docs/ROUTES.md](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/docs/ROUTES.md): Direccionamiento del MVC, APIs e instrucciones del Front Controller.

## 🚀 Comandos Rápidos de Verificación

- **Versión PHP**: `docker-compose exec app php -v`
- **Conexión DB**: `docker-compose exec app php -r "require 'construccion/src/Database.php'; echo Database::getInstance() ? 'OK' : 'Error';"`

## 🌐 URLs del Entorno Docker

| Servicio          | URL                     | Descripción                     |
| :---------------- | :---------------------- | :------------------------------ |
| **App Principal** | `http://localhost:8081` | PHP + Apache (Front Controller) |
| **Adminer**       | `http://localhost:8082` | Gestión visual de la BD         |
| **MySQL**         | `127.0.0.1:3307`        | Conexión directa (no es web)    |

## 🧪 Credenciales de Prueba para Browser Testing

Cuando se ejecuten pruebas con el browser subagent en el entorno de desarrollo:

1. **URL de entrada:** `http://localhost:8081`
2. **Proyecto:** Siempre seleccionar **"Prueba"**
3. **Credenciales de Login:**
   - **Administrador Sistema:** `jbenitez` / `kkdCPM15`
   - **Usuarios de Prueba (RBAC):** (Clave para todos: `aia2026`)
     - Administrador (`A`): `test.A`
     - Director (`D`): `test.D`
     - Residente (`R`): `test.R`
     - Seguridad SST (`S`): `test.S`
     - Ambiental (`G`): `test.G`
     - SST + Ambiental (`SG`): `test.SG`
     - Oficina Técnica / Compras (`OT`): `test.OT`
     - Profesional DCV (`DCV`): `test.DCV`
     - Visualizador (`V`): `test.V`
     - Subcontratista (`C`): `test.C`
4. **Credenciales de Base de Datos:**
   - **Host:** `127.0.0.1` | **Puerto:** `3307` (desde host) / `3306` (interno Docker)
   - **Base de datos:** `lastplanneraia_dev`
   - **Usuario:** `root` | **Contraseña:** `Jbe#1106z`
5. **Nota:** El navegador puede tener las credenciales prefilled. Si están disponibles, usarlas directamente.
