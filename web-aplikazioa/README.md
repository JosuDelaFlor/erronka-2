# Web-aplikazioa segura

## Requisitos

Para ejecutar la aplicación con Docker:

- Docker Engine o Docker Desktop.
- Docker Compose v2 (`docker compose`).
- Git.

Para ejecutar las pruebas unitarias en local:

- PHP CLI 8.2 o superior.
- Composer 2.
- Extensiones PHP `curl`, `dom`, `mbstring` y `xml`.

En Debian/Parrot, instala las herramientas de desarrollo con:

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-plugin composer \
    php-cli php-curl php-mbstring php-xml
```

Si utilizas Docker Desktop y tu shell tiene configurado un socket de Podman
inexistente, limpia la variable antes de usar Docker:

```bash
unset DOCKER_HOST
docker context use desktop-linux
```

## Instalación y ejecución con Docker

Ejecuta los comandos desde la carpeta `web-aplikazioa`:

```bash
cd /home/josu/Desktop/Repos/erronka-2/web-aplikazioa
```

1. Crea el archivo local de variables de entorno:

   ```bash
   cp .env.example .env
   ```

2. Edita `.env` y establece contraseñas largas y diferentes:

   ```env
   MYSQL_DATABASE=BDweb
   MYSQL_USER=webapp
   MYSQL_PASSWORD=pon-una-contraseña-larga
   MYSQL_ROOT_PASSWORD=pon-otra-contraseña-larga
   ```

   No subas `.env` a Git.

3. Valida la configuración:

   ```bash
   docker compose --env-file .env config
   ```

4. Construye las imágenes e inicia los contenedores:

   ```bash
   docker compose --env-file .env up -d --build
   ```

   Con Docker Desktop y `DOCKER_HOST` configurado, usa:

   ```bash
   env -u DOCKER_HOST docker --context desktop-linux compose \
       --env-file .env up -d --build
   ```

5. Comprueba el estado:

   ```bash
   docker compose --env-file .env ps
   ```

   El servicio `db` debe aparecer como `healthy` y `app` como `Up`.

6. Abre la aplicación:

   <http://localhost:8090>

La aplicación PHP y MySQL se ejecutan en contenedores separados. MySQL no publica
ningún puerto en el host; solo es accesible desde la red privada de Compose. El
puerto HTTP `8090` se publica para el acceso local. En producción debe protegerse
mediante HTTPS y un firewall.

El usuario inicial de demostración es `admin@bdweb` con la contraseña temporal
`password`. Cámbiala inmediatamente y no uses esta cuenta en producción.

### Logs y parada

```bash
docker compose --env-file .env logs -f app
docker compose --env-file .env logs -f db
docker compose --env-file .env down
```

`docker compose down` detiene los servicios sin borrar los datos. Para reinicializar
la base de datos de desarrollo, eliminando el volumen persistente:

```bash
docker compose --env-file .env down -v
docker compose --env-file .env up -d --build
```

No uses `down -v` si necesitas conservar los datos.

## Estructura

- `index.php`: punto de entrada HTTP.
- `config/`: configuración basada en variables de entorno.
- `src/Core/`: seguridad transversal y utilidades comunes.
- `src/Infrastructure/`: conexión con servicios externos.
- `src/Modules/Auth/`: registro y login.
- `src/Modules/Account/`: gestión de cuentas.
- `src/Modules/Catalog/`: productos, búsqueda y administración del catálogo.
- `database/`: SQL de inicialización de MySQL.
- `docker/`: configuración de Apache.
- `images/`: recursos estáticos del catálogo.
- `uploads/`: almacenamiento de ficheros subidos.

## Reorganización arquitectónica

La estructura anterior concentraba la configuración y todos los módulos en la raíz:

```text
conf.php
includes/
scripts/
apache.conf
```

Se sustituyó por una organización por responsabilidades:

```text
config/config.php                 # Configuración de entorno
src/Core/security.php             # Seguridad transversal
src/Infrastructure/database.php  # Acceso a MySQL
src/Modules/Auth/                 # Login y registro
src/Modules/Account/              # Cuenta y usuarios
src/Modules/Catalog/              # Productos y catálogo
database/init.sql                 # Inicialización de la base de datos
docker/apache.conf                # Configuración de Apache
```

### Mapa de movimientos

| Ubicación anterior | Ubicación actual | Motivo |
|---|---|---|
| `conf.php` | `config/config.php` | Separar configuración de la lógica de aplicación. |
| `includes/security.php` | `src/Core/security.php` | Centralizar políticas transversales de seguridad. |
| `includes/mysql.php` | `src/Infrastructure/database.php` | Aislar la dependencia de MySQL. |
| `includes/login.php`, `register.php` | `src/Modules/Auth/` | Agrupar las funciones de autenticación. |
| `includes/account.php` | `src/Modules/Account/` | Separar la gestión de cuentas. |
| `includes/main.php`, `description.php`, `updel.php` | `src/Modules/Catalog/` | Agrupar las operaciones del catálogo. |
| `scripts/db.sql` | `database/init.sql` | Identificar claramente los recursos de base de datos. |
| `apache.conf` | `docker/apache.conf` | Separar la infraestructura de despliegue. |

`index.php` se mantiene como front controller compatible con el despliegue actual.
Sus inclusiones se han convertido en rutas absolutas basadas en `__DIR__`, evitando
que los movimientos dependan del directorio de trabajo desde el que se inicia PHP.

### Principios aplicados

- **Responsabilidad única:** cada carpeta representa una responsabilidad técnica o funcional.
- **Modularidad:** autenticación, cuentas y catálogo pueden evolucionar de forma independiente.
- **Bajo acoplamiento:** la conexión a MySQL está aislada en `Infrastructure`.
- **Extensibilidad:** se pueden añadir módulos nuevos sin convertir `index.php` en un archivo monolítico.
- **Mantenibilidad:** la configuración, el SQL y Docker tienen ubicaciones explícitas.

La reorganización no cambia las URL públicas ni los nombres de las acciones (`login`,
`register`, `account`, `description` y `updel`).

## Medidas de seguridad implementadas

- Consultas preparadas en login, búsquedas, gestión de usuarios y productos.
- `password_hash()`/`password_verify()` en lugar de MD5.
- Regeneración del identificador de sesión al autenticarse.
- Cookies `HttpOnly`, `SameSite=Lax` y `Secure` cuando se usa HTTPS.
- Tokens CSRF en todas las operaciones que modifican datos.
- Escape contextual con `htmlspecialchars`.
- Uploads con MIME real, tamaño limitado, nombre aleatorio y bloqueo de ejecución PHP.
- Usuario MySQL dedicado y red interna sin puerto 3306 publicado.
- Imagen PHP reproducible con extensión `mysqli` instalada durante el build.

## Producción

Usa HTTPS delante de Apache, un gestor de secretos, elimina el usuario inicial, cambia las contraseñas y no expongas el puerto de MySQL. El directorio `uploads` debe almacenarse en un volumen con copias de seguridad y permisos mínimos.

## Pruebas y CI/CD

Las pruebas unitarias están en `tests/` y utilizan PHPUnit. Ejecuta todos los comandos
desde `web-aplikazioa`, no desde `web-aplikazioa/tests`:

```bash
cd /home/josu/Desktop/Repos/erronka-2/web-aplikazioa
composer install
composer test
```

`composer install` instala PHPUnit en `vendor/` y genera `composer.lock`. No ejecutes
Composer como `root`; utiliza tu usuario normal para evitar problemas de permisos.
Si ya lo ejecutaste como `root`, corrige la propiedad:

```bash
sudo chown -R "$USER":"$USER" vendor composer.lock
```

Para ejecutar una prueba concreta:

```bash
vendor/bin/phpunit --filter SecurityTest
```

La suite actual comprueba:

- Escape de HTML para prevenir XSS.
- Generación de tokens CSRF.
- Reutilización correcta del token CSRF en una sesión.

El workflow `.github/workflows/security.yml` se ejecuta en cada `push` y `pull_request` y realiza:

- Instalación reproducible de las dependencias de desarrollo.
- Comprobación de sintaxis de todos los PHP.
- Pruebas unitarias PHPUnit.
- Comprobaciones de regresión de seguridad.
- Análisis SAST mediante Semgrep.
