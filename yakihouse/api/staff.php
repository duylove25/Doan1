<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT StaffID, Name, Code, Position, Phone FROM Staffs");
    $staffs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $staffs[] = $row;
        }
        $response['success'] = true;
        $response['staffs'] = $staffs;
    } else {
        $response['message'] = 'Không tìm thấy nhân viên nào.';
        $response['staffs'] = [];
    }
}

else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $staffID = $input['id'] ?? null;
    $name = $input['name'] ?? '';
    $code = $input['code'] ?? '';
    $password = $input['password'] ?? '';
    $phone = $input['phone'] ?? '';
    $position = $input['position'] ?? '';

    if (empty($name) || empty($code) || empty($password) || empty($position)) {
        $response['message'] = 'Vui lòng điền đầy đủ thông tin nhân viên.';
        echo json_encode($response);
        exit();
    }


    if ($staffID) { // Cập nhật nhân viên
        $stmt = $conn->prepare("UPDATE Staffs SET Name = ?, Code = ?, Password = ?, Position = ?, Phone = ? WHERE StaffID = ?");
        $stmt->bind_param("ssssss", $name, $code, $password, $position, $phone, $staffID); // $password nên là $hashed_password
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Cập nhật nhân viên thành công!';
        } else {
            $response['message'] = 'Lỗi khi cập nhật nhân viên: ' . $stmt->error;
        }
        $stmt->close();
    } else { // Thêm nhân viên mới
      
        $check_stmt = $conn->prepare("SELECT StaffID FROM Staffs WHERE Code = ?");
        $check_stmt->bind_param("s", $code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $response['message'] = 'Mã nhân viên đã tồn tại. Vui lòng chọn mã khác.';
            $check_stmt->close();
            echo json_encode($response);
            exit();
        }
        $check_stmt->close();

        $newStaffID = 'nv' . uniqid();
        $stmt = $conn->prepare("INSERT INTO Staffs (StaffID, Name, Code, Password, Position, Phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $newStaffID, $name, $code, $password, $position, $phone); 
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Thêm nhân viên thành công!';
        } else {
            $response['message'] = 'Lỗi khi thêm nhân viên: ' . $stmt->error;
        }
        $stmt->close();
    }
}
// Xóa nhân viên (DELETE request)
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $staffID = $input['id'] ?? '';

    if (empty($staffID)) {
        $response['message'] = 'Vui lòng cung cấp ID nhân viên để xóa.';
    } else {
     
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Orders WHERE StaffID = ?");
        $check_stmt->bind_param("s", $staffID);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_row()[0];
        $check_stmt->close();

        if ($check_result > 0) {
            $response['message'] = 'Không thể xóa nhân viên này vì có đơn hàng liên quan.';
        } else {
            $stmt = $conn->prepare("DELETE FROM Staffs WHERE StaffID = ?");
            $stmt->bind_param("s", $staffID);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Xóa nhân viên thành công!';
            } else {
                $response['message'] = 'Lỗi khi xóa nhân viên: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
else {
    $response['message'] = 'Phương thức yêu cầu không hợp lệ.';
}

echo json_encode($response);
$conn->close();
?>
