# Quick Employment

Plataforma web que conecta a personas que necesitan un servicio (**clientes**) con personas que tienen las habilidades para ofrecerlo (**freelancers**). Proyecto de aula de Análisis y Diseño de Software (Ingeniería de Sistemas).

## Tecnologías

HTML5 · CSS3 · PHP nativo (con PDO) · MySQL · phpMyAdmin · XAMPP · Visual Studio Code · Git y GitHub.

## Instalación (cada integrante, en su computador)

1. Instalar **XAMPP** e iniciar **Apache** y **MySQL**.
2. Clonar este repositorio dentro de `htdocs`:
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/Leider223/quick-employment.git
   ```
3. Entrar a `http://localhost/phpmyadmin`, pestaña **Importar**, y cargar `database/quick_employment.sql` (conjunto de caracteres: **utf8mb4**).
   El script crea la base `quick_employment`, las 11 tablas y los datos de prueba. Se puede volver a importar: borra y recrea las tablas.
   > Si en los nombres o descripciones ves `?` en lugar de tildes o eñes, la base se importó con otra codificación. Vuelve a importar el script eligiendo `utf8mb4`; el archivo ya está guardado en UTF-8.
4. Comprobar que la base quedó creada: `quick_employment` debe tener 11 tablas (`usuarios`, `perfiles_freelancer`, `habilidades`, `freelancer_habilidades`, `categorias`, `servicios`, `disponibilidad`, `solicitudes`, `pagos`, `calificaciones` y `notificaciones`).
5. Abrir `http://localhost/quick-employment/modulos/usuarios/login.php` e iniciar sesión con uno de los usuarios de prueba.

> Si tu MySQL tiene contraseña para `root`, no la subas a GitHub. Avisa al equipo antes de cambiar `config/conexion.php`.

## Usuarios de prueba

Contraseña de todos: `Test1234`

| Rol | Correo |
|---|---|
| Cliente | camila.cliente@test.com |
| Cliente | andres.cliente@test.com |
| Freelancer | laura.freelancer@test.com |
| Freelancer | julian.freelancer@test.com |
| Freelancer | sofia.freelancer@test.com |

> El script también trae las cuentas creadas durante las pruebas (`leider`, `alonso posada`, `Camilo Posada`), con sus propias contraseñas.

## Estructura del proyecto

```
quick-employment/
├── config/conexion.php          Única conexión a la base de datos
├── includes/
│   ├── sesion.php               Sesión, login y control de roles
│   ├── notificaciones.php       Avisos de CU13 (RF14)
│   ├── plantilla.php            Encabezado, menú por rol y pie comunes
│   └── utilidades.php           Funciones compartidas de formularios
├── css/                         Estilos globales
├── assets/                      Imágenes y logo
├── modulos/
│   ├── usuarios/                Integrante 1
│   ├── servicios/               Integrante 2 (CU02 buscar y CU09 mis servicios)
│   ├── solicitudes/             Integrante 3 (incluye confirmar_finalizacion.php y cancelar.php)
│   └── pagos_calificaciones/    Integrante 4 (incluye pagar.php y calificar.php)
├── database/quick_employment.sql
├── probar_sesion.php            Temporal: panel de prueba de la sesión
└── README.md
```

## Responsables

| Integrante | Módulo | Carpeta | Nombre |
|---|---|---|---|
| 1 | Gestión de usuarios | `modulos/usuarios/` | |
| 2 | Gestión de servicios | `modulos/servicios/` | |
| 3 | Gestión de solicitudes | `modulos/solicitudes/` | |
| 4 | Pagos y calificaciones | `modulos/pagos_calificaciones/` | |

## Forma de trabajo (Live Share + Git)

El equipo programa sobre una sola copia del proyecto: el responsable de Git abre
una sesión de **Live Share** en VS Code, comparte el enlace y cada integrante
trabaja en su módulo desde ahí. Los commits y el `push` los hace únicamente el
responsable de Git, al terminar cada bloque de trabajo.

### Durante la sesión

- Cada integrante modifica **solo su carpeta** dentro de `modulos/`.
- `config/`, `includes/` y `css/` los toca únicamente el responsable de Git,
  porque los usan todos los módulos.
- **Nadie ejecuta comandos de Git.** Un `checkout` o un `reset` de un invitado
  afecta el trabajo de todos.
- No se cambia de rama mientras la sesión está abierta: los archivos cambian en
  la máquina de todos y se pierde lo que estén escribiendo.
- Si dos personas escriben el mismo archivo a la vez, gana el último que
  escribe. Por eso cada archivo tiene un solo dueño.
- A quien solo va a revisar se le da el modo **solo lectura** de Live Share.

### Base de datos de cada integrante

Live Share comparte archivos, no la base de datos. Cada integrante importa
`database/quick_employment.sql` en **su** XAMPP para poder probar su módulo.
Si hace falta que todos vean los mismos datos, el responsable de Git comparte su
servidor local desde Live Share (puerto `3306`).

### Antes de cada commit

1. Ver qué cambió: `git status --short`
2. Revisar la sintaxis de todos los PHP:
   ```powershell
   Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & C:\xampp\php\php.exe -l $_.FullName }
   ```
3. Añadir **solo** los archivos del módulo terminado. Evita `git add -A` para no
   arrastrar cambios a medias de otros módulos ni borrados accidentales:
   ```powershell
   git add modulos/usuarios
   git commit -m "feat(usuarios): registro, login y perfil del freelancer"
   git push origin main
   ```

Prefijos para los mensajes de commit:

| Prefijo | Cuándo |
|---|---|
| `feat(usuarios)` `feat(servicios)` `feat(solicitudes)` `feat(pagos)` | Funcionalidad nueva de un módulo |
| `fix(...)` | Corrección de un error |
| `docs` | Cambios en el README o en la documentación |
| `db` | Cambios en `database/quick_employment.sql` |

## Reglas de código

- Una sola conexión (`config/conexion.php`), consultas preparadas con PDO,
  contraseñas con `password_hash()`.
- Los textos que vienen de la base o del usuario se muestran con
  `htmlspecialchars()`.
- Estados de solicitud (siempre así, en minúscula): `pendiente`, `aceptada`,
  `rechazada`, `cancelada`, `finalizada`.


## Reglas funcionales acordadas para la versión final

- **Pagos externos:** el cliente realiza el pago directamente al freelancer. Quick Employment no procesa dinero; únicamente registra valor, método y fecha.
- **Finalización bilateral:** cliente y freelancer confirman desde el detalle de la solicitud. El estado cambia a `finalizada` únicamente cuando ambas confirmaciones están guardadas.
- **Calificación:** el cliente puede calificar una sola vez, después de que la solicitud esté `finalizada` y el pago esté registrado.
- **Cancelación:** el cliente puede cancelar una solicitud `pendiente` o `aceptada` mientras no exista pago registrado ni confirmaciones de finalización.
