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
3. Entrar a `http://localhost/phpmyadmin`, pestaña **Importar**, y cargar `database/quick_employment.sql` (conjunto de caracteres: utf-8).
4. Abrir `http://localhost/quick-employment/probar_conexion.php`. Debe decir **Conexión exitosa** y **Usuarios registrados: 5**.

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

## Estructura del proyecto

```
quick-employment/
├── config/conexion.php          Única conexión a la base de datos
├── includes/                    Archivos compartidos (sesión, notificaciones, encabezado, pie)
├── css/                         Estilos globales
├── assets/                      Imágenes y logo
├── modulos/
│   ├── usuarios/                Integrante 1
│   ├── servicios/               Integrante 2
│   ├── solicitudes/             Integrante 3
│   └── pagos_calificaciones/    Integrante 4
├── database/quick_employment.sql
├── probar_conexion.php          Temporal: se borra al terminar la configuración
└── README.md
```

## Responsables

| Integrante | Módulo | Rama | Nombre |
|---|---|---|---|
| 1 | Gestión de usuarios | `feature/usuarios` | |
| 2 | Gestión de servicios | `feature/servicios` | |
| 3 | Gestión de solicitudes | `feature/solicitudes` | |
| 4 | Pagos y calificaciones | `feature/pagos-calificaciones` | |

## Reglas de trabajo

- **Nadie sube código directo a `main`.** Cada integrante trabaja en su rama y entra a `main` con un Pull Request revisado por un compañero.
- Cada integrante solo modifica su carpeta dentro de `modulos/`.
- Los archivos de `config/` e `includes/` tienen responsable; no se cambian sin avisar.
- Una sola conexión (`config/conexion.php`), consultas preparadas con PDO, contraseñas con `password_hash()`.
- Estados de solicitud (siempre así, en minúscula): `pendiente`, `aceptada`, `rechazada`, `cancelada`, `finalizada`.
