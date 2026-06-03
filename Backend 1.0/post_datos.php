<?php
// ══════════════════════════════════════════════════════════════════════════════
// api/post_datos.php — Recibe y guarda una lectura de temperatura del ESP32
// ══════════════════════════════════════════════════════════════════════════════
//
// Método HTTP: POST
// URL:         http://localhost/camaras/api/post_datos.php
//
// Body JSON que debe enviar el ESP32:
//   Mínimo:    { "Camara": 1, "Temp": 3.5 }
//   Completo:  { "Camara": 1, "Temp": 3.5, "FechaHora": "2024-06-01 14:30:00" }
//
//   → "FechaHora" es OPCIONAL. Si no viene, se usa la hora actual del servidor.
//
// Respuesta exitosa (HTTP 201 Created):
//   { "id": 42, "message": "Lectura guardada correctamente" }
//
// ── Ejemplo en Arduino (ESP32): ──────────────────────────────────────────────
//   HTTPClient http;
//   http.begin("http://192.168.1.105/camaras/api/post_datos.php");
//   http.addHeader("Content-Type", "application/json");
//   String body = "{\"Camara\": 1, \"Temp\": " + String(temperatura, 1) + "}";
//   int httpCode = http.POST(body);
//   http.end();

// ── Incluir dependencias ──────────────────────────────────────────────────────
// __DIR__ es la carpeta donde está ESTE archivo (api/)
// '/../config/db.php' sube un nivel (a camaras/) y entra a config/
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';


// ── Verificar método HTTP ─────────────────────────────────────────────────────
// Solo aceptamos POST. Si alguien abre este URL en el navegador (GET), rechazamos.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Este endpoint solo acepta peticiones POST'], 405);
}


// ── Leer el cuerpo JSON de la petición ───────────────────────────────────────
$body = get_json_body();


// ── Validar campos obligatorios ───────────────────────────────────────────────
// Verificamos que vengan tanto "Camara" como "Temp"
// isset() devuelve false si la clave no existe o si su valor es null
if (!isset($body['Camara']) || !isset($body['Temp'])) {
    respond([
        'error'    => 'Faltan campos requeridos',
        'requeridos' => ['Camara', 'Temp'],
        'recibidos'  => array_keys($body)   // Muestra qué campos sí llegaron (útil para debug)
    ], 422);
}


// ── Convertir y sanitizar los valores ────────────────────────────────────────
// (int) convierte a entero: "1" → 1, "1.9" → 1 (trunca)
// (float) convierte a decimal: "3.5" → 3.5, "4" → 4.0
$camara    = (int)   $body['Camara'];
$temp      = (float) $body['Temp'];

// Si el ESP32 no envía fecha, usamos la hora actual del servidor XAMPP
// date('Y-m-d H:i:s') genera algo como "2024-06-01 14:30:00"
// El operador ?? significa "si el lado izquierdo es null, usa el derecho"
$fechaHora = isset($body['FechaHora']) ? $body['FechaHora'] : date('Y-m-d H:i:s');


// ── Insertar en la base de datos ──────────────────────────────────────────────
$pdo = get_conn();   // Abre (o reutiliza) la conexión PDO

// prepare() crea la consulta con "placeholders" (:camara, :temp, :fecha)
// ¿Por qué no insertar los valores directamente en el string SQL?
// Porque si alguien envía un Camara malicioso como "1; DROP TABLE Datos;--",
// podría borrar toda la base de datos. Los placeholders lo previenen: PDO
// trata el valor como dato, nunca como código SQL.
$stmt = $pdo->prepare(
    "INSERT INTO Datos (Camara, Temp, FechaHora)
     VALUES (:camara, :temp, :fecha)"
);

// execute() reemplaza cada :placeholder con el valor real y ejecuta la query
$stmt->execute([
    ':camara' => $camara,
    ':temp'   => $temp,
    ':fecha'  => $fechaHora,
]);


// ── Responder con éxito ───────────────────────────────────────────────────────
// lastInsertId() devuelve el "id" que MySQL le asignó a la fila recién creada
respond([
    'id'      => (int) $pdo->lastInsertId(),
    'message' => 'Lectura guardada correctamente',
], 201);   // 201 Created: estándar HTTP para "se creó un recurso nuevo"
