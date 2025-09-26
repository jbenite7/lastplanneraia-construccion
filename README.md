# Last Planner AIA - Sistema de Gestión de Construcción

## 1. Descripción del Proyecto

Last Planner AIA es una aplicación web diseñada para la gestión y planificación de proyectos de construcción, implementando la metodología *Last Planner System (LPS)*. La plataforma permite a los equipos de construcción coordinar tareas, gestionar la programación semanal, identificar y eliminar restricciones, monitorear el avance del proyecto a través de indicadores clave (CIC, CNC, CNP) y generar reportes de productividad.

El objetivo central es proporcionar una herramienta digital que centralice la información, mejore la comunicación entre los involucrados y aumente la fiabilidad de la planificación en obras de construcción.

## 2. Stack Tecnológico

La aplicación está construida sobre un stack tradicional de PHP, sin el uso de un framework principal, optimizado para entornos de hosting compartido.

- **Backend:** PHP 7.x / 8.x (Vanilla)
- **Gestor de Dependencias:** Composer
- **Librerías PHP Clave:**
    - `phpoffice/phpspreadsheet`: Para la generación y manipulación de archivos Excel.
    - `vlucas/phpdotenv`: Para la gestión de variables de entorno.
- **Base de Datos:** MySQL / MariaDB (utilizando la extensión PDO para la conexión).
- **Frontend:** HTML, CSS, JavaScript (Vanilla).
- **Servidor Web:** Apache (compatible con SiteGround).

## 3. Variables de Entorno

La configuración de la aplicación se gestiona a través de un archivo `.env` en la raíz del proyecto. Este archivo es requerido para establecer la conexión con la base de datos y otras configuraciones sensibles.

Cree un archivo `.env` a partir de `.env.example` (si existiera) y complete las siguientes variables:

```dotenv
DB_HOST=your_database_host
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
```

## 4. Instalación y Uso

1.  **Clonar el repositorio:**
    ```bash
    git clone <url-del-repositorio>
    cd construccion
    ```

2.  **Instalar dependencias de PHP:**
    ```bash
    composer install
    ```

3.  **Configurar el entorno:**
    - Copie o renombre su archivo de configuración a `.env`.
    - Configure las variables de entorno, especialmente las credenciales de la base de datos.

4.  **Configurar el servidor web:**
    - Apunte la raíz de su servidor web (ej. Apache en SiteGround) al directorio `public_html/construccion`.
    - Asegúrese de que el módulo `mod_rewrite` esté habilitado (viene por defecto en SiteGround).

5.  **Acceder a la aplicación:**
    - Abra su navegador y navegue a la URL configurada. Será redirigido a la página de login.

## 5. Cómo Contribuir

Para contribuir al desarrollo del proyecto, por favor siga los siguientes pasos:

1.  **Cree un Fork** del repositorio.
2.  **Cree una nueva rama** para su feature o bugfix: `git checkout -b feature/nueva-funcionalidad` o `fix/bug-a-corregir`.
3.  **Realice sus cambios** y asegúrese de seguir las guías de estilo del proyecto.
4.  **Haga commit** de sus cambios con un mensaje descriptivo.
5.  **Envíe sus cambios** a su fork: `git push origin <nombre-de-su-rama>`.
6.  **Abra un Pull Request** hacia la rama `main` o `develop` del repositorio original.

## 6. Entorno de Desarrollo

Para asegurar una experiencia de desarrollo consistente y eficiente, recomendamos utilizar **Visual Studio Code** con las siguientes extensiones y configuraciones.

### Prerrequisitos

Asegúrate de tener instalados los siguientes programas en tu sistema:
- **PHP** (versión 7.4 o superior)
- **Composer** (gestor de dependencias para PHP)
- **Git** (para control de versiones)
- **Visual Studio Code** (editor de código)

### Extensiones Recomendadas de VS Code

Las siguientes extensiones están listadas en el archivo `.vscode/extensions.json` y VS Code te sugerirá instalarlas al abrir el proyecto.

---

#### Para Escribir Código PHP

1.  **Intelephense (`bmewburn.vscode-intelephense-client`)**
    - **¿Qué es?** Es tu **asistente inteligente para PHP**. Te proporciona autocompletado avanzado, análisis de código en tiempo real y navegación rápida.
    - **Analogía:** Es como un corrector que no solo marca errores, sino que te sugiere la siguiente palabra. Te ayuda a escribir código más rápido y con menos errores.

2.  **PHP Debug (`xdebug.php-debug`)**
    - **¿Qué es?** Es una **herramienta de detective** para encontrar errores (bugs) en tu código PHP.
    - **Analogía:** Te permite ejecutar tu código paso a paso, viendo qué está pasando con tus variables en cada momento, para que puedas encontrar el punto exacto donde ocurre un problema.

3.  **php-cs-fixer (`junstyle.php-cs-fixer`)**
    - **¿Qué es?** Es el **organizador automático** de tu código PHP.
    - **Analogía:** Es un robot que ordena tu código para que siga un estilo único y consistente (basado en el archivo `.php-cs-fixer.dist.php`), haciendo que sea más fácil de leer para todo el equipo.

---

#### Para la Base de Datos

4.  **SQLTools y Driver de MySQL (`mtxr.sqltools`, `mtxr.sqltools-driver-mysql`)**
    - **¿Qué es?** Es tu **ventana a la base de datos** directamente desde VS Code.
    - **Analogía:** En lugar de usar otra aplicación, te permite ver, consultar y modificar tu base de datos MySQL desde una pestaña dentro de tu editor.

---

#### Para el Frontend (JS, CSS, HTML)

5.  **Prettier (`esbenp.prettier-vscode`)**
    - **¿Qué es?** Es el **organizador automático** para tu código de Frontend.
    - **Analogía:** Al igual que `php-cs-fixer`, pero para JavaScript, HTML y CSS. Cada vez que guardas, "embellece" tu código para mantenerlo ordenado y consistente.

6.  **ESLint (`dbaeumer.vscode-eslint`)**
    - **¿Qué es?** Es un **entrenador de calidad** para tu código JavaScript.
    - **Analogía:** No solo se preocupa de que el código se vea bonito (como Prettier), sino que te avisa de posibles errores lógicos o malas prácticas que podrían causar problemas a futuro.

---

#### Para el Control de Versiones (Git)

7.  **GitLens (`eamodio.gitlens`)**
    - **¿Qué es?** Son **superpoderes para ver el historial** de tu código.
    - **Analogía:** Git es una máquina del tiempo para tu proyecto. GitLens te permite ver fácilmente quién escribió cada línea de código, cuándo y por qué, sin tener que salir del editor.

---

#### Utilidades Generales

8.  **DotENV (`mikestead.dotenv`)**
    - **¿Qué es?** Un **resaltador de texto para tus secretos**.
    - **Analogía:** Simplemente añade colores a los archivos `.env` para que sean más fáciles de leer y diferenciar las claves de los valores.

9.  **EditorConfig for VS Code (`editorconfig.editorconfig`)**
    - **¿Qué es?** Un **acuerdo de formato básico** para todo el equipo.
    - **Analogía:** Asegura que todos usen las mismas reglas básicas (como espacios vs. tabulaciones) para mantener la consistencia del código, sin importar la configuración personal del editor.