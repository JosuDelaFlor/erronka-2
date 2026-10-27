# Auditoría de seguridad y remediación

## Alcance

Auditoría local de `web-aplikazioa`, incluyendo PHP, MySQL, sesiones, subida de archivos y Docker. Las pruebas deben ejecutarse únicamente contra `localhost`.

## Checklist

- [x] SQL Injection en autenticación, búsquedas, productos y usuarios: consultas preparadas.
- [x] XSS reflejado y almacenado: escape HTML con `e()` y límites de entrada.
- [x] Contraseñas con MD5: migradas a `password_hash()`/`password_verify()`.
- [x] Fijación y robo de sesión: regeneración de ID y cookies endurecidas.
- [x] CSRF en cambios de contraseña, usuarios y productos: tokens aleatorios.
- [x] Upload RCE: MIME real, extensión derivada del MIME, nombre aleatorio y almacenamiento no ejecutable.
- [x] Control de acceso: autorización centralizada con `require_login()` y `require_admin()`.
- [x] Exposición de MySQL: sin `ports`, red Docker interna y usuario no root.
- [x] Secretos: variables de entorno y `.env` excluido de Git.
- [x] Cabeceras HTTP defensivas: `nosniff`, `SAMEORIGIN` y política de referrer.
- [ ] HTTPS: debe terminarse en un reverse proxy gestionado por el despliegue.
- [ ] Rotación del secreto inicial: obligatoria antes de producción.

## Evidencias previas y verificación posterior

Las siguientes PoC son referencias para reproducir la evidencia previa en un laboratorio aislado. No deben ejecutarse contra sistemas ajenos.

| Vector | PoC previa | Verificación posterior |
|---|---|---|
| Login SQLi | En el usuario de login, `' OR 1=1 #`; antes devolvía una sesión válida. | La entrada se trata como texto y devuelve credenciales incorrectas. |
| Búsqueda SQLi | En `keyword`, usar una comilla y una condición SQL; antes alteraba la consulta. | La búsqueda usa dos parámetros preparados y no modifica SQL. |
| XSS | `?action=search&keyword=<script>alert(document.domain)</script>`. | Se muestra texto escapado, nunca se ejecuta JavaScript. |
| Upload RCE | Subir un archivo `.php` en el formulario de registro o productos. | Solo JPEG/PNG/GIF válidos; el nombre es aleatorio y Apache bloquea PHP en `uploads`. |
| CSRF | Enviar una petición POST externa al cambio de contraseña. | Falta el token y la respuesta es HTTP 403. |
| MD5 | Comparar el hash inicial `5f4...` con `md5('password')`. | El dump contiene un hash bcrypt `$2y$`; la validación usa `password_verify()`. |
| MySQL expuesto | Intentar conectar al puerto 3306 del host. | No hay mapeo de puerto; solo `app` puede acceder a `db`. |

## Registro de cambios

| Archivo | Cambio | Motivo |
|---|---|---|
| `src/Core/security.php` | Nuevo | CSRF, escape, autorización y validación de uploads. |
| `src/Modules/Auth/login.php`, `register.php` | Modificados | Prepared statements, hashing, validación y XSS. |
| `src/Modules/Account/account.php` | Modificado | CSRF, autorización, gestión segura de contraseñas y usuarios. |
| `src/Modules/Catalog/main.php`, `description.php`, `updel.php` | Modificados | CSRF, autorización, consultas preparadas, validación y XSS. |
| `index.php`, `config/config.php`, `src/Infrastructure/database.php` | Modificados | Punto de entrada, configuración por entorno, sesiones seguras y errores no verbosos. |
| `database/init.sql` | Modificado | Hash seguro, longitud adecuada y base de datos explícita. |
| `Dockerfile`, `docker/apache.conf`, `docker-compose.yml` | Nuevos/modificados | Build reproducible, red privada sin puerto MySQL publicado, publicación HTTP local y defensa de uploads. |
| `.dockerignore` | Nuevo | Evita incluir secretos, dumps SQL, documentación y archivos de desarrollo en la imagen. |
| `.env.example`, `.gitignore` | Nuevos | Gestión segura de configuración local y exclusión de secretos. |
| `uploads/.gitkeep` | Nuevo | Mantiene el directorio de uploads vacío y preparado para el volumen de archivos. |
| `includes/main_orig.php` | Eliminado | Retira una copia antigua que conservaba consultas y salidas vulnerables. |

## Estructura modular

```text
web-aplikazioa/
├── index.php                 # Front controller y composición de la aplicación
├── config/                   # Configuración obtenida del entorno
├── src/
│   ├── Core/                 # Seguridad transversal y utilidades compartidas
│   ├── Infrastructure/       # Adaptadores externos, como la conexión MySQL
│   └── Modules/
│       ├── Auth/             # Registro y autenticación
│       ├── Account/          # Cuenta y administración de usuarios
│       └── Catalog/          # Búsqueda y gestión de productos
├── database/                 # Migraciones/SQL de inicialización
├── docker/                   # Configuración específica de Apache
├── images/                   # Recursos de catálogo
├── uploads/                  # Archivos subidos, separado por permisos
└── .github/workflows/        # Automatización CI/CD
```

La separación sigue responsabilidades únicas: la configuración no contiene lógica de
negocio, la infraestructura encapsula la conexión, `Core` contiene políticas
transversales y cada módulo agrupa una capacidad funcional. Para escalar, se pueden
añadir módulos nuevos sin ampliar un archivo monolítico, sustituir la infraestructura
por otra implementación y extraer servicios de dominio sin cambiar el punto de entrada.
| `README.md` | Nuevo | Despliegue y operación segura. |
| `.github/workflows/security.yml` | Nuevo, en la raíz del repositorio | SAST, sintaxis PHP y comprobaciones automáticas en push/PR. |

## Limitaciones conocidas

El proyecto conserva HTML antiguo y el usuario inicial de demostración por compatibilidad académica. Antes de publicar debe eliminarse ese usuario, habilitar HTTPS, rotar secretos y configurar un proxy con TLS.

## Evidencias y estado de las pruebas

Este documento contiene los pasos reproducibles de PoC y las verificaciones esperadas. Las
capturas de pantalla deben tomarse durante la ejecución local de cada paso, antes y después
de la corrección. No se han incluido capturas binarias en el repositorio.

Verificaciones automatizadas ejecutadas:

- `php -l` en todos los archivos PHP: correcto.
- `docker compose config`: correcto.
- `git diff --check`: correcto.
- `docker compose build`: pendiente de ejecutar con el daemon Docker/Podman iniciado.

## Registro de la reorganización

La reorganización arquitectónica se realizó después de la remediación de seguridad para
reducir el acoplamiento y facilitar futuras ampliaciones. Se actualizaron las inclusiones
del front controller, las rutas de los uploads, la ruta del SQL de inicialización y la
referencia al fichero de configuración de Apache en el `Dockerfile`.

No se modificaron las URL públicas ni los parámetros funcionales de las acciones existentes.
La validación posterior confirmó que todos los archivos PHP de `src/` tienen sintaxis válida
y que Docker Compose encuentra `database/init.sql` y `docker/apache.conf`.
