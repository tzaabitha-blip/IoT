<?php
// File: deepseek_api.php
// Fungsi untuk memanggil DeepSeek API

function getDeepSeekRecommendation($sensorData) {
    // Konfigurasi API Key DeepSeek
    // Daftar di https://platform.deepseek.com/ untuk mendapatkan API Key
    $apiKey = "sk-57f6993cf85c4f97aa19c9e78f28d171"; // Ganti dengan API key Anda
    
    $apiUrl = "https://api.deepseek.com/v1/chat/completions";
    
    // Format data sensor untuk prompt
    $tanah = $sensorData['kelTanah'] ?? 0;
    $udara = $sensorData['kelUdara'] ?? 0;
    $suhu = $sensorData['suhuUdara'] ?? 0;
    $cahaya = $sensorData['kecerahan'] ?? 0;
    
    // Ambil data historis 7 hari terakhir untuk analisis tren
    global $conn;
    $sql_historis = "SELECT AVG(kelTanah) as avg_tanah, AVG(kelUdara) as avg_udara, 
                            AVG(suhuUdara) as avg_suhu, AVG(kecerahan) as avg_cahaya 
                     FROM SensorData WHERE reading_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $result_historis = $conn->query($sql_historis);
    $historis = $result_historis->fetch_assoc();
    
    // Buat prompt untuk DeepSeek
    $prompt = "Anda adalah seorang ahli pertanian khusus tanaman sawi. 
Berikan rekomendasi perawatan berdasarkan data sensor berikut:

DATA SENSOR SAAT INI:
- Kelembaban Tanah: {$tanah}% (ideal: 60-80%)
- Kelembaban Udara: {$udara}% (ideal: 50-70%)
- Suhu Udara: {$suhu}°C (ideal: 20-28°C)
- Intensitas Cahaya: {$cahaya} Lux (ideal: >5000 Lux)

DATA RATA-RATA 7 HARI TERAKHIR:
- Rata-rata Kelembaban Tanah: " . round($historis['avg_tanah'] ?? 0) . "%
- Rata-rata Kelembaban Udara: " . round($historis['avg_udara'] ?? 0) . "%
- Rata-rata Suhu: " . round($historis['avg_suhu'] ?? 0) . "°C
- Rata-rata Cahaya: " . round($historis['avg_cahaya'] ?? 0) . " Lux

Berdasarkan data di atas, berikan:
1. Analisis singkat kondisi tanaman
2. 3-5 rekomendasi perawatan yang spesifik dan actionable
3. Peringatan jika ada kondisi kritis
4. Saran jadwal perawatan (penyiraman, pemupukan, dll)

Format respons dalam HTML dengan struktur:
<div class='ai-analysis'>
  <p><strong>📊 Analisis:</strong> [analisis]</p>
  <ul>
    <li>[rekomendasi 1]</li>
    <li>[rekomendasi 2]</li>
  </ul>
  <p><strong>⚠️ Peringatan:</strong> [peringatan jika ada]</p>
  <p><strong>📅 Jadwal:</strong> [saran jadwal]</p>
</div>

Gunakan bahasa Indonesia yang mudah dipahami.";

    // Data yang akan dikirim ke API
    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            [
                "role" => "system",
                "content" => "Anda adalah ahli pertanian tanaman sawi yang memberikan rekomendasi perawatan berdasarkan data sensor."
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "temperature" => 0.7,
        "max_tokens" => 1000
    ];
    
    // Inisialisasi CURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    
    // Eksekusi CURL
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Proses response
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'content' => $result['choices'][0]['message']['content'],
                'raw_response' => $response
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Response format tidak sesuai',
                'raw_response' => $response
            ];
        }
    } else {
        return [
            'success' => false,
            'error' => "HTTP Error: " . $httpCode,
            'raw_response' => $response
        ];
    }
}

// Fungsi untuk menyimpan rekomendasi ke database
function saveRecommendation($sensorId, $recommendation) {
    global $conn;
    $sql = "INSERT INTO AIRecommendations (sensor_id, recommendation, created_at) 
            VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $sensorId, $recommendation);
    return $stmt->execute();
}

// Fungsi untuk mendapatkan rekomendasi terbaru dari database
function getLatestRecommendation() {
    global $conn;
    $sql = "SELECT * FROM AIRecommendations ORDER BY created_at DESC LIMIT 1";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}
?>