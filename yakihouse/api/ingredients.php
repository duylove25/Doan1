<?php
header('Content-Type: application/json');

// Kết nối cơ sở dữ liệu
require_once 'db_connect.php';

// Xử lý yêu cầu POST (Nhập/Xuất kho)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ yêu cầu POST
    $action = $_POST['action'] ?? '';
    $ingredientName = $_POST['ingredient_name'] ?? '';
    $ingredientUnit = $_POST['ingredient_unit'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $note = $_POST['note'] ?? '';

    // Kiểm tra các trường dữ liệu cần thiết
    if (empty($ingredientName) || !is_numeric($quantity) || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ. Vui lòng điền đầy đủ thông tin.']);
        $conn->close();
        exit;
    }

    // Xử lý hành động nhập kho (add)
    if ($action === 'add') {
        // Sử dụng ON DUPLICATE KEY UPDATE để thêm mới nếu chưa có hoặc cập nhật nếu đã tồn tại
        // Đảm bảo cột `name` trong bảng `ingredients` của bạn là UNIQUE KEY
        $stmt = $conn->prepare("INSERT INTO ingredients (name, unit, quantity, note) VALUES (?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), unit = VALUES(unit), note = VALUES(note)");
        $stmt->bind_param("ssis", $ingredientName, $ingredientUnit, $quantity, $note);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi nhập kho: ' . $stmt->error]);
        }
        $stmt->close();
    }
    // Xử lý hành động xuất kho (subtract)
    elseif ($action === 'subtract') {
        // Kiểm tra xem nguyên liệu có tồn tại không
        $stmt_check = $conn->prepare("SELECT quantity FROM ingredients WHERE name = ?");
        $stmt_check->bind_param("s", $ingredientName);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        if ($result->num_rows > 0) {
            $currentQuantity = $result->fetch_assoc()['quantity'];
            if ($currentQuantity < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Số lượng xuất kho vượt quá số lượng tồn kho.']);
                $stmt_check->close();
                $conn->close();
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy nguyên liệu.']);
            $stmt_check->close();
            $conn->close();
            exit;
        }
        $stmt_check->close();

        // Cập nhật số lượng
        $stmt = $conn->prepare("UPDATE ingredients SET quantity = quantity - ? WHERE name = ?");
        $stmt->bind_param("is", $quantity, $ingredientName);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xuất kho: ' . $stmt->error]);
        }
        $stmt->close();
    }
    // Hành động không hợp lệ
    else {
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
    }
}
// Xử lý yêu cầu GET (Lấy danh sách nguyên liệu)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, name, unit, quantity, note FROM ingredients";
    $result = $conn->query($sql);
    $ingredients = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ingredients[] = $row;
        }
    }
    echo json_encode(['success' => true, 'ingredients' => $ingredients]);
}
// Xử lý các yêu cầu khác
else {
    echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không được hỗ trợ.']);
}

$conn->close();
?>