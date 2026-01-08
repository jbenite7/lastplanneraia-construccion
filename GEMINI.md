# GEMINI.md - Constitución de IA para el Proyecto

Este documento establece las reglas y configuraciones específicas para que los asistentes de IA operen de forma segura y eficiente en este entorno.

## 🛠 Entorno de Ejecución (MAMP)

- **Binario de PHP:** Siempre usar `/Applications/MAMP/bin/php/php8.3.14/bin/php`.
- **Base de Datos:** MySQL corre a través de MAMP. Si hay errores de conexión, verificar que los servidores de MAMP estén encendidos.
- **Configuración:** El proyecto depende de un archivo `.env` en la raíz.

## 📜 Reglas de Oro

1. **Conexiones DB:** Siempre utilizar la clase `Database.php` (Singleton) ubicada en `construccion/src/Database.php` para asegurar consistencia y seguridad.
2. **Normalización de Cargos:** Toda lógica relacionada con cargos debe pasar por `Admin\Core\RoleManager::cleanCargo()`.
3. **Scripts Críticos:** Antes de ejecutar cualquier script que modifique datos masivamente, se debe mostrar un resumen y pedir confirmación.
4. **Logs:** Al ejecutar scripts desde la CLI, imprimir siempre los resultados con prefijos claros (`SUCCESS:`, `ERROR:`, `INFO:`).

## 🚀 Comandos Rápidos

- **Probar Conexión:** `/Applications/MAMP/bin/php/php8.3.14/bin/php -r "require 'construccion/conexion.php'; echo 'OK';" `
