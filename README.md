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
