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

## 🛡️ Mejores Prácticas SQL

1. **Consultas Dinámicas:** Para consultas SQL largas o con lógica dinámica, utilizar siempre la sintaxis **HEREDOC** (`<<<SQL ... SQL`). Esto evita errores de escape de comillas simples/dobles y mejora la legibilidad.
2. **Cálculos de Fechas:** Al usar `DATE_SUB` o `DATE_ADD` con variables de la base de datos, envolver siempre el valor del intervalo en `IFNULL(valor, 0)` para prevenir errores fatales de MySQL si el campo está vacío.
3. **PDO Prepared Statements:** Verificar siempre que el número de placeholders (`?` o `:name`) coincida exactamente con el array de parámetros enviado a `execute()`.

## 🚀 Comandos Rápidos

- **Probar Conexión:** `/Applications/MAMP/bin/php/php8.3.14/bin/php -r "require 'construccion/conexion.php'; echo 'OK';" `
