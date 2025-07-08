#include <WiFi.h>
#include <HTTPClient.h>
#include <Arduino_JSON.h>
#include <HardwareSerial.h>
#include <ModbusMaster.h>
#include <U8g2lib.h>
#include <ESPmDNS.h>

// === Modul Fuzzy Logic ===
struct FuzzyRule {
  int n, p, k, soil;
  int n_out, p_out, k_out;
};

FuzzyRule rules[33] = {
  {0,0,0,0,2,1,1}, {0,0,0,1,1,0,0}, {0,0,0,2,0,0,0},
  {0,0,1,0,2,1,0}, {0,0,1,1,1,0,0}, {0,0,1,2,0,0,0},
  {0,1,0,0,2,0,1}, {0,1,0,1,1,0,0}, {0,1,0,2,0,0,0},
  {0,1,1,0,2,0,0}, {0,1,1,1,1,0,0}, {0,1,1,2,0,0,0},
  {0,2,2,0,2,0,0}, {0,2,2,1,1,0,0}, {0,2,2,2,0,0,0},
  {1,0,0,0,1,1,1}, {1,0,0,1,0,0,0}, {1,0,0,2,0,0,0},
  {1,1,1,0,1,0,0}, {1,1,1,1,0,0,0}, {1,1,1,2,0,0,0},
  {1,2,2,0,1,0,0}, {1,2,2,1,0,0,0}, {1,2,2,2,0,0,0},
  {2,0,0,0,1,1,1}, {2,0,0,1,0,0,0}, {2,0,0,2,0,0,0},
  {2,1,1,0,1,0,0}, {2,1,1,1,0,0,0}, {2,1,1,2,0,0,0},
  {2,2,2,0,1,0,0}, {2,2,2,1,0,0,0}, {2,2,2,2,0,0,0}
};

// Konversi nilai ke kategori fuzzy level
int toFuzzyLevel(float val, float low, float med, float high) {
  if (val <= low) return 0;      // Low
  else if (val <= med) return 1; // Medium
  else return 2;                 // High
}

// Cari aturan yang cocok
FuzzyRule getMatchingRule(int n, int p, int k, int soil) {
  for (int i = 0; i < 33; i++) {
    if (rules[i].n == n && rules[i].p == p && rules[i].k == k && rules[i].soil == soil) {
      return rules[i];
    }
  }
  return {n, p, k, soil, 0, 0, 0}; // default OFF semua
}

// Konversi level ke string (untuk dikirim ke Laravel atau OLED)
String pumpLevelToStr(int level) {
  if (level == 2) return "Medium";
  if (level == 1) return "Short";
  return "OFF";
}

// Kontrol pompa berdasarkan level dan kembalikan status sebagai string
String controlPump(int pin, int level) {
  if (level == 2) {
    digitalWrite(pin, HIGH); delay(6000);
  } else if (level == 1) {
    digitalWrite(pin, HIGH); delay(3000);
  }
  digitalWrite(pin, LOW);
  return pumpLevelToStr(level);
}

// Fungsi utama kontrol fuzzy pompa NPK
void fuzzyPumpControl(float nitrogen, float phosphorus, float potassium, float humidity,
                      String& nStatus, String& pStatus, String& kStatus) {

  int n_level = toFuzzyLevel(nitrogen, 25, 50, 100);
  int p_level = toFuzzyLevel(phosphorus, 25, 50, 100);
  int k_level = toFuzzyLevel(potassium, 25, 50, 100);
  int soil_level = toFuzzyLevel(humidity, 10, 30, 50);

  FuzzyRule rule = getMatchingRule(n_level, p_level, k_level, soil_level);

  nStatus = controlPump(4, rule.n_out);
  pStatus = controlPump(5, rule.p_out);
  kStatus = controlPump(6, rule.k_out);
}
String nPumpStatusStr = "OFF";
String pPumpStatusStr = "OFF";
String kPumpStatusStr = "OFF";

// ===========================
// Konfigurasi WiFi (MULTI-SSID)
// ===========================
const char* ssidList[] = {"Rosyadi", "Xs"};
const char* passwordList[] = {"Rosyadi13015", "qwertyui"};
const int wifiCount = sizeof(ssidList) / sizeof(ssidList[0]);

// ===========================
// Konfigurasi REST API Laravel
// ===========================
const char* soilApiUrl = "http://administrator.local:8000/api/soil-data";
const char* npkApiUrl  = "http://administrator.local:8000/api/npk-data";


// ===========================
// Konfigurasi RS485
// ===========================
#define RXD2_PIN 16
#define TXD2_PIN 15
#define DE_RE_PIN 14

#define SLAVE_ID_CWT 2
#define SLAVE_ID_DFR 1

HardwareSerial RS485Serial(2);
ModbusMaster nodeCWT;
ModbusMaster nodeDFR;

// ===========================
// Konfigurasi Relay
// // ===========================
// #define RELAY_PIN 48
// #define HUMIDITY_THRESHOLD 30.0

// ===========================
// Kalibrasi NPK
// ===========================
const float NITROGEN_SLOPE = 0.09;
const float NITROGEN_OFFSET = 6.2;
const float PHOSPHORUS_SLOPE = 0.19;
const float PHOSPHORUS_OFFSET = 1.5;
const float POTASSIUM_SLOPE = 0.19;
const float POTASSIUM_OFFSET = -1.2;

int lastSoilResponseCode = 0;
int lastNpkResponseCode = 0;


// ===========================
// OLED SH1106 (SDA=8, SCL=9)
// ===========================
U8G2_SH1106_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0, U8X8_PIN_NONE, 9, 8);

// ===========================
// Timer
// ===========================
unsigned long lastTime = 0;
unsigned long interval = 2000; // 10 detik

unsigned long lastOledTime = 0;
unsigned long oledInterval = 1000; // OLED update tiap 1 detik

// ===========================
// Fungsi RS485
// ===========================
void preTransmission() {
  digitalWrite(DE_RE_PIN, HIGH);
}

void postTransmission() {
  digitalWrite(DE_RE_PIN, LOW);
}

uint16_t readRegisterWithRetry(ModbusMaster& node, uint16_t reg, uint8_t retries = 3, bool isHolding = true) {
  uint8_t result;
  for (uint8_t i = 0; i < retries; i++) {
    result = isHolding ? node.readHoldingRegisters(reg, 1) : node.readInputRegisters(reg, 1);
    if (result == node.ku8MBSuccess) {
      return node.getResponseBuffer(0);
    }
    delay(200);
  }
  Serial.printf("Gagal baca register 0x%04X. Error Code: %d\n", reg, result);
  return 0;
}

// ===========================
// Setup
// ===========================
void setup() {
  Serial.begin(115200);
  pinMode(DE_RE_PIN, OUTPUT);
  digitalWrite(DE_RE_PIN, LOW);
  // pinMode(RELAY_PIN, OUTPUT);
  // digitalWrite(RELAY_PIN, LOW);

  u8g2.begin();

  Serial.println("Mencoba menghubungkan ke WiFi...");
  connectToWiFi();

  RS485Serial.begin(9600, SERIAL_8N1, RXD2_PIN, TXD2_PIN);

  nodeCWT.begin(SLAVE_ID_CWT, RS485Serial);
  nodeCWT.preTransmission(preTransmission);
  nodeCWT.postTransmission(postTransmission);

  nodeDFR.begin(SLAVE_ID_DFR, RS485Serial);
  nodeDFR.preTransmission(preTransmission);
  nodeDFR.postTransmission(postTransmission);

  Serial.println("Setup selesai.");
  // Inisialisasi relay fuzzy
pinMode(4, OUTPUT); digitalWrite(4, LOW);  // N_Pump
pinMode(5, OUTPUT); digitalWrite(5, LOW);  // P_Pump
pinMode(6, OUTPUT); digitalWrite(6, LOW);  // K_Pump

if (MDNS.begin("esp32")) {
  Serial.println("mDNS responder started as esp32.local");
} else {
  Serial.println("Error starting mDNS");
}

}


// ===========================
// Fungsi Multiple WiFi
// ===========================
void connectToWiFi() {
  for (int i = 0; i < wifiCount; i++) {
    Serial.printf("Mencoba koneksi ke SSID: %s\n", ssidList[i]);
    WiFi.begin(ssidList[i], passwordList[i]);

    unsigned long startTime = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - startTime < 15000) {
      delay(500);
      Serial.print(".");
    }

    if (WiFi.status() == WL_CONNECTED) {
      Serial.printf("\nWiFi terhubung ke SSID: %s\n", ssidList[i]);
      return;
    } else {
      Serial.printf("\nGagal terhubung ke SSID: %s\n", ssidList[i]);
    }
  }
  Serial.println("Tidak ada WiFi yang terhubung. Restart ESP...");
  delay(3000);
  ESP.restart();
}

// ===========================
// Fungsi Kirim Data ke Laravel
// ===========================
void sendSoilData(float humidity, float temperature, float pH) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(soilApiUrl);
    http.addHeader("Content-Type", "application/json");

    JSONVar payload;
    payload["device_id"] = 1;
    payload["soil_moisture"] = humidity;
    payload["soil_temperature"] = temperature;
    payload["soil_ph"] = pH;

    String requestBody = JSON.stringify(payload);
    Serial.println("Mengirim Soil Data:");
    Serial.println(requestBody);
    

    int httpResponseCode = http.POST(requestBody);
    lastSoilResponseCode = httpResponseCode;


    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.printf("HTTP Response code: %d\n", httpResponseCode);
      Serial.println("Response dari server:");
      Serial.println(response);
    } else {
      Serial.printf("Error HTTP Response: %d\n", httpResponseCode);
    }
    http.end();
  } else {
    Serial.println("WiFi terputus!");
  }
}

void sendNpkData(float nitrogen, float phosphorus, float potassium) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(npkApiUrl);
    http.addHeader("Content-Type", "application/json");

    JSONVar payload;
    payload["device_id"] = 1;
    payload["nitrogen"] = nitrogen;
    payload["phosphorus"] = phosphorus;
    payload["potassium"] = potassium;
    payload["n_pump_status"] = nPumpStatusStr;
    payload["p_pump_status"] = pPumpStatusStr;
    payload["k_pump_status"] = kPumpStatusStr;


    String requestBody = JSON.stringify(payload);
    Serial.println("Mengirim NPK Data:");
    Serial.println(requestBody);

    int httpResponseCode = http.POST(requestBody);
    lastNpkResponseCode = httpResponseCode;


    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.printf("HTTP Response code: %d\n", httpResponseCode);
      Serial.println("Response dari server:");
      Serial.println(response);
    } else {
      Serial.printf("Error HTTP Response: %d\n", httpResponseCode);
    }
    http.end();
  } else {
    Serial.println("WiFi terputus!");
  }
}

// ===========================
// Loop
// ===========================
void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectToWiFi();
  }

  if ((millis() - lastTime) > interval) {
    Serial.println("Membaca data sensor...");

    float humidity = readRegisterWithRetry(nodeCWT, 0x0000) / 10.0;
    float temperature = readRegisterWithRetry(nodeCWT, 0x0001) / 10.0;
    float pH = readRegisterWithRetry(nodeCWT, 0x0003) / 10.0;

    uint16_t nitrogen_raw = readRegisterWithRetry(nodeDFR, 0x0010);
    uint16_t phosphorus_raw = readRegisterWithRetry(nodeDFR, 0x0001);
    uint16_t potassium_raw = readRegisterWithRetry(nodeDFR, 0x0002);

    float nitrogen = (nitrogen_raw * NITROGEN_SLOPE) - NITROGEN_OFFSET;
    float phosphorus = (phosphorus_raw * PHOSPHORUS_SLOPE) - PHOSPHORUS_OFFSET;
    float potassium = (potassium_raw * POTASSIUM_SLOPE) + POTASSIUM_OFFSET;

    // if (humidity <= HUMIDITY_THRESHOLD) {
    //   digitalWrite(RELAY_PIN, HIGH);
    //   Serial.println("Pompa AKTIF - Kelembapan rendah");
    // } else {
    //   digitalWrite(RELAY_PIN, LOW);
    //   Serial.println("Pompa NONAKTIF - Kelembapan cukup");
    // }

    sendSoilData(humidity, temperature, pH);
    sendNpkData(nitrogen, phosphorus, potassium);
    // Kontrol fuzzy pompa NPK
  //  String nPumpStatus, pPumpStatus, kPumpStatus;
fuzzyPumpControl(nitrogen, phosphorus, potassium, humidity, nPumpStatusStr, pPumpStatusStr, kPumpStatusStr);



    if ((millis() - lastOledTime) > oledInterval) {
  lastOledTime = millis();

  char buffer[32];
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.drawStr(10, 10, "Smart Farming ");

  sprintf(buffer, "Hmdt: %.1f%%", humidity);
  u8g2.drawStr(0, 22, buffer);

  sprintf(buffer, "pH: %.1f Temp: %.1fC", pH, temperature);
  u8g2.drawStr(0, 32, buffer);

  sprintf(buffer, "N:%.1f P:%.1f K:%.1f", nitrogen, phosphorus, potassium);
  u8g2.drawStr(0, 42, buffer);

  // sprintf(buffer, "Pompa: %s", humidity <= HUMIDITY_THRESHOLD ? "AKTIF" : "NONAKTIF");
  // u8g2.drawStr(0, 52, buffer);

  sprintf(buffer, "Soil:%d NPK:%d", lastSoilResponseCode, lastNpkResponseCode);
  u8g2.drawStr(0, 62, buffer);

  u8g2.sendBuffer();
}

  }
}
