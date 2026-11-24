# Quick Employment

Plataforma web que conecta a personas que necesitan un servicio (**clientes**) con personas que tienen las habilidades para ofrecerlo (**freelancers**). Proyecto de aula de AnÃ¡lisis y DiseÃ±o de Software (IngenierÃ­a de Sistemas).

## TecnologÃ­as

HTML5 Â· CSS3 Â· PHP nativo (con mysqli) Â· MySQL Â· phpMyAdmin Â· XAMPP Â· Visual Studio Code Â· Git y GitHub.

## InstalaciÃ³n (cada integrante, en su computador)

1. Instalar **XAMPP** e iniciar **Apache** y **MySQL**.
2. Clonar este repositorio dentro de `htdocs`:
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/Leider223/quick-employment.git
   ```
3. Entrar a `http://localhost/phpmyadmin`, pestaÃ±a **Importar**, y cargar `database/quick_employment.sql` (conjunto de caracteres: **utf8mb4**).
   El script crea la base `quick_employment`, las 11 tablas y los datos de prueba. Se puede volver a importar: borra y recrea las tablas.
   > Si en los nombres o descripciones ves `?` en lugar de tildes o eÃ±es, la base se importÃ³ con otra codificaciÃ³n. Vuelve a importar el script eligiendo `utf8mb4`; el archivo ya estÃ¡ guardado en UTF-8.
4. Comprobar que la base quedÃ³ creada: `quick_employment` debe tener 11 tablas (`usuarios`, `perfiles_freelancer`, `habilidades`, `freelancer_habilidades`, `categorias`, `servicios`, `disponibilidad`, `solicitudes`, `pagos`, `calificaciones` y `notificaciones`).
5. Abrir `http://localhost/quick-employment/modulos/usuarios/login.php` e iniciar sesiÃ³n con uno de los usuarios de prueba.

> Si tu MySQL tiene contraseÃ±a para `root`, no la subas a GitHub. Avisa al equipo antes de cambiar `config/conexion.php`.

## Usuarios de prueba

ContraseÃ±a de todos: `Test1234`

| Rol | Correo |
|---|---|
| Cliente | camila.cliente@test.com |
| Cliente | andres.cliente@test.com |
| Freelancer | laura.freelancer@test.com |
| Freelancer | julian.freelancer@test.com |
| Freelancer | sofia.freelancer@test.com |

> El script tambiÃ©n trae las cuentas creadas durante las pruebas (`leider`, `alonso posada`, `Camilo Posada`), con sus propias contraseÃ±as.

## Estructura del proyecto

```
quick-employment/
â”œâ”€â”€ config/conexion.php          Ãšnica conexiÃ³n a la base de datos
â”œâ”€â”€ includes/
â”‚   â”œâ”€â”€ sesion.php               SesiÃ³n, login y control de roles
â”‚   â”œâ”€â”€ notificaciones.php       Avisos de CU13 (RF14)
â”‚   â”œâ”€â”€ plantilla.php            Encabezado, menÃº por rol y pie comunes
â”‚   â””â”€â”€ utilidades.php           Funciones compartidas de formularios
â”œâ”€â”€ css/                         Estilos globales
â”œâ”€â”€ assets/                      ImÃ¡genes y logo
â”œâ”€â”€ modulos/
â”‚   â”œâ”€â”€ usuarios/                Integrante 1
â”‚   â”œâ”€â”€ servicios/               Integrante 2 (CU02 buscar y CU09 mis servicios)
â”‚   â”œâ”€â”€ solicitudes/             Integrante 3 (incluye confirmar_finalizacion.php y cancelar.php)
â”‚   â””â”€â”€ pagos_calificaciones/    Integrante 4 (incluye pagar.php y calificar.php)
â”œâ”€â”€ database/quick_employment.sql
â””â”€â”€ README.md
```

## Responsables

| Integrante | MÃ³dulo | Carpeta | Nombre |
|---|---|---|---|
| 1 | GestiÃ³n de usuarios | `modulos/usuarios/` | |
| 2 | GestiÃ³n de servicios | `modulos/servicios/` | |
| 3 | GestiÃ³n de solicitudes | `modulos/solicitudes/` | |
| 4 | Pagos y calificaciones | `modulos/pagos_calificaciones/` | |

## Forma de trabajo (Live Share + Git)

El equipo programa sobre una sola copia del proyecto: el responsable de Git abre
una sesiÃ³n de **Live Share** en VS Code, comparte el enlace y cada integrante
trabaja en su mÃ³dulo desde ahÃ­. Los commits y el `push` los hace Ãºnicamente el
responsable de Git, al terminar cada bloque de trabajo.

### Durante la sesiÃ³n

- Cada integrante modifica **solo su carpeta** dentro de `modulos/`.
- `config/`, `includes/` y `css/` los toca Ãºnicamente el responsable de Git,
  porque los usan todos los mÃ³dulos.
- **Nadie ejecuta comandos de Git.** Un `checkout` o un `reset` de un invitado
  afecta el trabajo de todos.
- No se cambia de rama mientras la sesiÃ³n estÃ¡ abierta: los archivos cambian en
  la mÃ¡quina de todos y se pierde lo que estÃ©n escribiendo.
- Si dos personas escriben el mismo archivo a la vez, gana el Ãºltimo que
  escribe. Por eso cada archivo tiene un solo dueÃ±o.
- A quien solo va a revisar se le da el modo **solo lectura** de Live Share.

### Base de datos de cada integrante

Live Share comparte archivos, no la base de datos. Cada integrante importa
`database/quick_employment.sql` en **su** XAMPP para poder probar su mÃ³dulo.
Si hace falta que todos vean los mismos datos, el responsable de Git comparte su
servidor local desde Live Share (puerto `3306`).

### Antes de cada commit

1. Ver quÃ© cambiÃ³: `git status --short`
2. Revisar la sintaxis de todos los PHP:
   ```powershell
   Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & C:\xampp\php\php.exe -l $_.FullName }
   ```
3. AÃ±adir **solo** los archivos del mÃ³dulo terminado. Evita `git add -A` para no
   arrastrar cambios a medias de otros mÃ³dulos ni borrados accidentales:
   ```powershell
   git add modulos/usuarios
   git commit -m "feat(usuarios): registro, login y perfil del freelancer"
   git push origin main
   ```

Prefijos para los mensajes de commit:

| Prefijo | CuÃ¡ndo |
|---|---|
| `feat(usuarios)` `feat(servicios)` `feat(solicitudes)` `feat(pagos)` | Funcionalidad nueva de un mÃ³dulo |
| `fix(...)` | CorrecciÃ³n de un error |
| `docs` | Cambios en el README o en la documentaciÃ³n |
| `db` | Cambios en `database/quick_employment.sql` |

## Reglas de cÃ³digo

- Una sola conexiÃ³n (`config/conexion.php`), consultas con mysqli (mysqli_real_escape_string + casts (int)),
  contraseÃ±as con `password_hash()`.
- Los textos que vienen de la base o del usuario se muestran con
  `htmlspecialchars()`.
- Estados de solicitud (siempre asÃ­, en minÃºscula): `pendiente`, `aceptada`,
  `rechazada`, `cancelada`, `finalizada`.


## Reglas funcionales acordadas para la versiÃ³n final

- **Pagos externos:** el cliente realiza el pago directamente al freelancer. Quick Employment no procesa dinero; Ãºnicamente registra valor, mÃ©todo y fecha.
- **FinalizaciÃ³n bilateral:** cliente y freelancer confirman desde el detalle de la solicitud. El estado cambia a `finalizada` Ãºnicamente cuando ambas confirmaciones estÃ¡n guardadas.
- **CalificaciÃ³n:** el cliente puede calificar una sola vez, despuÃ©s de que la solicitud estÃ© `finalizada` y el pago estÃ© registrado.
- **CancelaciÃ³n:** el cliente puede cancelar una solicitud `pendiente` o `aceptada` mientras no exista pago registrado ni confirmaciones de finalizaciÃ³n.
