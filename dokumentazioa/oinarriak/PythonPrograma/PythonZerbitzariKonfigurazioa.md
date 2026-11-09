
# Gmail-perfil — Automatización de correos con Python

## 1. Introducción

Este proyecto consiste en una aplicación desarrollada en Python que comprueba automáticamente los usuarios registrados en una base de datos y envía un correo electrónico a aquellos que no tienen una imagen de perfil asignada.

El objetivo principal es automatizar esta tarea para evitar tener que revisar manualmente los usuarios.

La aplicación se ha desplegado en Ubuntu Server utilizando Docker y se ha configurado una tarea programada mediante Cron para ejecutarla todos los días a las 08:00.

## 2. Objetivos del proyecto

Los objetivos principales son:

- Automatizar la comprobación de usuarios sin imagen de perfil.
- Consultar los registros almacenados en MySQL.
- Obtener los correos electrónicos de los usuarios afectados.
- Enviar un aviso mediante SMTP.
- Ejecutar la aplicación automáticamente cada día.
- Utilizar Docker para simplificar el despliegue.
- Evitar instalar Python directamente en Ubuntu Server.

## 3. Tecnologías utilizadas

| Tecnología | Función |
|---|---|
| Ubuntu Server 24.04 | Servidor donde se ejecuta la aplicación |
| Python 3.10 | Lenguaje de programación |
| Docker | Ejecución mediante contenedores |
| Docker Compose | Gestión de los servicios |
| MySQL 8 | Base de datos |
| smtp4dev | Servidor SMTP de pruebas |
| Git | Control de versiones |
| Cron | Automatización de tareas |

## 4. Funcionamiento de la aplicación

El funcionamiento previsto del programa es el siguiente:

1. Se inicia la aplicación Python.
2. Se conecta a la base de datos MySQL.
3. Consulta los registros de usuarios.
4. Comprueba qué usuarios no tienen imagen de perfil.
5. Obtiene sus direcciones de correo electrónico.
6. Envía un correo de aviso mediante SMTP.
7. Finaliza la ejecución.

La aplicación está preparada para ejecutarse automáticamente una vez al día.

**Nota:** el funcionamiento completo debe comprobarse con una ejecución manual de `main.py`.

## 5. Arquitectura del proyecto

La aplicación utiliza tres servicios Docker:

- **db:** base de datos MySQL.
- **smtp4dev:** servidor de correo de pruebas.
- **app:** aplicación Python.

La arquitectura es la siguiente:

```text
             UBUNTU SERVER
                   |
             Docker Compose
                   |
        +----------+----------+
        |          |          |
      MySQL      smtp4dev    Python
        |          |          |
        +----------+----------+
                   |
              Aplicación
                   |
                 Cron
                   |
           Todos los días
              08:00
```

Los servicios MySQL y smtp4dev permanecen disponibles mientras que el programa Python se ejecuta cuando lo inicia Cron.

## 6. Estructura del proyecto

Los principales archivos son:

```text
gmail-perfil/
|
|-- Dockerfile
|-- docker-compose.yml
|-- requirements.txt
|-- main.py
|
|-- scripts/
|   |-- crontab.sh
|   |
|   |-- init/
|       |-- db.sql
```

### Archivos principales

- `Dockerfile`: prepara el entorno Python.
- `docker-compose.yml`: configura los servicios Docker.
- `requirements.txt`: contiene las dependencias Python.
- `main.py`: archivo principal del programa.
- `scripts/crontab.sh`: ejecuta la aplicación desde Cron.
- `scripts/init/db.sql`: inicializa la base de datos.

## 7. Preparación de Ubuntu Server

Para alojar la aplicación se ha utilizado una máquina Ubuntu Server.

Inicialmente el servidor no tenía Docker ni Python instalados.

No es necesario instalar Python directamente, porque se utiliza dentro del contenedor Docker.

### 7.1. Actualizar Ubuntu

Primero se actualizaron los repositorios:

```bash
sudo apt update
```

Se instalaron las herramientas necesarias:

```bash
sudo apt install -y ca-certificates curl git cron
```

### 7.2. Instalar Docker

Durante la instalación apareció un error porque Ubuntu no encontraba los paquetes oficiales de Docker.

Para solucionarlo se añadió el repositorio oficial.

Primero se creó el directorio de claves:

```bash
sudo install -m 0755 -d /etc/apt/keyrings
```

Se descargó la clave oficial:

```bash
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
```

Se configuraron los permisos:

```bash
sudo chmod a+r /etc/apt/keyrings/docker.asc
```

Se añadió el repositorio:

```bash
sudo tee /etc/apt/sources.list.d/docker.sources > /dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: noble
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
```

Después se actualizaron los repositorios:

```bash
sudo apt update
```

Finalmente se instalaron Docker y Docker Compose:

```bash
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

### 7.3. Activar Docker

Se activó el servicio Docker:

```bash
sudo systemctl enable --now docker
```

Para comprobar la instalación:

```bash
docker --version
```

```bash
docker compose version
```

También se puede realizar una prueba:

```bash
sudo docker run hello-world
```

## 8. Descargar el proyecto desde Git

El código de la aplicación se encuentra en un repositorio Git.

Para descargarlo se utiliza:

```bash
git clone URL_DEL_REPOSITORIO
```

En este servidor el proyecto se encuentra en:

```text
/home/erronka-2/gmail-perfil
```

Para acceder:

```bash
cd /home/erronka-2/gmail-perfil
```

Comprobamos los archivos:

```bash
ls -la
```

## 9. Configuración de Docker

Docker Compose permite ejecutar los diferentes servicios de la aplicación.

### 9.1. Base de datos MySQL

Se utiliza la imagen:

```yaml
image: mysql:8.0
```

La base de datos configurada es:

```text
ziber_db
```

El servicio ejecuta el archivo SQL inicial:

```text
scripts/init/db.sql
```

También incluye un healthcheck que comprueba si MySQL está funcionando.

### 9.2. Servidor SMTP

Se utiliza smtp4dev:

```yaml
image: rnwood/smtp4dev:v3
```

Los puertos configurados son:

```text
8080:80
2525:25
```

El puerto 8080 permite acceder a la interfaz web de smtp4dev.

Por ejemplo:

```text
http://IP_DEL_SERVIDOR:8080
```

smtp4dev permite comprobar los correos generados durante las pruebas.

No es un servidor de entrega de correo real.

### 9.3. Aplicación Python

La aplicación utiliza un Dockerfile basado en Python 3.10.

```dockerfile
FROM python:3.10-slim

WORKDIR /app

COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt

COPY . .

CMD ["python", "main.py"]
```

Este Dockerfile realiza las siguientes operaciones:

1. Descarga la imagen de Python.
2. Crea el directorio de trabajo.
3. Copia las dependencias.
4. Instala las librerías necesarias.
5. Copia el código del proyecto.
6. Ejecuta `main.py`.

De esta manera no necesitamos instalar Python directamente en Ubuntu Server.

## 10. Construcción y ejecución de Docker

Desde la carpeta principal del proyecto:

```bash
cd /home/erronka-2/gmail-perfil
```

Primero comprobamos la configuración:

```bash
sudo docker compose config
```

Construimos la imagen Python:

```bash
sudo docker compose build app
```

Iniciamos MySQL y smtp4dev:

```bash
sudo docker compose up -d db smtp4dev
```

Comprobamos los contenedores:

```bash
sudo docker compose ps
```

### Ejecutar Python manualmente

Para comprobar que la aplicación funciona:

```bash
sudo docker compose run --rm app
```

Este comando inicia un contenedor temporal, ejecuta el programa Python y elimina el contenedor cuando termina.

Así podemos ejecutar la aplicación sin dejar Python funcionando permanentemente.

## 11. Automatización mediante Cron

Uno de los principales objetivos del proyecto es que la aplicación se ejecute automáticamente todos los días a las 08:00.

Para conseguirlo se utiliza Cron.

Cron permite programar comandos y scripts para que se ejecuten en una fecha y hora determinadas.

### 11.1. Activar Cron

Primero comprobamos que está instalado:

```bash
sudo apt install cron -y
```

Se activa el servicio:

```bash
sudo systemctl enable --now cron
```

Para comprobar su estado:

```bash
sudo systemctl status cron
```

### 11.2. Crear el script de ejecución

El proyecto contiene un archivo llamado:

```text
scripts/crontab.sh
```

Este script se utiliza para iniciar la aplicación Python desde Docker.

El contenido preparado es:

```bash
#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/.." && pwd)"

cd "${PROJECT_DIR}"

/usr/bin/docker compose run --rm app
```

El script primero localiza el directorio principal del proyecto.

Después ejecuta la aplicación mediante Docker Compose.

Se utiliza:

```bash
docker compose run --rm app
```

De esta manera se ejecuta Python una sola vez y el contenedor se elimina cuando termina.

Anteriormente se utilizaba:

```bash
docker compose up --build app
```

Se ha cambiado porque no es necesario reconstruir la imagen Docker todos los días.

### 11.3. Dar permisos al script

Para permitir su ejecución:

```bash
chmod +x scripts/crontab.sh
```

También podemos comprobar su ruta:

```bash
realpath scripts/crontab.sh
```

Resultado:

```text
/home/erronka-2/gmail-perfil/scripts/crontab.sh
```

### 11.4. Programar la ejecución automática

Para configurar la tarea se ejecutó:

```bash
sudo crontab -e
```

La primera vez Ubuntu pidió seleccionar un editor.

Se seleccionó la opción:

```text
1. /bin/nano
```

Dentro del archivo se añadió la siguiente línea:

```cron
0 8 * * * /bin/bash /home/erronka-2/gmail-perfil/scripts/crontab.sh >> /var/log/python-diario.log 2>&1
```

Esta línea permite ejecutar el programa automáticamente todos los días a las 08:00.

### 11.5. Explicación de la configuración

La expresión Cron utilizada es:

```cron
0 8 * * *
```

| Campo | Valor | Significado |
|---|---|---|
| Minuto | 0 | Minuto 00 |
| Hora | 8 | 08:00 |
| Día | * | Todos los días |
| Mes | * | Todos los meses |
| Día semana | * | Todos los días de la semana |

Por tanto, Python se ejecutará cada día a las 08:00.

El resto del comando indica qué script debe ejecutarse:

```bash
/bin/bash /home/erronka-2/gmail-perfil/scripts/crontab.sh
```

Y esta parte guarda los resultados y errores:

```bash
>> /var/log/python-diario.log 2>&1
```

### 11.6. Comprobar la tarea

Para comprobar que la programación se ha guardado:

```bash
sudo crontab -l
```

Debería aparecer:

```cron
0 8 * * * /bin/bash /home/erronka-2/gmail-perfil/scripts/crontab.sh >> /var/log/python-diario.log 2>&1
```

La ejecución queda programada sin necesidad de iniciar manualmente el programa cada mañana.

## 12. Configuración de la hora

Para asegurar que la ejecución sea a las 08:00 de España, se utiliza la zona horaria de Madrid.

Se configura con:

```bash
sudo timedatectl set-timezone Europe/Madrid
```

Para comprobar la configuración:

```bash
timedatectl
```

Así se respetan los cambios entre horario de invierno y verano.

## 13. Pruebas de funcionamiento

Antes de utilizar la automatización es necesario comprobar que el programa se ejecuta correctamente.

### 13.1. Ejecutar el script manualmente

```bash
sudo bash /home/erronka-2/gmail-perfil/scripts/crontab.sh
```

Esto permite ejecutar la misma tarea que utiliza Cron.

### 13.2. Comprobar Docker

```bash
sudo docker compose ps
```

### 13.3. Comprobar Cron

```bash
sudo systemctl status cron
```

### 13.4. Consultar los registros

Después de la primera ejecución programada, se pueden comprobar los registros:

```bash
sudo tail -n 100 /var/log/python-diario.log
```

También podemos consultar los registros de MySQL:

```bash
sudo docker compose logs --tail=50 db
```

Y los del servidor SMTP:

```bash
sudo docker compose logs --tail=50 smtp4dev
```

## 14. Actualización del proyecto

Si realizamos cambios en el código Python y los subimos al repositorio Git, podemos actualizar el servidor.

Primero entramos en el proyecto:

```bash
cd /home/erronka-2/gmail-perfil
```

Descargamos los cambios:

```bash
git pull
```

Volvemos a construir la imagen:

```bash
sudo docker compose build app
```

La siguiente ejecución utilizará la nueva versión de Python.

Si fuera necesario iniciar los servicios:

```bash
sudo docker compose up -d db smtp4dev
```

## 15. Comandos de mantenimiento

### Ver contenedores activos

```bash
sudo docker ps
```

### Ver servicios del proyecto

```bash
sudo docker compose ps
```

### Iniciar MySQL y SMTP

```bash
sudo docker compose up -d db smtp4dev
```

### Ejecutar Python

```bash
sudo docker compose run --rm app
```

### Detener los servicios

```bash
sudo docker compose down
```

### Ver las tareas Cron

```bash
sudo crontab -l
```

### Editar la programación

```bash
sudo crontab -e
```

### Consultar el registro de Python

```bash
sudo tail -f /var/log/python-diario.log
```

## 16. Seguridad y mejoras pendientes

Antes de utilizar la aplicación en un entorno real conviene revisar los siguientes aspectos:

- Cambiar la contraseña de MySQL que aparece directamente en Docker Compose.
- Evitar publicar el puerto 3306 si únicamente accede Python.
- Añadir un volumen persistente para la base de datos.
- Configurar copias de seguridad.
- Utilizar un servidor SMTP real si se necesita enviar correos a destinatarios externos.
- Evitar el envío repetido de correos al mismo usuario.
- Comprobar que Cron está ejecutando correctamente el programa.
- Revisar periódicamente los registros de errores.

El archivo Docker Compose original utiliza una contraseña de prueba y no configura un volumen persistente para los datos de MySQL.

Estas mejoras deben realizarse antes de utilizar el proyecto con información importante.

## 17. Resultado final

Con esta configuración se ha preparado un entorno en Ubuntu Server para ejecutar una aplicación Python utilizando Docker.

Los servicios MySQL y smtp4dev proporcionan la base de datos y el entorno de correo de pruebas.

Python se ejecuta dentro de su propio contenedor, por lo que no es necesario instalarlo directamente en Ubuntu Server.

Además, se ha preparado una tarea automática mediante Cron para ejecutar el programa todos los días a las 08:00.

Para confirmar que todo está funcionando correctamente, queda verificar una ejecución manual completa y comprobar los registros de la primera ejecución automática.

## 18. Conclusión

Este proyecto permite automatizar la revisión de usuarios sin imagen de perfil y preparar el envío de avisos mediante Python.

Durante el desarrollo del despliegue se han utilizado Docker, Docker Compose, Git, MySQL y Cron.

La utilización de contenedores simplifica la instalación y permite mantener separados los diferentes servicios.

Por otro lado, Cron permite automatizar la ejecución diaria sin necesidad de acceder manualmente al servidor.

De esta manera se dispone de una solución que facilita la administración, el mantenimiento y la ejecución programada de la aplicación.
