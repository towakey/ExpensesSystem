document.addEventListener('DOMContentLoaded', function() {
    // カテゴリ別支出グラフの初期化
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = JSON.parse(document.getElementById('categoryData').textContent);
    
    if (categoryData && categoryData.length > 0) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(item => item.category_name),
                datasets: [{
                    data: categoryData.map(item => item.total_amount),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#ffffff'
                        }
                    }
                }
            }
        });
    }

    // Bootstrap 5のモーダル初期化
    const transactionModal = new bootstrap.Modal(document.getElementById('transactionModal'));

    // 店舗選択時の商品リスト更新
    const storeSelect = document.querySelector('select[name="store_id"]');
    const goodsSelect = document.querySelector('select[name="goods_id"]');
    
    if (storeSelect && goodsSelect) {
        storeSelect.addEventListener('change', async function() {
            const storeId = this.value;
            if (!storeId) {
                goodsSelect.innerHTML = '<option value="">選択してください</option>';
                return;
            }

            try {
                const response = await fetch(`api/get_store_goods.php?store_id=${storeId}`);
                const goods = await response.json();
                
                goodsSelect.innerHTML = '<option value="">選択してください</option>';
                goods.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = `${item.name} (¥${item.price.toLocaleString()})`;
                    goodsSelect.appendChild(option);
                });
            } catch (error) {
                console.error('商品リストの取得に失敗しました:', error);
            }
        });
    }

    // 新規店舗登録
    const addStoreBtn = document.getElementById('addStoreBtn');
    if (addStoreBtn) {
        addStoreBtn.addEventListener('click', function() {
            const storeName = prompt('店舗名を入力してください:');
            if (!storeName) return;

            fetch('api/add_store.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ name: storeName })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const option = document.createElement('option');
                    option.value = data.store_id;
                    option.textContent = storeName;
                    storeSelect.appendChild(option);
                    storeSelect.value = data.store_id;
                    storeSelect.dispatchEvent(new Event('change'));
                } else {
                    alert('店舗の登録に失敗しました: ' + data.error);
                }
            })
            .catch(error => {
                console.error('店舗の登録に失敗しました:', error);
                alert('店舗の登録に失敗しました');
            });
        });
    }

    // 新規商品登録
    const addGoodsBtn = document.getElementById('addGoodsBtn');
    if (addGoodsBtn) {
        addGoodsBtn.addEventListener('click', function() {
            const storeId = storeSelect.value;
            if (!storeId) {
                alert('先に店舗を選択してください');
                return;
            }

            const goodsName = prompt('商品名を入力してください:');
            if (!goodsName) return;

            const price = prompt('価格を入力してください:');
            if (!price || isNaN(price)) return;

            fetch('api/add_goods.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    store_id: storeId,
                    name: goodsName,
                    price: parseFloat(price)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const option = document.createElement('option');
                    option.value = data.goods_id;
                    option.textContent = `${goodsName} (¥${parseFloat(price).toLocaleString()})`;
                    goodsSelect.appendChild(option);
                    goodsSelect.value = data.goods_id;
                } else {
                    alert('商品の登録に失敗しました: ' + data.error);
                }
            })
            .catch(error => {
                console.error('商品の登録に失敗しました:', error);
                alert('商品の登録に失敗しました');
            });
        });
    }

    // フォーム送信処理
    const expenseForm = document.getElementById('expenseForm');
    const incomeForm = document.getElementById('incomeForm');

    if (expenseForm) {
        expenseForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('支出の登録に失敗しました: ' + result.error);
                }
            } catch (error) {
                console.error('支出の登録に失敗しました:', error);
                alert('支出の登録に失敗しました');
            }
        });
    }

    if (incomeForm) {
        incomeForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('収入の登録に失敗しました: ' + result.error);
                }
            } catch (error) {
                console.error('収入の登録に失敗しました:', error);
                alert('収入の登録に失敗しました');
            }
        });
    }
});
