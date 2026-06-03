<?php
// ══════════════════════════════════════════════════════════════════════════════
// api/get_latest.php — Devuelve las últimas N lecturas de todas las cámaras
// ══════════════════════════════════════════════════════════════════════════════
//
// Método HTTP: GET
//
// URLs posibles:
//   http://localhost/camaras/api/get_latest.php
//       → Devuelve las últimas 100 lecturas (valor por defecto)
//
//   http://localhost/camaras/api/get_latest.php?n=50
//       → Devuelve las últimas 50 lecturas
//
// Usado por: Historial.html (tabla de registro detallado)
//
// Respuesta — array de lecturas, la más reciente primero:
// [
//   { "id": 10, "Camara": 1, "Temp": 3.5, "FechaHora": "2024-06-01 14:30:00" },
//   { "id": 9,  "Camara": 3, "Temp": 2.1, "FechaHora": "2024-06-01 14:28:00" },
//   ...
// ]

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'Este endpoint solo acepta peticiones GET'], 405);
}


// ── Parámetro ?n= ─────────────────────────────────────────────────────────────
// Cuántas lecturas devolver.
// max(1, ...) garantiza que sea al menos 1 (evita LIMIT 0 o negativos)
$n = isset($_GET['n']) ? max(1, (int) $_GET['n']) : 100;

$pdo  = get_conn();

$stmt = $pdo->prepare("
    SELECT   id, Camara, Temp, FechaHora
    FROM     Datos
    ORDER    BY FechaHora DESC    -- más reciente primero
    LIMIT    :n
");

// ⚠️ bindValue con PDO::PARAM_INT es OBLIGATORIO para LIMIT
// Si usás execute([':n' => $n]), PDO lo trata como string y MySQL falla.
// Con bindValue(..., PDO::PARAM_INT) le decimos explícitamente que es entero.
$stmt->bindValue(':n', $n, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();


// ── Formatear tipos ───────────────────────────────────────────────────────────
$resultado = array_map(fn($r) => [
    'id'        => (int)   $r['id'],
    'Camara'    => (int)   $r['Camara'],
    'Temp'      => (float) $r['Temp'],       // float para mantener el decimal: 3.5 no 3
    'FechaHora' => $r['FechaHora'],          // string: "2024-06-01 14:30:00"
], $rows);

respond($resultado);
