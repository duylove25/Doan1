<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';
    $password = $input['password'] ?? '';

    if (empty($code) || empty($password)) {
        $response['message'] = 'Vui lòng nhập mã nhân viên và mật khẩu.';
        echo json_encode($response);
        exit();
    }

    $stmt = $conn->prepare("SELECT StaffID, Name, Position, Password FROM Staffs WHERE Code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($password === $user['Password']) { 
            $response['success'] = true;
            $response['message'] = 'Đăng nhập thành công!';
            $response['user'] = [
                'id' => $user['StaffID'],
                'name' => $user['Name'],
                'code' => $code,
                'position' => $user['Position'],
                'role' => $user['Position']
            ];
        } else {
            $response['message'] = 'Mật khẩu không đúng.';
        }
    } else {
        $response['message'] = 'Mã nhân viên không tồn tại.';
    }

    $stmt->close();
} else {
    $response['message'] = 'Phương thức yêu cầu không hợp lệ.';
}

echo json_encode($response);
$conn->close();
?>
