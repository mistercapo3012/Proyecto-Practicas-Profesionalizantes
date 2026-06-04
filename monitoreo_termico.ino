/*
  ════════════════════════════════════════════════════════════════════════════
  monitoreo_termico.ino — Sistema de monitoreo para cámaras frigoríficas
  ════════════════════════════════════════════════════════════════════════════

  ¿Qué hace este programa?
    1. Lee la temperatura del sensor DHT11 cada INTERVALO_MS milisegundos
    2. Verifica si está dentro del rango permitido (TEMP_MIN a TEMP_MAX)
    3. Muestra el resultado en el Monitor Serie (para debug)
    4. Envía los datos al servidor XAMPP mediante HTTP POST

  ¿Qué envía al servidor?
    Body JSON:  { "Camara": 1, "Temp": 3.5 }
    Endpoint:   POST /camaras/api/post_datos.php
    Puerto:     80 (HTTP estándar de Apache/XAMPP)

  Hardware requerido:
    - Arduino Uno (o compatible)
    - Ethernet Shield W5100/W5500 (se conecta encima del Arduino)
    - Sensor DHT11 (pin DATA al pin digital 2 del Arduino)

  ⚠️  ANTES DE CARGAR EL SKETCH, revisá la sección "CONFIGURACIÓN" y ajustá:
    - NUMERO_CAMARA: número único por cada Arduino instalado (1, 2, 3...)
    - ip[]:          IP fija del Arduino en tu red
    - servidor[]:    IP de la PC donde corre XAMPP
  ════════════════════════════════════════════════════════════════════════════
*/

// ── Librerías ────────────────────────────────────────────────────────────────
#include <SPI.h>       // Comunicación SPI: el Ethernet Shield la usa para hablar con el Arduino
#include <Ethernet.h>  // Funciones de red: conectarse, enviar HTTP, etc.
#include <DHT.h>       // Leer temperatura y humedad del sensor DHT11

// ════════════════════════════════════════════════════════════════════════════
//  CONFIGURACIÓN — ajustá estos valores antes de cargar el sketch
// ════════════════════════════════════════════════════════════════════════════

// ── Identificador de esta unidad ─────────────────────────────────────────────
// Cada Arduino instalado en el sistema debe tener un número diferente.
// Este número le indica al servidor a qué cámara corresponde la lectura.
// Cámara 1 → NUMERO_CAMARA 1 | Cámara 2 → NUMERO_CAMARA 2 | etc.
#define NUMERO_CAMARA  1

// ── Sensor DHT11 ─────────────────────────────────────────────────────────────
#define DHTPIN  2        // Pin digital del Arduino donde está el cable DATA del DHT11
#define DHTTYPE DHT11    // Modelo del sensor (DHT11 o DHT22 si se cambia en el futuro)

// ── Red: dirección MAC del Ethernet Shield ───────────────────────────────────
// La MAC está impresa en el sticker del shield.
// Si no tiene sticker, este valor genérico funciona en redes locales privadas.
byte mac[] = { 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED };

// ── Red: IP fija del Arduino ──────────────────────────────────────────────────
// Asigná una IP libre en el rango de tu red local.
// Ejemplo: si tu router usa 192.168.1.x, usá algo como 192.168.1.50
// Tip: podés ver el rango ejecutando "ipconfig" en CMD de Windows.
IPAddress ip(192, 168, 1, 50);

// ── Red: IP del servidor (PC donde corre XAMPP) ───────────────────────────────
// ⚠️  Este es el dato más importante a cambiar.
// Abrí CMD en la PC del servidor y ejecutá "ipconfig" → buscá "Dirección IPv4"
IPAddress servidor(192, 168, 1, 100);

// Puerto 80: es el puerto estándar de HTTP, el que usa Apache (XAMPP) por defecto
const int  PUERTO_SERVIDOR = 80;

// Endpoint completo donde el PHP recibe los datos
// Formato: /[carpeta en htdocs]/[subcarpeta api]/[archivo].php
const char* ENDPOINT = "/camaras/api/post_datos.php";

// ── Rango de temperatura permitido ───────────────────────────────────────────
// Estos valores solo se usan para mostrar el estado en el Monitor Serie.
// El frontend (Historial.html) aplica los mismos rangos para colorear la tabla.
const float TEMP_MIN = 0.0;    // por debajo de 0°C → ALERTA BAJA
const float TEMP_MAX = 10.0;   // por encima de 10°C → ALERTA ALTA

// ── Intervalo de envío ───────────────────────────────────────────────────────
// Cada cuánto tiempo (en milisegundos) se lee el sensor y se envía al servidor.
// 10000 ms = 10 segundos. Podés aumentarlo para no saturar la base de datos.
const long INTERVALO_MS = 10000;

// ════════════════════════════════════════════════════════════════════════════
//  Variables globales (no modificar)
// ════════════════════════════════════════════════════════════════════════════

DHT           dht(DHTPIN, DHTTYPE); // Objeto que representa el sensor
EthernetClient cliente;             // Objeto que maneja la conexión TCP al servidor
unsigned long ultimoEnvio = 0;      // Marca de tiempo del último envío exitoso

// ════════════════════════════════════════════════════════════════════════════
//  setup() — se ejecuta UNA SOLA VEZ al encender o resetear el Arduino
// ════════════════════════════════════════════════════════════════════════════

void setup() {
  // Abrir el puerto serie a 9600 baudios (para ver mensajes en el Monitor Serie)
  Serial.begin(9600);
  Serial.println();
  Serial.println("=================================================");
  Serial.println("  SISTEMA DE MONITOREO — CAMARA FRIGORIFICA");
  Serial.println("  Camara #" + String(NUMERO_CAMARA));
  Serial.println("=================================================");

  // ── Iniciar el sensor DHT11 ─────────────────────────────────────────────
  dht.begin();
  Serial.println("[DHT11] Sensor iniciado en pin " + String(DHTPIN));

  // ── Iniciar el Ethernet Shield con IP fija ──────────────────────────────
  // Alternativa: Ethernet.begin(mac) para obtener IP automática por DHCP
  // (requiere que el router asigne IPs automáticamente, que suele ser el caso)
  Ethernet.begin(mac, ip);
  delay(1000); // Pequeña pausa para que el shield inicialice la conexión

  Serial.print("[RED] IP del Arduino: ");
  Serial.println(Ethernet.localIP());
  Serial.print("[RED] Servidor destino: ");
  Serial.print(servidor);
  Serial.print(":");
  Serial.println(PUERTO_SERVIDOR);
  Serial.print("[RED] Endpoint: ");
  Serial.println(ENDPOINT);
  Serial.println("-------------------------------------------------");
  Serial.println("Enviando datos cada " + String(INTERVALO_MS / 1000) + " segundos...");
  Serial.println("=================================================");
}

// ════════════════════════════════════════════════════════════════════════════
//  loop() — se ejecuta CONTINUAMENTE después de setup()
// ════════════════════════════════════════════════════════════════════════════

void loop() {

  // millis() devuelve los milisegundos transcurridos desde que el Arduino se encendió.
  // Usamos la diferencia con ultimoEnvio en vez de delay() para no bloquear el programa.
  // Con delay(), el Arduino no puede hacer nada más mientras espera.
  // Con millis(), podría atender otras tareas en ese tiempo (aunque acá no las hay).
  unsigned long ahora = millis();

  if (ahora - ultimoEnvio >= INTERVALO_MS) {
    ultimoEnvio = ahora; // Actualizar la marca de tiempo

    // ── Paso 1: Leer el sensor ───────────────────────────────────────────
    float temperatura = dht.readTemperature(); // En grados Celsius
    float humedad     = dht.readHumidity();    // En % (solo para mostrar en Serie)

    // ── Paso 2: Verificar que la lectura sea válida ──────────────────────
    // isnan() = "is not a number": el DHT devuelve NaN si hay un problema físico
    // (cable suelto, sensor dañado, tiempo de lectura insuficiente, etc.)
    if (isnan(temperatura) || isnan(humedad)) {
      Serial.println("[ERROR] No se pudo leer el DHT11. Verificar conexiones.");
      return; // Saltear este ciclo, intentar de nuevo en el próximo INTERVALO_MS
    }

    // ── Paso 3: Determinar el estado para el Monitor Serie ───────────────
    String estado = determinarEstado(temperatura);

    // ── Paso 4: Mostrar en el Monitor Serie (Herramientas > Monitor Serie) ──
    Serial.println();
    Serial.println("--- Nueva lectura ---");
    Serial.print("  Camara      : #"); Serial.println(NUMERO_CAMARA);
    Serial.print("  Temperatura : "); Serial.print(temperatura, 1); Serial.println(" C");
    Serial.print("  Humedad     : "); Serial.print(humedad, 1);     Serial.println(" %");
    Serial.print("  Estado      : "); Serial.println(estado);

    // ── Paso 5: Enviar los datos al servidor ─────────────────────────────
    enviarDatos(temperatura);
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  determinarEstado() — clasifica la temperatura en OK / ALERTA
// ════════════════════════════════════════════════════════════════════════════

String determinarEstado(float temp) {
  if (temp < TEMP_MIN) {
    return "ALERTA_BAJA  ⚠  Temperatura bajo el mínimo permitido";
  } else if (temp > TEMP_MAX) {
    return "ALERTA_ALTA  ⚠  Temperatura sobre el máximo permitido";
  } else {
    return "OK  ✓  Dentro del rango";
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  enviarDatos() — construye y envía el HTTP POST al servidor XAMPP
// ════════════════════════════════════════════════════════════════════════════

void enviarDatos(float temperatura) {

  // ── Construir el body JSON ───────────────────────────────────────────────
  // El servidor PHP espera exactamente este formato:
  //   { "Camara": 1, "Temp": 3.5 }
  //
  // String(temperatura, 1) convierte el float a texto con 1 decimal: 3.5, -0.3, 10.0
  // \"  dentro de un String en Arduino representa las comillas del JSON
  String json = "{\"Camara\":" + String(NUMERO_CAMARA) + ",\"Temp\":" + String(temperatura, 1) + "}";

  Serial.print("  [HTTP] Conectando con " + ipToString(servidor) + ":" + String(PUERTO_SERVIDOR) + " ... ");

  // ── Intentar conectar con el servidor ───────────────────────────────────
  // cliente.connect() devuelve true si la conexión TCP fue exitosa
  if (cliente.connect(servidor, PUERTO_SERVIDOR)) {

    Serial.print("conectado. Enviando JSON... ");

    // ── Enviar la petición HTTP POST ─────────────────────────────────────
    // Un HTTP POST tiene este formato exacto (cada println agrega \r\n al final):
    //
    //   POST /camaras/api/post_datos.php HTTP/1.1
    //   Host: 192.168.1.100
    //   Content-Type: application/json
    //   Content-Length: 22
    //   Connection: close
    //                          ← línea en blanco OBLIGATORIA (separa headers del body)
    //   {"Camara":1,"Temp":3.5}

    cliente.println("POST " + String(ENDPOINT) + " HTTP/1.1");
    cliente.println("Host: " + ipToString(servidor));
    cliente.println("Content-Type: application/json");
    cliente.println("Content-Length: " + String(json.length()));
    cliente.println("Connection: close");
    cliente.println();        // ← línea en blanco obligatoria
    cliente.println(json);    // ← el body JSON

    // ── Leer la primera línea de respuesta del servidor ──────────────────
    // No necesitamos leer toda la respuesta, solo verificar que llegó algo.
    delay(500); // Dar tiempo al servidor para procesar y responder
    if (cliente.available()) {
      // Ejemplo de respuesta exitosa: "HTTP/1.1 201 Created"
      String respuesta = cliente.readStringUntil('\n');
      Serial.println("Respuesta: " + respuesta);
    } else {
      Serial.println("enviado (sin respuesta del servidor).");
    }

    // Cerrar la conexión TCP (buena práctica: no dejar conexiones abiertas)
    cliente.stop();

  } else {
    // La conexión falló: verificar IP del servidor, que XAMPP esté corriendo,
    // y que ambos dispositivos estén en la misma red.
    Serial.println("FALLO. Verificar IP del servidor y que XAMPP esté activo.");
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  ipToString() — convierte IPAddress a String para los headers HTTP
// ════════════════════════════════════════════════════════════════════════════

// IPAddress es un tipo especial de Arduino. Los headers HTTP necesitan
// la IP como texto ("192.168.1.100"), no como objeto IPAddress.
String ipToString(IPAddress addr) {
  return String(addr[0]) + "." + String(addr[1]) + "." +
         String(addr[2]) + "." + String(addr[3]);
}
