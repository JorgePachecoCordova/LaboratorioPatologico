# Entrega y despliegue del sistema de Patología

Esta guía acompaña el código que se entregará al ingeniero. El sistema utiliza PHP
8.3.36, Laravel 12 y PostgreSQL. GitHub transporta el código; la base de datos y
los archivos privados se transfieren por un canal privado independiente.

## Numeración e historial

- Cada combinación de serie U, R o G y gestión tiene su propio contador. La
  primera solicitud de cada serie es `000001-U`, `000001-R` o `000001-G`.
  El número impreso se interpreta junto con la gestión: puede repetirse en otro año.
- El año procede de la fecha de la solicitud. Cargar una solicitud atrasada de
  diciembre utiliza la gestión anterior, sin alterar el contador de enero.
- Citología mantiene sus series: `SC-000001-2027` para solicitudes y `001-2027`
  para informes. Los contadores se inicializan desde los documentos existentes,
  incluidos los archivados, y luego se reservan bajo bloqueo transaccional.
- Al comenzar una gestión se crea un contador nuevo. No se vacían tablas,
  no se borran pacientes ni resultados y no se ponen a cero contadores anteriores.
- Los identificadores internos y los índices únicos de la base de datos
  mantienen separadas las atenciones. No eliminar físicamente estudios o pacientes;
  el sistema dispone de borrado lógico en sus modelos clínicos.
- La migración `2026_10_26_000000_create_annual_document_sequences_table.php`
  agrega los contadores de citología sin modificar los informes existentes.
- La zona horaria del sistema es `America/La_Paz`, configurable con `APP_TIMEZONE`.
  Mantenerla igual en el servidor y en el planificador.

### Identificación histórica pendiente de conciliación

Las solicitudes generales tienen `patient_id`; citología también admite ese
vínculo y guarda datos de la atención. Los informes de anatomopatología actuales
guardan nombre y edad, pero su historial se busca por nombre. Algunos informes
citológicos antiguos también se encuentran por nombre. Antes de la puesta en
producción hay que conciliar los registros heredados con el CI o identificador
del paciente, revisar homónimos y definir el vínculo permanente de anatomopatología.
No unir personas automáticamente solo porque sus nombres coincidan. Este cambio
no reescribe identidades ni adjudica informes antiguos a otra persona.

## Preparación para GitHub

1. Crear un repositorio privado y dar acceso al ingeniero. Revisar los archivos
   que se van a incluir antes de confirmar o subir cambios.
2. Ejecutar `php tools/check_github_files.php`. Bloquea rutas de `.env`, bases de
   datos, respaldos, registros, sesiones y archivos privados, incluidos los que
   ya estén siendo rastreados por Git. No sustituye la revisión de secretos y
   documentos: comprobar manualmente que no haya nombres, CI o estudios reales
   en otros archivos, capturas o en el historial anterior del repositorio.
3. Incluir código, migraciones, catálogos sin datos de pacientes, `composer.lock`,
   `package-lock.json` y `.env.example`. No incluir `vendor` ni `node_modules`.
4. `.gitignore` no retira archivos ya rastreados. Las vistas compiladas que
   estaban en Git se han retirado del índice conservando los archivos locales.
   Si el control encuentra otros archivos privados rastreados, retirarlos del
   índice antes de publicar y revisar si existen en commits anteriores.
5. Añadir los archivos elegidos, revisar `git diff --cached` y realizar el commit.
   Crear el remoto y ejecutar `git push` cuando el propietario decida publicar.

Los respaldos y `.env` no se publican ni siquiera en un repositorio privado.
La aplicación usa claves y credenciales configuradas en el servidor.

## Primer despliegue

Preparar PostgreSQL y PHP 8.3.36 con sus extensiones requeridas, incluido
`pdo_pgsql`, Composer y Node para compilar recursos. Hacer que el servidor web
apunte a `public/`, nunca a la raíz del repositorio, y usar HTTPS. El usuario de
la aplicación debe poder escribir en `storage` y `bootstrap/cache`.

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs
npm ci
npm run build
cp .env.example .env
```

Configurar `.env` de forma privada:

```dotenv
APP_NAME=Patologia
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio-del-hospital.example
APP_TIMEZONE=America/La_Paz
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nombre_asignado_por_el_ingeniero
DB_USERNAME=usuario_exclusivo_de_la_aplicacion
DB_PASSWORD=valor_privado
SESSION_SECURE_COOKIE=true
BACKUP_ENABLED=false
PG_DUMP_BINARY=/usr/bin/pg_dump
BACKUP_DIRECTORY=/srv/backups/patologia
BACKUP_TIME=02:00
```

Elegir uno de estos dos caminos:

**Instalación vacía:** ejecutar `php artisan key:generate`, luego
`php artisan migrate --force`. Configurar las variables `INITIAL_ADMIN_*` y
ejecutar `php artisan db:seed --force` para crear catálogos y administrador.

**Traslado de datos existentes:** restaurar el respaldo completo en una base
destino vacía antes de ejecutar `php artisan migrate --force`. Mantener la
`APP_KEY` original junto con los archivos privados necesarios. No ejecutar
`key:generate` sobre la instalación trasladada ni cargar datos de prueba.

Después de cualquiera de los caminos:

```sh
php artisan optimize
php artisan about --only=environment
```

No usar `migrate:fresh`, `db:wipe` ni restauraciones con `--clean` sobre una base
con pacientes. No cambiar ni eliminar una `APP_KEY` existente sin un plan de
migración de los datos cifrados. Reiniciar PHP y los trabajadores persistentes
si se actualizan sus binarios o configuración. Configurar trabajadores de cola
si el despliegue los utiliza.

## Respaldo y recuperación

El comando `php artisan patologia:backup` crea un archivo PostgreSQL custom
comprimido y un archivo `.sha256` en el directorio privado configurado. Incluye
el esquema, pacientes, solicitudes, estudios, resultados, usuarios, auditoría y
contadores de esa base. No incluye los adjuntos del disco, `.env`, roles globales
de PostgreSQL ni la configuración del servidor. Guardar esos elementos por
separado con acceso restringido; el ingeniero debe recrear los roles necesarios.

La herramienta `pg_dump` debe ser compatible con la versión del servidor.
En este equipo se encontró `C:/Program Files/PostgreSQL/17/bin/pg_dump.exe`.
En Windows se puede configurar esa ruta mediante `PG_DUMP_BINARY` en `.env`.

El archivo no está cifrado por el comando. El ingeniero debe usar almacenamiento
cifrado y copiar los respaldos fuera del servidor con acceso restringido. Una
copia en el mismo disco no cubre la pérdida del servidor. Definir cuánto se
puede perder entre copias; para un servicio con mucha actividad, evaluar
respaldo físico y archivado WAL para recuperación a un instante, además de
exportaciones lógicas. Definir retención y vigilar el espacio disponible; este
comando no elimina respaldos anteriores automáticamente.

Para activar la copia diaria, comprobar antes una restauración y configurar:

```dotenv
BACKUP_ENABLED=true
BACKUP_TIME=02:00
```

Actualizar la configuración con `php artisan config:cache` y agregar al cron del
usuario del servicio, sustituyendo la ruta del proyecto y el PHP apropiado:

```cron
* * * * * cd /srv/patologia && /usr/bin/php artisan schedule:run >> /srv/patologia/storage/logs/scheduler.log 2>&1
```

`withoutOverlapping` evita dos copias programadas simultáneas en el mismo
servidor. En despliegues con varias instancias, ejecutar el cron de respaldos
en una sola instancia. Los fallos retornan código distinto de cero y se registran
en el log de Laravel. Configurar alertas operativas por fallo, ausencia de copia
reciente y falta de espacio; la aplicación no envía estas alertas por sí sola.

### Comprobación de una copia

```sh
cd /srv/backups/patologia
sha256sum -c archivo.dump.sha256
pg_restore --list archivo.dump
```

El hash comprueba integridad de transferencia y el listado comprueba que el
archivo es legible. Ninguno sustituye una restauración real.

### Prueba de restauración

Crear una base vacía independiente llamada, por ejemplo, `patologia_restauracion`.
Usar credenciales privadas mediante `.pgpass` o la configuración segura del
operador, evitando contraseñas en el historial de terminal. Sobre esa base vacía:

```sh
pg_restore --exit-on-error --single-transaction --no-owner --no-privileges --dbname=patologia_restauracion archivo.dump
```

Dirigir una copia aislada de la aplicación a la base restaurada. Comprobar
cantidades de pacientes, solicitudes y resultados, abrir estudios de varias
gestiones y confirmar que los siguientes correlativos no se repitan. Restaurar
tanto `request_sequences` como `annual_document_sequences`; no borrarlos en
actualizaciones. Registrar fecha, operador, duración y resultado de la prueba.

Para el traslado final, detener temporalmente las escrituras, generar la copia
final, restaurarla en el destino vacío, copiar los archivos privados y cambiar
la conexión cuando las verificaciones hayan pasado. No mantener dos servidores
recibiendo nuevas atenciones sobre copias independientes de la base.

## Validación antes de abrir el servicio

- Ejecutar las comprobaciones aisladas `php tests/Checks/AnnualSequenceCheck.php`,
  `php tests/Checks/ProfileCheck.php` y `php tests/Checks/ResultAccessAuditCheck.php`.
  Usan SQLite en memoria, sin borrar la base configurada de pacientes.
- En un entorno PostgreSQL de pruebas, comprobar dos registros simultáneos y la
  concurrencia al iniciar una gestión. `php tests/Checks/PostgresAnnualSequenceCheck.php`
  crea un esquema temporal de datos ficticios en la conexión PostgreSQL configurada,
  ejecuta seis procesos y retira exclusivamente ese esquema al terminar. Usarlo
  en un entorno de pruebas, con permiso de crear esquemas. La prueba de 1500
  números usa SQLite y es secuencial; ninguna sustituye una prueba de carga con
  volumen clínico representativo y todos los módulos.
- Probar acceso por rol, registro, muestras, resultados, consulta histórica,
  impresión, exportación y cambio de año. Confirmar que los reportes anteriores
  siguen disponibles y revisar los casos de homónimos indicados arriba.
- Verificar copia externa, recuperación, tareas programadas, HTTPS, permisos y
  ausencia de información sensible en GitHub.
- Medir las búsquedas con volumen representativo. La paginación limita las filas
  mostradas; las búsquedas por fragmentos de nombre pueden necesitar índices
  específicos de PostgreSQL según el volumen y el plan de ejecución real.

### Resultado de las verificaciones del 26 de octubre de 2026

Pasaron la numeración de 1500 solicitudes en memoria, el reinicio anual, la
recuperación desde documentos archivados y el rollback. En PostgreSQL pasaron
seis procesos concurrentes con 240 solicitudes U y 240 citológicas sin números
duplicados. También pasaron las comprobaciones de perfil y paginación histórica.
Se creó una copia real de la base local y se verificaron su SHA256 y el catálogo
legible con `pg_restore`; no se realizó una restauración completa de esa copia.

La comprobación existente `ResultAccessAuditCheck.php` se detuvo por una
discrepancia de permisos: espera que `anatomopathology_office` no consulte
`resultados.index`, mientras que `AccessRoles` sí autoriza esa consulta. No se
cambiaron permisos clínicos para hacer pasar la prueba. Antes del despliegue,
el responsable debe confirmar la matriz de acceso y ajustar la implementación
o esa expectativa de prueba según la decisión aprobada.

## Referencias oficiales

- [Despliegue Laravel 12](https://laravel.com/docs/12.x/deployment)
- [pg_dump](https://www.postgresql.org/docs/current/app-pgdump.html)
- [pg_restore](https://www.postgresql.org/docs/current/app-pgrestore.html)
