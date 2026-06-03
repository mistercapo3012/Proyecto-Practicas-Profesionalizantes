<?php
// ══════════════════════════════════════════════════════════════════════════════
// api/get_stats.php — Estadísticas globales de TODAS las cámaras
// ══════════════════════════════════════════════════════════════════════════════
//
// Método HTTP: GET
//
// URLs posibles:
//   http://localhost/camaras/api/get_stats.php
//       → Considera TODOS los datos históricos
//
//   http://localhost/camaras/api/get_stats.php?horas=24
//       → Solo considera las lecturas de las últimas 24 horas
//       → Usado por Temperaturas.html para el "Resumen Global de 24 hs"
//
// Respuesta:
//   {
//     "max": 8.2,
//     "min": -0.5,
//     "avg": 2.8,
//     "periodo": "24h"      ← o "historico" si no se pasó ?horas=
//   }
//
// Si la base de datos está vacía, devuelve max/min/avg = null (sin error).

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'Este endpoint solo acepta peticiones GET'], 405);
}


// ── Parámetro opcional ?horas= ────────────────────────────────────────────────
// isset() verifica que el parámetro exista en la URL
// max(1, ...) garantiza que sea al menos 1 hora (evita valores absurdos como 0 o negativos)
$horas = isset($_GET['horas']) ? max(1, (int) $_GET['horas']) : null;

$pdo = get_conn();


if ($horas !== null) {
    // ── Con filtro de tiempo ──────────────────────────────────────────────────
    // "NOW() - INTERVAL :h HOUR" es la sintaxis de MySQL para "hace X horas"
    // Ejemplo con horas=24: WHERE FechaHora >= (hora actual menos 24 horas)
    $stmt = $pdo->prepare("
        SELECT
            MAX(Temp)           AS max,
            MIN(Temp)           AS min,
            ROUND(AVG(Temp), 1) AS avg
        FROM Datos
        WHERE FechaHora >= NOW() - INTERVAL :h HOUR
    ");
    // PDO::PARAM_INT le dice a PDO que :h es un número entero (no string)
    $stmt->bindValue(':h', $horas, PDO::PARAM_INT);
    $stmt->execute();
    $periodo = "{$horas}h";   // Ej: "24h"

} else {
    // ── Sin filtro: todos los datos históricos ────────────────────────────────
    $stmt = $pdo->query("
        SELECT
            MAX(Temp)           AS max,
            MIN(Temp)           AS min,
            ROUND(AVG(Temp), 1) AS avg
        FROM Datos
    ");
    $periodo = 'historico';
}

$row = $stmt->fetch();


// ── Formatear y responder ─────────────────────────────────────────────────────
// Si la tabla está vacía, MAX/MIN/AVG devuelven NULL en SQL.
// Verificamos null antes de castear a número, para no devolver 0 cuando no hay datos.
respond([
    'max'     => $row['max'] !== null ? (float) $row['max'] : null,
    'min'     => $row['min'] !== null ? (float) $row['min'] : null,
    'avg'     => $row['avg'] !== null ? (float) $row['avg'] : null,
    'periodo' => $periodo,
]);
