document.addEventListener('DOMContentLoaded', async () => {
    const deleteHistoryBtn = document.getElementById('deleteHistoryBtn');
    const dailyRevenueEl = document.getElementById('dailyRevenue');
    const weeklyRevenueEl = document.getElementById('weeklyRevenue');
    const monthlyRevenueEl = document.getElementById('monthlyRevenue');
    const yearlyRevenueEl = document.getElementById('yearlyRevenue');
    const totalFilteredRevenueEl = document.getElementById('totalFilteredRevenue');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const filterRevenueBtn = document.getElementById('filterRevenueBtn');
    const resetFilterBtn = document.getElementById('resetFilterBtn');
    const transactionListUl = document.getElementById('transactionList');

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
    }

    function isDateBetween(date, start, end) {
        const transactionDate = new Date(date);
        return transactionDate >= start && transactionDate <= end;
    }

    async function loadAndDisplayRevenue(startDate = null, endDate = null) {
        let apiUrl = 'api/transactions.php';
        const params = new URLSearchParams();
        if (startDate) params.append('startDate', startDate.toISOString().split('T')[0]);
        if (endDate) params.append('endDate', endDate.toISOString().split('T')[0]);
        if (params.toString()) apiUrl += '?' + params.toString();

        try {
            const response = await fetch(apiUrl);
            const data = await response.json();
            if (data.success) {
                calculateAndDisplayRevenue(data.transactions);
            } else {
                console.error('Lỗi khi tải giao dịch:', data.message);
                calculateAndDisplayRevenue([]);
            }
        } catch (error) {
            console.error('Lỗi kết nối API:', error);
            calculateAndDisplayRevenue([]);
            alert('Lỗi kết nối: Không thể tải dữ liệu doanh thu.');
        }
    }

    function calculateAndDisplayRevenue(transactionsToProcess) {
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        let dailyRevenue = 0, weeklyRevenue = 0, monthlyRevenue = 0, yearlyRevenue = 0, totalFilteredRevenue = 0;
        transactionListUl.innerHTML = '';
        if (transactionsToProcess.length === 0) {
            const li = document.createElement('li');
            li.classList.add('empty-message');
            li.textContent = 'Chưa có giao dịch nào được ghi nhận.';
            transactionListUl.appendChild(li);
        }

        const revenueByDate = {};

        transactionsToProcess.forEach(transaction => {
            const date = new Date(transaction.Timestamp);
            const amount = parseFloat(transaction.Amount);
            const dateStr = date.toLocaleDateString('vi-VN');
            date.setHours(0, 0, 0, 0);

            totalFilteredRevenue += amount;
            if (date.toDateString() === now.toDateString()) dailyRevenue += amount;

            const weekStart = new Date(now);
            weekStart.setDate(now.getDate() - now.getDay());
            weekStart.setHours(0, 0, 0, 0);
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekStart.getDate() + 6);
            weekEnd.setHours(23, 59, 59, 999);
            if (isDateBetween(transaction.Timestamp, weekStart, weekEnd)) weeklyRevenue += amount;

            if (date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear()) monthlyRevenue += amount;
            if (date.getFullYear() === now.getFullYear()) yearlyRevenue += amount;

            const li = document.createElement('li');
            li.innerHTML = `<span>${date.toLocaleString('vi-VN')}</span> <span>${transaction.TableName}</span> <strong>${formatCurrency(amount)}</strong>`;
            transactionListUl.appendChild(li);

            revenueByDate[dateStr] = (revenueByDate[dateStr] || 0) + amount;
        });

        dailyRevenueEl.textContent = formatCurrency(dailyRevenue);
        weeklyRevenueEl.textContent = formatCurrency(weeklyRevenue);
        monthlyRevenueEl.textContent = formatCurrency(monthlyRevenue);
        yearlyRevenueEl.textContent = formatCurrency(yearlyRevenue);
        totalFilteredRevenueEl.textContent = formatCurrency(totalFilteredRevenue);

        // Vẽ biểu đồ
        const labels = Object.keys(revenueByDate).sort((a, b) => {
            const da = new Date(a.split('/').reverse().join('-'));
            const db = new Date(b.split('/').reverse().join('-'));
            return da - db;
        });
        const values = labels.map(date => revenueByDate[date]);

        if (window.revenueChartInstance) window.revenueChartInstance.destroy();

        const ctx = document.getElementById('revenueChart')?.getContext('2d');
        if (ctx) {
            window.revenueChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Doanh thu theo ngày',
                        data: values,
                        backgroundColor: '#007bff',
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => formatCurrency(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => formatCurrency(value)
                            }
                        }
                    }
                }
            });
        }
    }

    deleteHistoryBtn.addEventListener('click', async () => {
        if (confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử doanh thu?')) {
            try {
                const response = await fetch('api/transactions.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    loadAndDisplayRevenue();
                } else {
                    alert('Lỗi khi xóa: ' + data.message);
                }
            } catch (error) {
                console.error('Lỗi API khi xóa:', error);
                alert('Không thể xóa dữ liệu doanh thu.');
            }
        }
    });

    filterRevenueBtn.addEventListener('click', () => {
        const startDate = startDateInput.value ? new Date(startDateInput.value) : null;
        const endDate = endDateInput.value ? new Date(endDateInput.value) : null;
        if (startDate && endDate && startDate > endDate) {
            alert('Ngày bắt đầu không được lớn hơn ngày kết thúc!');
            return;
        }
        loadAndDisplayRevenue(startDate, endDate);
    });

    resetFilterBtn.addEventListener('click', () => {
        startDateInput.value = '';
        endDateInput.value = '';
        loadAndDisplayRevenue();
    });

    loadAndDisplayRevenue();
});
