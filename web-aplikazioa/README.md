# Web-aplikazioa segura

## Inicio local

1. Copia `.env.example` a `.env` y sustituye todos los valores por secretos largos y aleatorios.
2. Ejecuta:

   ```bash
   docker compose up -d --build
   ```

3. Abre <http://localhost:8081>.

La aplicación PHP y MySQL se ejecutan en contenedores separados. MySQL no publica ningún puerto en el host; solo es accesible desde la red privada de Compose. El puerto HTTP `8090` se publica para el acceso local; en producción debe protegerse mediante HTTPS y un firewall. El usuario inicial es `admin@bdweb` con la contraseña temporal `password`; debe cambiarse inmediatamente en un entorno real.

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

Para reinicializar la base de datos de desarrollo:

```bash
docker compose down -v
docker compose up -d --build
```

Este comando elimina los datos persistidos.

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
