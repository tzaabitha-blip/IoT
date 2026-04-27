<?php
// File: pump_control_save.php - Untuk menyimpan setting dari AJAX
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $control_mode = $_POST['control_mode'] ?? 'auto';
    $manual_status = isset($_POST['manual_status']) ? intval($_POST['manual_status']) : 0;
    $soil_threshold = intval($_POST['soil_threshold'] ?? 40);
    
    $sql = "UPDATE pump_control SET 
            control_mode = ?, 
            manual_status = ?, 
            soil_threshold = ? 
            WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $control_mode, $manual_status, $soil_threshold);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "method not allowed";
}
$conn->close();
?>