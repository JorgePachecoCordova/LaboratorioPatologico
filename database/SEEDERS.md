# Carga inicial de catálogos

Con las migraciones aplicadas, ejecutar:

```sh
php artisan db:seed --class=CatalogSeeder
```

Carga secretarías, municipios, distritos, áreas, sectores, establecimientos,
servicios hospitalarios, médicos, tipos de estudio, diagnósticos y muestras
citológicas. Los datos provienen de los archivos versionados en `database/data`
y de los seeders existentes. No crea pacientes, solicitudes ni resultados.

La carga agrega registros faltantes y conserva los existentes, incluidos los
desactivados o eliminados de forma lógica. No restablece ediciones de catálogos.
Los códigos y nombres identifican registros para evitar duplicados. No se
inventan relaciones geográficas: las relaciones confirmadas se administran
desde Configuración. `CatalogoRelacionesSeeder` queda fuera de la carga normal
porque propone relaciones por semejanza de nombres.

Para una instalación nueva, configurar `INITIAL_ADMIN_USERNAME` y
`INITIAL_ADMIN_PASSWORD` en el entorno y ejecutar:

```sh
php artisan migrate --seed
```

`DatabaseSeeder` ejecuta los catálogos y configura los permisos, roles y el
administrador inicial. Repetirlo conserva contraseñas y roles de usuarios
existentes. No almacenar contraseñas reales en los archivos de datos.

Los datos clínicos reales se guardan mediante los formularios del sistema;
trasladarlos desde otra base requiere una importación específica.
