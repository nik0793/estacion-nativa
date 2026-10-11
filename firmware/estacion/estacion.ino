// Estación Nativa — firmware v1.0 para ESP32-CAM (AI-Thinker)
// Un solo sketch para los tres tipos de estación. Ver docs/informe-tecnico.html, sección 9.
// Placa: "AI Thinker ESP32-CAM" · Partition Scheme: "Huge APP (3MB No OTA/1MB SPIFFS)" · 115200
// Librerías: solo las del paquete esp32 (3.x): esp_camera, WiFi, WiFiClientSecure, HTTPClient, LittleFS.

#include "esp_camera.h"
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <LittleFS.h>
#include <time.h>
#include "esp_sleep.h"

// ------------------------------------------------------------------ CONFIGURACIÓN POR ESTACIÓN
#define BASE      0
#define MAESTRA   1
#define COMEDERO  2

#define ID_ESTACION      "estanque-1"          // único por estación: estanque-1, estanque-2, comedero-1...
#define TIPO_ESTACION    BASE                  // BASE, MAESTRA o COMEDERO
#define WIFI_SSID        "estacion-nativa"     // la red del módem (o la de tu casa para probar)
#define WIFI_CLAVE       "cambiar-esta-clave"
#define SERVIDOR         "https://skyblue-gorilla-834934.hostingersite.com"  // sin barra final; http:// también sirve para probar
#define CLAVE_API        "cambiar-esta-clave-api"   // la misma que en servidor/config.php
#define HORA_INICIO_PIR  6                     // el PIR despierta la estación entre estas horas
#define HORA_FIN_PIR     20
#define MINUTO_VENTANA   1                     // minuto de la hora en que las estaciones buscan WiFi
#define MINUTOS_MODEM    6                     // la maestra mantiene el módem encendido este tiempo
#define PILA_MINIMA_MV   3500                  // por debajo no saca fotos, solo reporta estado
#define ZONA_HORARIA     "<-03>3"              // Argentina
#define MAX_FLASH_BYTES  (1400UL * 1024UL)     // espacio máximo para fotos pendientes
#define VERSION_FW       "1.0"

// ------------------------------------------------------------------ PINES
#define PIN_PIR          13
#define PIN_PILA         15
#define PIN_MODEM        4      // maestra: gate del AOD4184 (también enciende el LED de flash)
#define PIN_SERVO        12     // comedero: señal del SG90
#define PIN_SERVO_LLAVE  2      // comedero: gate del segundo AOD4184
#define PIN_BOYA         14     // comedero: boya del tanque, a GND con pull-up interno

// Cámara AI-Thinker
#define PWDN_GPIO_NUM  32
#define RESET_GPIO_NUM -1
#define XCLK_GPIO_NUM   0
#define SIOD_GPIO_NUM  26
#define SIOC_GPIO_NUM  27
#define Y9_GPIO_NUM    35
#define Y8_GPIO_NUM    34
#define Y7_GPIO_NUM    39
#define Y6_GPIO_NUM    36
#define Y5_GPIO_NUM    21
#define Y4_GPIO_NUM    19
#define Y3_GPIO_NUM    18
#define Y2_GPIO_NUM     5
#define VSYNC_GPIO_NUM 25
#define HREF_GPIO_NUM  23
#define PCLK_GPIO_NUM  22

// ------------------------------------------------------------------ ESTADO QUE SOBREVIVE AL DEEP SLEEP
RTC_DATA_ATTR int  ultimaVentanaHora = -1;      // para no repetir la ventana en la misma hora
RTC_DATA_ATTR bool horaValida = false;          // true después del primer NTP
RTC_DATA_ATTR int  horasComida[4] = {8, 17, -1, -1};
RTC_DATA_ATTR uint32_t contadorArranques = 0;

String causaTexto = "encendido";
int pilaMv = 0;

// ------------------------------------------------------------------ UTILIDADES
bool tiempoOk() {
  time_t ahora = time(nullptr);
  return ahora > 1700000000;   // posterior a 2023
}

void ahoraLocal(struct tm &t) {
  time_t ahora = time(nullptr);
  localtime_r(&ahora, &t);
}

bool esDeDia(int hora) { return hora >= HORA_INICIO_PIR && hora < HORA_FIN_PIR; }

bool horaConVentana(int hora) { return hora == 0 || (hora >= 7 && hora <= 20); }

int leerPilaMv() {
  analogSetPinAttenuation(PIN_PILA, ADC_11db);
  uint32_t suma = 0;
  for (int i = 0; i < 8; i++) { suma += analogReadMilliVolts(PIN_PILA); delay(2); }
  return (int)(suma / 8) * 2;   // divisor 100k/100k
}

String marcaTiempo() {
  struct tm t;
  if (!tiempoOk()) return "sinhora_" + String(contadorArranques) + "_" + String(millis());
  ahoraLocal(t);
  char b[20];
  strftime(b, sizeof(b), "%Y%m%d_%H%M%S", &t);
  return String(b);
}

String tomadaDesdeNombre(const String &nombre) {
  // /f_estanque-1_20261011_153012_1.jpg -> 2026-10-11 15:30:12
  int p = nombre.indexOf("_20");
  if (p < 0 || nombre.length() < p + 16) return "";
  String f = nombre.substring(p + 1, p + 16);  // 20261011_153012
  return f.substring(0, 4) + "-" + f.substring(4, 6) + "-" + f.substring(6, 8) + " " +
         f.substring(9, 11) + ":" + f.substring(11, 13) + ":" + f.substring(13, 15);
}

// ------------------------------------------------------------------ CÁMARA
bool iniciarCamara() {
  camera_config_t c;
  c.ledc_channel = LEDC_CHANNEL_0;
  c.ledc_timer   = LEDC_TIMER_0;
  c.pin_d0 = Y2_GPIO_NUM;  c.pin_d1 = Y3_GPIO_NUM;  c.pin_d2 = Y4_GPIO_NUM;  c.pin_d3 = Y5_GPIO_NUM;
  c.pin_d4 = Y6_GPIO_NUM;  c.pin_d5 = Y7_GPIO_NUM;  c.pin_d6 = Y8_GPIO_NUM;  c.pin_d7 = Y9_GPIO_NUM;
  c.pin_xclk = XCLK_GPIO_NUM; c.pin_pclk = PCLK_GPIO_NUM; c.pin_vsync = VSYNC_GPIO_NUM; c.pin_href = HREF_GPIO_NUM;
  c.pin_sccb_sda = SIOD_GPIO_NUM; c.pin_sccb_scl = SIOC_GPIO_NUM;
  c.pin_pwdn = PWDN_GPIO_NUM; c.pin_reset = RESET_GPIO_NUM;
  c.xclk_freq_hz = 20000000;
  c.pixel_format = PIXFORMAT_JPEG;
  c.frame_size   = FRAMESIZE_SVGA;     // 800x600
  c.jpeg_quality = 12;                 // 0 mejor .. 63 peor
  c.fb_count     = 1;
  c.fb_location  = psramFound() ? CAMERA_FB_IN_PSRAM : CAMERA_FB_IN_DRAM;
  c.grab_mode    = CAMERA_GRAB_LATEST;
  if (esp_camera_init(&c) != ESP_OK) { Serial.println("camara: error de inicio"); return false; }
  sensor_t *s = esp_camera_sensor_get();
  if (s) { s->set_vflip(s, 0); s->set_hmirror(s, 0); s->set_brightness(s, 0); }
  // descartar los primeros cuadros (exposición)
  for (int i = 0; i < 2; i++) { camera_fb_t *fb = esp_camera_fb_get(); if (fb) esp_camera_fb_return(fb); delay(80); }
  return true;
}

void liberarEspacio() {
  while (LittleFS.usedBytes() > MAX_FLASH_BYTES) {
    File raiz = LittleFS.open("/");
    String masVieja = "";
    File f = raiz.openNextFile();
    while (f) {
      String n = String("/") + f.name();
      if (n.startsWith("/f_") && (masVieja == "" || n < masVieja)) masVieja = n;
      f = raiz.openNextFile();
    }
    if (masVieja == "") break;
    LittleFS.remove(masVieja);
    Serial.println("borrada por espacio: " + masVieja);
  }
}

bool guardarFoto(int numero, const String &marca) {
  camera_fb_t *fb = esp_camera_fb_get();
  if (!fb) { Serial.println("camara: sin cuadro"); return false; }
  liberarEspacio();
  String nombre = "/f_" ID_ESTACION "_" + marca + "_" + String(numero) + ".jpg";
  File f = LittleFS.open(nombre, FILE_WRITE);
  bool ok = false;
  if (f) { ok = f.write(fb->buf, fb->len) == fb->len; f.close(); }
  esp_camera_fb_return(fb);
  Serial.println(String(ok ? "foto guardada " : "foto ERROR ") + nombre + " (" + String(fb->len) + " bytes)");
  return ok;
}

void sacarFotos() {
  if (!iniciarCamara()) return;
  String marca = marcaTiempo();
  guardarFoto(1, marca);
  delay(1500);
  guardarFoto(2, marca);
  esp_camera_deinit();
}

// ------------------------------------------------------------------ RED
bool conectarWifi(uint32_t topeMs) {
  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);
  WiFi.begin(WIFI_SSID, WIFI_CLAVE);
  uint32_t t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < topeMs) { delay(250); }
  if (WiFi.status() != WL_CONNECTED) { Serial.println("wifi: no encontrada"); return false; }
  Serial.println("wifi: " + WiFi.localIP().toString());
  return true;
}

void sincronizarHora() {
  configTzTime(ZONA_HORARIA, "pool.ntp.org", "time.google.com");
  uint32_t t0 = millis();
  while (!tiempoOk() && millis() - t0 < 10000) delay(200);
  horaValida = tiempoOk();
  struct tm t; ahoraLocal(t);
  Serial.printf("hora: %s %02d:%02d:%02d\n", horaValida ? "ok" : "SIN NTP", t.tm_hour, t.tm_min, t.tm_sec);
}

// Devuelve el código HTTP. cuerpo puede ser nullptr para GET.
int pedir(const String &ruta, const char *metodo, const uint8_t *cuerpo, size_t largo, const String &tipo, String &respuesta) {
  String url = String(SERVIDOR) + ruta;
  HTTPClient http;
  WiFiClientSecure segura;
  WiFiClient simple;
  bool https = url.startsWith("https");
  if (https) segura.setInsecure();   // v1: sin verificar certificado
  if (!(https ? http.begin(segura, url) : http.begin(simple, url))) return -1;
  http.setTimeout(20000);
  int codigo;
  if (strcmp(metodo, "POST") == 0) {
    http.addHeader("Content-Type", tipo);
    codigo = http.POST((uint8_t *)cuerpo, largo);
  } else {
    codigo = http.GET();
  }
  respuesta = codigo > 0 ? http.getString() : String("");
  http.end();
  return codigo;
}

bool subirArchivo(const String &nombre) {
  File f = LittleFS.open(nombre, FILE_READ);
  if (!f) return false;
  size_t largo = f.size();
  String limite = "----EstacionNativa7f3a";
  String cabeza = "--" + limite + "\r\nContent-Disposition: form-data; name=\"estacion\"\r\n\r\n" ID_ESTACION "\r\n"
                  "--" + limite + "\r\nContent-Disposition: form-data; name=\"clave\"\r\n\r\n" CLAVE_API "\r\n"
                  "--" + limite + "\r\nContent-Disposition: form-data; name=\"tomada\"\r\n\r\n" + tomadaDesdeNombre(nombre) + "\r\n"
                  "--" + limite + "\r\nContent-Disposition: form-data; name=\"archivo\"; filename=\"" + nombre.substring(1) + "\"\r\nContent-Type: image/jpeg\r\n\r\n";
  String cola = "\r\n--" + limite + "--\r\n";
  size_t total = cabeza.length() + largo + cola.length();
  uint8_t *buf = (uint8_t *)(psramFound() ? ps_malloc(total) : malloc(total));
  if (!buf) { f.close(); Serial.println("subir: sin memoria"); return false; }
  memcpy(buf, cabeza.c_str(), cabeza.length());
  f.read(buf + cabeza.length(), largo);
  f.close();
  memcpy(buf + cabeza.length() + largo, cola.c_str(), cola.length());
  String resp;
  int codigo = pedir("/api/foto.php", "POST", buf, total, "multipart/form-data; boundary=" + limite, resp);
  free(buf);
  Serial.println("subida " + nombre + " -> " + String(codigo));
  return codigo == 200;
}

int subirPendientes() {
  int pendientes = 0;
  File raiz = LittleFS.open("/");
  File f = raiz.openNextFile();
  std::vector<String> nombres;
  while (f) { String n = String("/") + f.name(); if (n.startsWith("/f_")) nombres.push_back(n); f = raiz.openNextFile(); }
  for (auto &n : nombres) {
    if (subirArchivo(n)) LittleFS.remove(n); else pendientes++;
  }
  return pendientes;
}

String estadoTanque() {
#if TIPO_ESTACION == COMEDERO
  return digitalRead(PIN_BOYA) == LOW ? "ok" : "bajo";   // boya cerrada a GND = tanque ok
#else
  return "na";
#endif
}

void enviarEstado(int pendientes) {
  String json = "{\"estacion\":\"" ID_ESTACION "\",\"clave\":\"" CLAVE_API "\",\"pila_mv\":" + String(pilaMv) +
                ",\"pendientes\":" + String(pendientes) + ",\"tanque\":\"" + estadoTanque() + "\",\"causa\":\"" + causaTexto +
                "\",\"version\":\"" VERSION_FW "\",\"arranques\":" + String(contadorArranques) + "}";
  String resp;
  int codigo = pedir("/api/estado.php", "POST", (const uint8_t *)json.c_str(), json.length(), "application/json", resp);
  Serial.println("estado -> " + String(codigo));
}

void darComida() {
#if TIPO_ESTACION == COMEDERO
  Serial.println("comida: dosificando");
  pinMode(PIN_SERVO_LLAVE, OUTPUT); digitalWrite(PIN_SERVO_LLAVE, HIGH);
  delay(100);
  ledcAttach(PIN_SERVO, 50, 16);           // 50 Hz, 16 bits: 20 ms = 65535
  ledcWrite(PIN_SERVO, 4915);              // 1,5 ms  = compuerta abierta (90°)
  delay(1000);
  ledcWrite(PIN_SERVO, 1638);              // 0,5 ms  = cerrada (0°)
  delay(1000);
  ledcDetach(PIN_SERVO);
  pinMode(PIN_SERVO, INPUT);
  digitalWrite(PIN_SERVO_LLAVE, LOW);
#endif
}

void ejecutarOrdenes() {
  String resp;
  int codigo = pedir(String("/api/ordenes.php?estacion=" ID_ESTACION "&clave=" CLAVE_API), "GET", nullptr, 0, "", resp);
  Serial.println("ordenes -> " + String(codigo) + " " + resp);
  if (codigo != 200) return;
  // Respuesta: [{"id":12,"tipo":"comida"},{"id":13,"tipo":"foto"},{"id":14,"tipo":"horario","valor":"8,17"}]
  int desde = 0;
  bool fotoAhora = false;
  while (true) {
    int p = resp.indexOf("\"tipo\":\"", desde);
    if (p < 0) break;
    int q = resp.indexOf("\"", p + 8);
    String tipo = resp.substring(p + 8, q);
    desde = q;
    if (tipo == "comida") darComida();
    else if (tipo == "foto") fotoAhora = true;
    else if (tipo == "horario") {
      int v = resp.indexOf("\"valor\":\"", q);
      if (v > 0) {
        int w = resp.indexOf("\"", v + 9);
        String lista = resp.substring(v + 9, w);
        for (int i = 0; i < 4; i++) horasComida[i] = -1;
        int i = 0, ini = 0;
        while (i < 4 && ini < (int)lista.length()) {
          int coma = lista.indexOf(',', ini); if (coma < 0) coma = lista.length();
          horasComida[i++] = lista.substring(ini, coma).toInt();
          ini = coma + 1;
        }
      }
    }
  }
  if (fotoAhora) { sacarFotos(); subirPendientes(); }
}

void ventana(int hora) {
  Serial.println("ventana: inicio");
#if TIPO_ESTACION == MAESTRA
  pinMode(PIN_MODEM, OUTPUT); digitalWrite(PIN_MODEM, HIGH);
  Serial.println("modem: encendido, esperando 45 s");
  delay(45000);
#endif
  uint32_t t0 = millis();
  if (conectarWifi(90000)) {
    sincronizarHora();
    int pendientes = subirPendientes();
    enviarEstado(pendientes);
    ejecutarOrdenes();
#if TIPO_ESTACION == COMEDERO
    for (int i = 0; i < 4; i++) if (horasComida[i] == hora) darComida();
#endif
    WiFi.disconnect(true);
    WiFi.mode(WIFI_OFF);
  }
  ultimaVentanaHora = hora;
#if TIPO_ESTACION == MAESTRA
  // mantener el módem encendido para que las otras estaciones suban lo suyo
  uint32_t fin = 45000UL + (uint32_t)MINUTOS_MODEM * 60000UL;
  while (millis() - t0 + 45000UL < fin) delay(500);
  digitalWrite(PIN_MODEM, LOW);
  Serial.println("modem: apagado");
#endif
  Serial.println("ventana: fin");
}

// ------------------------------------------------------------------ SUEÑO
void dormir() {
  uint64_t segundos;
  bool pirActivo;
  if (!tiempoOk()) {
    segundos = 600;          // sin hora: reintentar cada 10 minutos
    pirActivo = true;
  } else {
    struct tm t; ahoraLocal(t);
    int ahoraSeg = t.tm_hour * 3600 + t.tm_min * 60 + t.tm_sec;
    int objetivoMin = (TIPO_ESTACION == MAESTRA) ? 0 : MINUTO_VENTANA;
    // próxima hora con ventana
    int h = t.tm_hour, intentos = 0, objetivo = -1;
    while (intentos < 25) {
      int candidato = h * 3600 + objetivoMin * 60 + 5;
      if (horaConVentana(h) && candidato > ahoraSeg + 30 && h != ultimaVentanaHora) { objetivo = candidato; break; }
      h = (h + 1) % 24; intentos++;
      if (h == 0) ahoraSeg -= 86400;   // pasó la medianoche
    }
    if (objetivo < 0) objetivo = ahoraSeg + 3600;
    // si es de noche, despertar también al inicio del horario del PIR
    int inicioPir = HORA_INICIO_PIR * 3600;
    pirActivo = esDeDia(t.tm_hour);
    if (!pirActivo) {
      int faltaPir = inicioPir - (t.tm_hour * 3600 + t.tm_min * 60 + t.tm_sec);
      if (faltaPir <= 0) faltaPir += 86400;
      if (faltaPir < objetivo - ahoraSeg) objetivo = ahoraSeg + faltaPir;
    }
    segundos = (uint64_t)(objetivo - ahoraSeg);
    if (segundos < 30) segundos = 30;
    if (segundos > 4 * 3600) segundos = 4 * 3600;
  }
  Serial.printf("durmiendo %llu s, PIR %s\n", segundos, pirActivo ? "activo" : "apagado");
  Serial.flush();
  esp_sleep_enable_timer_wakeup(segundos * 1000000ULL);
  if (pirActivo) esp_sleep_enable_ext0_wakeup(GPIO_NUM_13, 1);
  esp_deep_sleep_start();
}

// ------------------------------------------------------------------ PROGRAMA
void setup() {
  Serial.begin(115200);
  delay(200);
  contadorArranques++;
  setenv("TZ", ZONA_HORARIA, 1); tzset();

  pinMode(PIN_MODEM, OUTPUT); digitalWrite(PIN_MODEM, LOW);        // LED de flash y módem apagados
  pinMode(PIN_SERVO_LLAVE, OUTPUT); digitalWrite(PIN_SERVO_LLAVE, LOW);
  pinMode(PIN_PIR, INPUT);
#if TIPO_ESTACION == COMEDERO
  pinMode(PIN_BOYA, INPUT_PULLUP);
#endif

  esp_sleep_wakeup_cause_t causa = esp_sleep_get_wakeup_cause();
  if (causa == ESP_SLEEP_WAKEUP_EXT0) causaTexto = "pir";
  else if (causa == ESP_SLEEP_WAKEUP_TIMER) causaTexto = "timer";

  pilaMv = leerPilaMv();            // antes del WiFi (ADC2)
  Serial.printf("\n[%s] arranque %lu causa=%s pila=%d mV\n", ID_ESTACION, (unsigned long)contadorArranques, causaTexto.c_str(), pilaMv);

  if (!LittleFS.begin(true)) Serial.println("LittleFS: error");
  else Serial.printf("LittleFS: %u de %u bytes usados\n", (unsigned)LittleFS.usedBytes(), (unsigned)LittleFS.totalBytes());

  struct tm t; ahoraLocal(t);
  int hora = tiempoOk() ? t.tm_hour : -1;
  int minuto = tiempoOk() ? t.tm_min : -1;

  if (causa == ESP_SLEEP_WAKEUP_EXT0) {
    if (pilaMv >= PILA_MINIMA_MV && (hora < 0 || esDeDia(hora))) sacarFotos();
    else Serial.println("pir: ignorado (pila baja o de noche)");
  }

  bool tocaVentana;
  if (!tiempoOk()) tocaVentana = true;                                   // sin hora: intentar ahora
  else if (TIPO_ESTACION == MAESTRA) tocaVentana = horaConVentana(hora) && minuto <= 3 && hora != ultimaVentanaHora;
  else tocaVentana = horaConVentana(hora) && minuto >= MINUTO_VENTANA && minuto <= MINUTO_VENTANA + 4 && hora != ultimaVentanaHora;

  if (tocaVentana) ventana(hora);

  dormir();
}

void loop() {}
