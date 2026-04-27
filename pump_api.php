<?php
header('Content-Type: application/json');
include 'koneksi.php';

$api_key_value = "12345abcde";
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';

if ($api_key !== $api_key_value) {
    echo json_encode(['status' => 'error', 'message' => 'API Key Invalid']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {

    // =========================
    // 1. AMBIL STATUS
    // =========================
    case 'get_status':
        $sql = "SELECT control_mode, manual_status, soil_threshold FROM pump_control WHERE id = 1";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();

            echo json_encode([
                'status'        => 'success',
                'control_mode'  => $data['control_mode'],
                'manual_status' => (bool)$data['manual_status'],
                'soil_threshold'=> (int)$data['soil_threshold']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }
        break;

    // =========================
    // 2. SET MANUAL ON/OFF (WEB)
    // FIX: sekaligus set control_mode = 'manual'
    // agar ESP32 tidak mengabaikan manual_status
    // =========================
    case 'set_manual':
        $manual_status = isset($_GET['manual_status'])
            ? (int)$_GET['manual_status']
            : (isset($_POST['manual_status']) ? (int)$_POST['manual_status'] : 0);

        // FIX: update manual_status DAN paksa control_mode = 'manual'
        $sql = "UPDATE pump_control SET manual_status = ?, control_mode = 'manual' WHERE id = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $manual_status);

        if ($stmt->execute()) {
            echo json_encode([
                'status'        => 'success',
                'manual_status' => $manual_status,
                'control_mode'  => 'manual'
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => $stmt->error
            ]);
        }
        break;

    // =========================
    // 3. SET MODE AUTO / MANUAL
    // =========================
    case 'set_mode':
        $mode = $_GET['control_mode'] ?? $_POST['control_mode'] ?? 'auto';

        $sql = "UPDATE pump_control SET control_mode = ? WHERE id = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $mode);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'control_mode' => $mode]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        break;

    // =========================
    // 4. UPDATE DARI ESP32
    // (hanya ESP32 yang pakai action ini, bukan web)
    // =========================
    case 'update_pump':
        $pump_status   = isset($_GET['pump_status'])   ? (int)$_GET['pump_status']     : 0;
        $soil_moisture = isset($_GET['soil_moisture'])  ? (float)$_GET['soil_moisture'] : 0;
        $trigger       = $_GET['trigger']       ?? 'auto';
        $control_mode  = $_GET['control_mode']  ?? 'auto';

        // Update status pompa
        $sql = "UPDATE pump_control SET pump_status = ? WHERE id = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pump_status);

        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
            exit;
        }

        // Simpan log
        $sql_log = "INSERT INTO pump_log (pump_status, control_mode, soil_moisture, trigger_type)
                    VALUES (?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("isds", $pump_status, $control_mode, $soil_moisture, $trigger);

        if (!$stmt_log->execute()) {
            echo json_encode(['status' => 'error', 'message' => $stmt_log->error]);
            exit;
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Pump updated'
        ]);
        break;

    default:
        echo json_encode([
            'status'  => 'error',
            'message' => 'Unknown action: ' . htmlspecialchars($action)
        ]);
}
?>