#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>
#include <ArduinoJson.h>

// ===== WIFI =====
const char* ssid = "HUAWEI-2.4G-B5zG";
const char* password = "lathifah01";

// ===== API =====
const char* serverName = "http://192.168.100.91/iot-sawi/post-esp-data.php";
const char* pumpApiUrl = "http://192.168.100.91/iot-sawi/pump_api.php";
String apiKeyValue = "12345abcde";

// ===== PIN =====
#define DHTPIN 4
#define DHTTYPE DHT22
#define SOIL_PIN 34
#define LDR_PIN 35
#define RELAY_PIN 18

// ===== GLOBAL =====
String controlMode = "auto";
bool manualStatus = false;
int soilThreshold = 40;

DHT dht(DHTPIN, DHTTYPE);

void setup() {
  Serial.begin(115200);

  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, HIGH); // OFF (relay aktif LOW)

  WiFi.begin(ssid, password);
  Serial.print("Menghubungkan WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi Connected!");
  Serial.print("IP ESP32: ");
  Serial.println(WiFi.localIP());

  dht.begin();
  delay(2000);
}

void loop() {
  if (WiFi.status() == WL_CONNECTED) {

    // =========================
    // 1. AMBIL DATA DARI WEB
    // =========================
    HTTPClient http;
    String url = String(pumpApiUrl) + "?action=get_status&api_key=" + apiKeyValue;

    http.begin(url);
    int httpCode = http.GET();

    if (httpCode == 200) {
      String payload = http.getString();
      payload.trim();

      // Bersihkan BOM
      if (payload.length() > 3 && (uint8_t)payload[0] == 0xEF) {
        payload = payload.substring(3);
      }

      Serial.println("=== RESPONSE WEB ===");
      Serial.println(payload);

      DynamicJsonDocument doc(256);
      DeserializationError error = deserializeJson(doc, payload);

      if (!error) {
        controlMode = doc["control_mode"].as<String>();
        manualStatus = doc["manual_status"];
        soilThreshold = doc["soil_threshold"];

        Serial.println("=== KONTROL DARI WEB ===");
        Serial.print("Mode: "); Serial.println(controlMode);
        Serial.print("Manual: "); Serial.println(manualStatus ? "ON" : "OFF");
        Serial.print("Threshold: "); Serial.println(soilThreshold);
      } else {
        Serial.print("JSON ERROR: ");
        Serial.println(error.c_str());
      }

    } else {
      Serial.print("HTTP ERROR get_status: ");
      Serial.println(httpCode);
    }
    http.end();

    // =========================
    // 2. BACA SENSOR
    // =========================
    float suhu = dht.readTemperature();
    float udara = dht.readHumidity();

    int soilRaw = analogRead(SOIL_PIN);
    float tanah = map(soilRaw, 4095, 1200, 0, 100);
    tanah = constrain(tanah, 0, 100);

    int ldrRaw = analogRead(LDR_PIN);
    float cahaya = map(ldrRaw, 0, 4095, 100, 0);
    cahaya = constrain(cahaya, 0, 100);

    // =========================
    // 3. LOGIKA POMPA
    // =========================
    bool pompaMenyala = false;
    String trigger = "";

    if (controlMode == "auto") {
      if (tanah < soilThreshold) {
        pompaMenyala = true;
        trigger = "auto_kering";
      } else {
        pompaMenyala = false;
        trigger = "auto_basah";
      }
    } else {
      pompaMenyala = manualStatus;
      trigger = pompaMenyala ? "manual_on" : "manual_off";
    }

    // =========================
    // 4. EKSEKUSI RELAY (AKTIF LOW)
    // =========================
    digitalWrite(RELAY_PIN, pompaMenyala ? LOW : HIGH);

    // =========================
    // 5. SERIAL MONITOR
    // =========================
    Serial.println("=== SENSOR ===");
    Serial.print("Tanah: "); Serial.print(tanah); Serial.println("%");
    Serial.print("Udara: "); Serial.print(udara); Serial.println("%");
    Serial.print("Suhu: "); Serial.print(suhu); Serial.println("C");
    Serial.print("Cahaya: "); Serial.print(cahaya); Serial.println("%");

    Serial.println("=== POMPA ===");
    Serial.print("Status: ");
    Serial.println(pompaMenyala ? "MENYALA 🔴" : "MATI 🟢");
    Serial.print("Trigger: ");
    Serial.println(trigger);

    // =========================
    // 6. UPDATE STATUS KE WEB
    // =========================
    HTTPClient http2;
    String url2 = String(pumpApiUrl) +
                  "?action=update_pump&api_key=" + apiKeyValue +
                  "&pump_status=" + String(pompaMenyala ? 1 : 0) +
                  "&soil_moisture=" + String(tanah, 1) +
                  "&control_mode=" + controlMode +
                  "&trigger=" + trigger;

    http2.begin(url2);
    int code2 = http2.GET();

    Serial.print("Update pump: ");
    Serial.println(code2);
    Serial.println(http2.getString());

    http2.end();

    // =========================
    // 7. KIRIM DATA SENSOR
    // =========================
    HTTPClient http3;
    http3.begin(serverName);
    http3.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String data = "api_key=" + apiKeyValue +
                  "&kelTanah=" + String(tanah, 1) +
                  "&kelUdara=" + String(udara, 1) +
                  "&suhuUdara=" + String(suhu, 1) +
                  "&kecerahan=" + String(cahaya, 0) +
                  "&latitude=-6.2" +
                  "&longitude=106.8";

    int code3 = http3.POST(data);

    Serial.print("Kirim sensor: ");
    Serial.println(code3);

    http3.end();

  } else {
    Serial.println("WiFi PUTUS!");
  }

  // Delay biar stabil
  delay(3000);
}