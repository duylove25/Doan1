document.addEventListener('DOMContentLoaded', () => {
    const ingredientTableBody = document.querySelector('#ingredientTable tbody');
    const logForm = document.getElementById('logForm');
    const statusMessage = document.getElementById('statusMessage');

    const role = sessionStorage.getItem('role');

    async function fetchAndRenderIngredients() {
        try {
            const response = await fetch('api/ingredients.php');
            const data = await response.json();

            if (data.success) {
                ingredientTableBody.innerHTML = '';
                data.ingredients.forEach(ing => {
                    const tr = document.createElement('tr');
                    const isOutOfStock = ing.quantity <= 0;
                    tr.innerHTML = `
                        <td>${ing.name}</td>
                        <td>${ing.unit || ''}</td>
                        <td class="${isOutOfStock ? 'out-of-stock' : ''}">${ing.quantity}</td>
                        <td>${ing.note || ''}</td>
                    `;
                    ingredientTableBody.appendChild(tr);
                });
            } else {
                statusMessage.textContent = data.message || 'Không thể tải danh sách nguyên liệu.';
            }
        } catch (error) {
            console.error('Lỗi khi tải nguyên liệu:', error);
            statusMessage.textContent = 'Đã xảy ra lỗi khi kết nối máy chủ.';
        }
    }

    if (logForm) {
        logForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            statusMessage.textContent = '';

            // Chỉ cho Quản lý submit form
            if (role !== 'Quản lý') {
                alert('Bạn không có quyền nhập hoặc xuất kho!');
                return;
            }

            const ingredientName = document.getElementById('ingredient-name').value.trim();
            const ingredientUnit = document.getElementById('ingredient-unit').value.trim();
            const quantity = parseFloat(document.getElementById('quantity').value);
            const action = document.getElementById('action').value;
            const note = document.getElementById('note').value.trim();

            if (!ingredientName || isNaN(quantity) || quantity <= 0) {
                statusMessage.textContent = 'Vui lòng điền đầy đủ tên nguyên liệu và số lượng hợp lệ.';
                return;
            }

            const formData = new FormData();
            formData.append('action', action);
            formData.append('ingredient_name', ingredientName);
            formData.append('ingredient_unit', ingredientUnit);
            formData.append('quantity', quantity);
            formData.append('note', note);

            try {
                const response = await fetch('api/ingredients.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert('Cập nhật kho thành công!');
                    logForm.reset();
                    fetchAndRenderIngredients();
                } else {
                    statusMessage.textContent = 'Lỗi: ' + result.message;
                }
            } catch (error) {
                console.error('Lỗi khi gửi yêu cầu:', error);
                statusMessage.textContent = 'Đã xảy ra lỗi khi cập nhật kho. Vui lòng thử lại.';
            }
        });
    }

    fetchAndRenderIngredients();
});
