<?php
header('Content-Type: application/json');
include 'koneksi.php';

$api_key_value = "12345abcde";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $api_key = $_POST['api_key'] ?? '';

    if ($api_key === $api_key_value) {

        $kelTanah  = isset($_POST['kelTanah']) ? (float)$_POST['kelTanah'] : 0;
        $kelUdara  = isset($_POST['kelUdara']) ? (float)$_POST['kelUdara'] : 0;
        $suhuUdara = isset($_POST['suhuUdara']) ? (float)$_POST['suhuUdara'] : 0;
        $kecerahan = isset($_POST['kecerahan']) ? (float)$_POST['kecerahan'] : 0;
        $lat       = isset($_POST['latitude']) ? (float)$_POST['latitude'] : 0;
        $lng       = isset($_POST['longitude']) ? (float)$_POST['longitude'] : 0;

        $sql = "INSERT INTO SensorData 
                (kelTanah, kelUdara, suhuUdara, kecerahan, latitude, longitude) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dddddd", $kelTanah, $kelUdara, $suhuUdara, $kecerahan, $lat, $lng);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Data berhasil disimpan"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => $stmt->error
            ]);
        }

        $stmt->close();

    } else {
        echo json_encode([
            "status" => "error",
            "message" => "API Key Tidak Valid"
        ]);
    }

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Harus POST"
    ]);
}

$conn->close();
?>