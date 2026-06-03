<?php
// ══════════════════════════════════════════════════════════════════════════════
// config/db.php — Credenciales y conexión a MySQL
// ══════════════════════════════════════════════════════════════════════════════
//
// Este archivo tiene DOS responsabilidades:
//   1. Guardar la configuración de la base de datos (host, nombre, usuario, etc.)
//   2. Proveer la función get_conn() que abre y devuelve la conexión PDO
//
// Los archivos de la API lo incluyen así:
//   require_once __DIR__ . '/../config/db.php';
// Y luego piden la conexión con:
//   $pdo = get_conn();
//
// ── ¿Qué es PDO? ──────────────────────────────────────────────────────────────
// PDO (PHP Data Objects) es la forma moderna y segura de conectarse a bases de
// datos desde PHP. Sus ventajas son:
//   - Prepared statements: evita que usuarios maliciosos inyecten SQL
//   - Funciona con MySQL, PostgreSQL, SQLite y otros sin cambiar el código
//   - Manejo de errores con excepciones (más claro que los errores silenciosos)


// ── 1. CONFIGURACIÓN ─────────────────────────────────────────────────────────
// ⚠️  EDITÁ ESTOS VALORES con los de tu instalación de XAMPP

define('DB_HOST',    'localhost');    // Siempre 'localhost' cuando XAMPP corre en tu PC
define('DB_NAME',    'camaras_db');  // El nombre que usamos en schema.sql
define('DB_USER',    'root');        // Usuario por defecto de MySQL en XAMPP
define('DB_PASS',    '');            // Contraseña de MySQL (vacía por defecto en XAMPP)
//                                  // Si le pusiste contraseña, escribila entre las comillas
define('DB_CHARSET', 'utf8mb4');     // Codificación: soporta acentos, ñ, emojis


// ── 2. FUNCIÓN DE CONEXIÓN ───────────────────────────────────────────────────
function get_conn(): PDO {

    // La palabra clave "static" hace que PHP recuerde el valor de $pdo
    // entre llamadas a esta función. Así, si el script pide la conexión
    // dos veces, no abre dos conexiones a MySQL; reutiliza la primera.
    static $pdo = null;

    if ($pdo === null) {

        // ── DSN (Data Source Name) ────────────────────────────────────────────
        // El DSN le dice a PDO qué motor usar y cómo conectarse.
        // Formato:  "driver:host=...;dbname=...;charset=..."
        $dsn = "mysql:host=" . DB_HOST
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        // ── Opciones de comportamiento ────────────────────────────────────────
        $opciones = [
            // ERRMODE_EXCEPTION: si una consulta SQL falla, PHP lanza una excepción
            // en vez de fallar silenciosamente (mucho más fácil de debuggear)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // FETCH_ASSOC: los resultados se devuelven como arrays asociativos
            // Ej: $row['Temp'] en vez de $row[2]
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // EMULATE_PREPARES = false: usa prepared statements reales de MySQL
            // (más seguro y más eficiente que los emulados)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            // Intentar abrir la conexión
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);

        } catch (PDOException $e) {
            // Si la conexión falló (credenciales incorrectas, MySQL apagado, etc.)
            // respondemos con un JSON de error y detenemos el script
            http_response_code(500);
            echo json_encode([
                'error'   => 'No se pudo conectar a la base de datos.',
                'detalle' => $e->getMessage()   // El mensaje técnico del error
            ]);
            exit;
        }
    }

    return $pdo;
}
