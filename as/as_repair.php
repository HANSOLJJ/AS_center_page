<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: login.php');
    exit;
}

// MySQL 호환성 레이어 로드
require_once 'mysql_compat.php';

// 데이터베이스 연결
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

$user_name = $_SESSION['member_id'];
$current_page = 'as_requests';
$error_message = '';

// 쿼리 파라미터에서 itemid 받기
$itemid = isset($_GET['itemid']) ? intval($_GET['itemid']) : 0;

if (empty($itemid)) {
    $error_message = 'AS 아이템 ID가 없습니다.';
}

$item_info = array();
$as_info = array();
$all_methods = array(); // 수리 방법 (step16_as_poor)

if (!empty($itemid)) {
    // Step 1: step14_as_item에서 s14_aiid로 조회
    $item_query = "SELECT s14_aiid, s14_asid, s14_model, s14_poor, s14_stat, cost_name, as_start_view, as_end_result FROM step14_as_item WHERE s14_aiid = $itemid";
    $item_result = mysql_query($item_query);

    if (!$item_result || mysql_num_rows($item_result) == 0) {
        $error_message = 'AS 아이템을 찾을 수 없습니다. (itemid=' . $itemid . ')';
    } else {
        $item_info = mysql_fetch_assoc($item_result);
        $asid = $item_info['s14_asid'];
        $current_poor_id = $item_info['s14_poor'];

        // Step 2: AS 요청 정보 조회 (asid 사용)
        $as_query = "SELECT s13_asid, s13_meid, s13_as_in_how, s13_as_in_date, ex_company, ex_tel FROM step13_as WHERE s13_asid = $asid";
        $as_result = mysql_query($as_query);

        if ($as_result && mysql_num_rows($as_result) > 0) {
            $as_info = mysql_fetch_assoc($as_result);
        }

        // Step 3: 수리 방법 목록 조회 (step19_as_result)
        $method_result = mysql_query("SELECT s19_asrid, s19_result FROM step19_as_result ORDER BY s19_asrid ASC");
        if ($method_result) {
            while ($row = mysql_fetch_assoc($method_result)) {
                $all_methods[] = $row;
            }
        }

        // Step 3-1: 제품 목록 조회 (step15_as_model)
        $all_models = array();
        $model_result = mysql_query("SELECT s15_amid, s15_model_name FROM step15_as_model ORDER BY s15_amid ASC");
        if ($model_result) {
            while ($row = mysql_fetch_assoc($model_result)) {
                $all_models[] = $row;
            }
        }

        // Step 4: 기존 등록된 자재 조회 (step18_as_cure_cart)
        $existing_parts_query = "SELECT s18_uid, cost_name, cost1, s18_quantity FROM step18_as_cure_cart WHERE s18_aiid = $itemid";
        $existing_parts_result = mysql_query($existing_parts_query);
        $existing_parts = array();
        if ($existing_parts_result) {
            while ($row = mysql_fetch_assoc($existing_parts_result)) {
                $existing_parts[] = array(
                    'part_id' => intval($row['s18_uid']),
                    'part_name' => $row['cost_name'],
                    'cost' => floatval($row['cost1']),
                    'quantity' => intval($row['s18_quantity'])
                );
            }
        }
    }
}

// 현재 선택된 as_end_result (기본값: "점검")
$current_result = isset($item_info['as_end_result']) && !empty($item_info['as_end_result']) ? $item_info['as_end_result'] : '점검';
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AS 수리 처리 - AS 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .header-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border: 1px solid white;
            border-radius: 5px;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        .nav-bar {
            background: white;
            padding: 0;
            border-bottom: 2px solid #ddd;
            display: flex;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .nav-item {
            padding: 15px 25px;
            text-decoration: none;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-item:hover {
            background: #f5f5f5;
            color: #667eea;
        }

        .nav-item.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f9f9ff;
        }

        .container {
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: #667eea;
            margin-bottom: 30px;
        }

        .message {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .error {
            background: #fee;
            border: 1px solid #f99;
            color: #c33;
        }

        .success {
            background: #efe;
            border: 1px solid #9f9;
            color: #3c3;
        }

        .info-box {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        /* Step 컨테이너 */
        .step-container {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #ebeeffff;
            border-left: 3px solid #667eea;
        }

        .step-container.green {
            background: #f5fff5ff;
            border-left-color: #27ae60;
        }

        .step-container.blue {
            background: #f0f8ff;
            border-left-color: #3498db;
        }

        .step-container.gray {
            background: #f5f5f5;
            border-left-color: #ccc;
            opacity: 0.6;
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group select,
        .form-group input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group select:focus,
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .required {
            color: #e74c3c;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: flex-end;
        }

        .btn-submit {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e0e0e0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
        }

        .category-btn {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            color: #333;
            font-weight: 500;
        }

        .category-btn:hover {
            background: #f0f0f0;
        }

        .category-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .parts-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .parts-table th {
            background: #f0f4ff;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .parts-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .parts-table button {
            padding: 5px 10px;
            font-size: 12px;
        }

        .coming-soon {
            text-align: center;
            color: #999;
            padding: 30px;
            font-size: 14px;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            margin: 0 3px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: inline-block;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>디지탈컴 AS 시스템</h1>
        <div class="header-right">
            <span><?php echo htmlspecialchars($user_name); ?>님</span>
            <form method="POST" action="logout.php" style="margin: 0;">
                <button type="submit" class="logout-btn">로그아웃</button>
            </form>
        </div>
    </div>

    <div class="nav-bar">
        <a href="dashboard.php" class="nav-item">대시보드</a>
        <a href="as_requests.php" class="nav-item <?php echo $current_page === 'as_requests' ? 'active' : ''; ?>">AS
            작업</a>
        <a href="orders.php" class="nav-item">자재 판매</a>
        <a href="parts.php" class="nav-item">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
    </div>

    <div class="container">
        <div class="content">
            <h2>AS 수리 처리</h2>

            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($error_message) && !empty($itemid) && !empty($item_info)): ?>

                <!-- 수리 요청 정보 -->
                <div class="info-box">
                    <h3 style="color: #667eea; margin-bottom: 15px;">수리 요청 정보</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">수리 요청 업체명</span>
                            <span class="info-value"><?php echo htmlspecialchars($as_info['ex_company'] ?? '-'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">전화번호</span>
                            <span class="info-value"><?php echo htmlspecialchars($as_info['ex_tel'] ?? '-'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">제품명</span>
                            <select id="product_name" name="product_name" style="padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-size: 14px; flex: 1;">
                                <option value="">선택하세요</option>
                                <?php
                                foreach ($all_models as $model) {
                                    $selected = ($item_info['s14_model'] == $model['s15_amid']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($model['s15_amid']) . '" ' . $selected . '>' . htmlspecialchars($model['s15_model_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="info-item">
                            <span class="info-label">초기 불량 증상</span>
                            <span
                                class="info-value"><?php echo htmlspecialchars($item_info['as_start_view'] ?? '-'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Step 1: 수리 방법 선택 -->
                <form id="repairForm" method="POST">
                    <div class="step-container">
                        <div class="step-header">
                            <div>1단계: 수리 방법 선택</div>
                        </div>

                        <div class="form-group">
                            <label for="as_end_result">수리 방법 <span class="required">*</span></label>
                            <select id="as_end_result" name="as_end_result" required>
                                <option value="">선택하세요</option>
                                <?php
                                foreach ($all_methods as $method) {
                                    $selected = ($method['s19_result'] == $current_result) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($method['s19_result']) . '" ' . $selected . '>' . htmlspecialchars($method['s19_result']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="itemid" value="<?php echo htmlspecialchars($itemid); ?>">


                    <!-- Step 2: 자재 선택 -->
                    <div class="step-container blue">
                        <div class="step-header">
                            <label>2단계: 자재 선택</label>
                        </div>
                        <!-- 현재 추가된 자재 목록 확인 -->
                        <div>
                            <h4 style="color: #667eea; margin-bottom: 10px; font-size: 14px;">현재 추가된 자재 목록 확인</h4>
                            <table class="parts-table">
                                <thead>
                                    <tr>
                                        <th>자재명</th>
                                        <th>공급가</th>
                                        <th>수량</th>
                                        <th>합계</th>
                                        <th>삭제</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedPartsBody">
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #999;">선택된 자재가 없습니다.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- 저장/취소 버튼 -->
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                            <div id="submitButtonContainer" style="display: none;">
                                <button type="submit" class="btn-submit">수리 자재 등록</button>
                            </div>
                            <a href="as_requests.php" class="btn-cancel">취소</a>
                        </div>
                        <!-- 카테고리 필터 -->
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button type="button" onclick="filterByCategory('all')" class="category-btn active">모든
                                    카테고리</button>
                                <div id="categoryButtons"></div>
                            </div>
                        </div>

                        <!-- 검색 -->
                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <input type="text" id="partSearch" placeholder="자재명 검색"
                                style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                            <button type="button" onclick="searchParts()"
                                style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap;">검색</button>
                        </div>

                        <!-- 자재 테이블 -->
                        <table class="parts-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                                    <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">번호</th>
                                    <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">카테고리</th>
                                    <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">자재명</th>
                                    <th style="padding: 10px; text-align: right; border-right: 1px solid #ddd;">가격</th>
                                    <th style="padding: 10px; text-align: center; border-right: 1px solid #ddd;">수량</th>
                                    <th style="padding: 10px; text-align: center;">추가</th>
                                </tr>
                            </thead>
                            <tbody id="partsTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px; color: #999;">자재를 검색해주세요.</td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- 페이지네이션 -->
                        <div id="paginationContainer" class="pagination"></div>
                    </div>

                    <!-- Step 3: 추가 정보 (추후 구현) -->
                    <div class="step-container gray">
                        <div class="step-header">
                            <div>3단계: 추가 정보 (추후 구현)</div>
                        </div>

                        <div class="coming-soon">
                            <p>📋 다음 단계는 추후 구현될 예정입니다.</p>
                        </div>
                    </div>

                </form>

            <?php endif; ?>
        </div>
    </div>

    <script>
        let selectedCategory = 'all';
        let selectedParts = [];
        let currentPage = 1;
        let totalPages = 1;

        // 초기화: 카테고리 로드
        function initializeCategories() {
            const categoryContainer = document.getElementById('categoryButtons');

            fetch('parts.php?action=get_categories', {
                method: 'GET'
            })
                .then(r => r.json())
                .then(data => {
                    if (data.categories && data.categories.length > 0) {
                        categoryContainer.innerHTML = '';
                        data.categories.forEach(cat => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'category-btn';
                            btn.textContent = cat.s5_category;
                            btn.onclick = () => filterByCategory(cat.s5_caid);
                            categoryContainer.appendChild(btn);
                        });
                    }
                });
        }

        function filterByCategory(categoryId) {
            selectedCategory = categoryId;

            // 버튼 상태 업데이트
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // 자재 로드
            loadParts();
        }

        function searchParts() {
            loadParts();
        }

        function loadParts(page = 1) {
            const searchKey = document.getElementById('partSearch').value.trim();
            currentPage = page;

            fetch('order_handler.php?action=get_parts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'search_key=' + encodeURIComponent(searchKey) + '&category=' + encodeURIComponent(selectedCategory) + '&member_id=0&page=' + page
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        totalPages = data.total_pages || 1;
                        displayPartsList(data.parts);
                        displayPagination(data.page, data.total_pages);
                    }
                })
                .catch(error => {
                    console.error('자재 검색 에러:', error);
                });
        }

        function displayPagination(page, totalPages) {
            // pagination div 내용 초기화
            const paginationDiv = document.getElementById('paginationContainer');
            paginationDiv.innerHTML = '';

            // 페이지가 1페이지 이하면 페이지네이션 표시 안 함
            if (totalPages <= 1) {
                return;
            }

            // 이전 버튼
            if (page > 1) {
                const prevLink = document.createElement('a');
                prevLink.href = 'javascript:loadParts(' + (page - 1) + ')';
                prevLink.textContent = '← 이전';
                paginationDiv.appendChild(prevLink);
            }

            // 페이지 번호들
            const startPage = Math.max(1, page - 2);
            const endPage = Math.min(totalPages, page + 2);

            if (startPage > 1) {
                const firstLink = document.createElement('a');
                firstLink.href = 'javascript:loadParts(1)';
                firstLink.textContent = '1';
                paginationDiv.appendChild(firstLink);

                if (startPage > 2) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    paginationDiv.appendChild(dots);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === page) {
                    const currentSpan = document.createElement('span');
                    currentSpan.className = 'current';
                    currentSpan.textContent = i;
                    paginationDiv.appendChild(currentSpan);
                } else {
                    const link = document.createElement('a');
                    link.href = 'javascript:loadParts(' + i + ')';
                    link.textContent = i;
                    paginationDiv.appendChild(link);
                }
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    paginationDiv.appendChild(dots);
                }

                const lastLink = document.createElement('a');
                lastLink.href = 'javascript:loadParts(' + totalPages + ')';
                lastLink.textContent = totalPages;
                paginationDiv.appendChild(lastLink);
            }

            // 다음 버튼
            if (page < totalPages) {
                const nextLink = document.createElement('a');
                nextLink.href = 'javascript:loadParts(' + (page + 1) + ')';
                nextLink.textContent = '다음 →';
                paginationDiv.appendChild(nextLink);
            }
        }

        function displayPartsList(parts) {
            const tbody = document.getElementById('partsTableBody');
            tbody.innerHTML = '';

            if (parts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #999;">자재가 없습니다.</td></tr>';
                return;
            }

            parts.forEach(part => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #eee';
                tr.innerHTML = `
                    <td style="padding: 10px; border-right: 1px solid #ddd;">${part.s1_uid}</td>
                    <td style="padding: 10px; border-right: 1px solid #ddd;">${part.s5_category || '-'}</td>
                    <td style="padding: 10px; border-right: 1px solid #ddd;">${part.s1_name}</td>
                    <td style="padding: 10px; border-right: 1px solid #ddd; text-align: right;">${parseInt(part.price).toLocaleString()}</td>
                    <td style="padding: 10px; border-right: 1px solid #ddd; text-align: center;"><input type="number" id="qty_${part.s1_uid}" value="1" min="1" style="width: 60px; padding: 5px;"></td>
                    <td style="padding: 10px; text-align: center;"><button type="button" onclick="addToCart(${part.s1_uid}, '${part.s1_name.replace(/'/g, "\\'")}', ${part.price})" style="background: #27ae60; color: white; padding: 5px 15px; border: none; border-radius: 3px; cursor: pointer;">추가</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function addToCart(partId, partName, cost) {
            const qty = parseInt(document.getElementById('qty_' + partId).value) || 1;

            const existingIndex = selectedParts.findIndex(item => item.part_id === partId);
            if (existingIndex >= 0) {
                selectedParts[existingIndex].quantity += qty;
            } else {
                selectedParts.push({
                    part_id: partId,
                    part_name: partName,
                    cost: cost,
                    quantity: qty
                });
            }

            document.getElementById('qty_' + partId).value = '1';
            updateSelectedPartsList();
            updateSubmitButtonVisibility();
        }

        function updateSelectedPartsList() {
            const tbody = document.getElementById('selectedPartsBody');
            tbody.innerHTML = '';

            if (selectedParts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">선택된 자재가 없습니다.</td></tr>';
                return;
            }

            selectedParts.forEach((item, index) => {
                const total = item.cost * item.quantity;
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #eee';
                tr.innerHTML = `
                    <td style="padding: 10px;">${item.part_name}</td>
                    <td style="padding: 10px;">${parseInt(item.cost).toLocaleString()}</td>
                    <td style="padding: 10px;"><input type="number" value="${item.quantity}" min="1" onchange="updateQuantity(${index}, this.value)" style="width: 60px; padding: 5px;"></td>
                    <td style="padding: 10px;">${parseInt(total).toLocaleString()}</td>
                    <td style="padding: 10px; text-align: center;"><button type="button" onclick="removeFromCart(${index})" style="background: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer;">삭제</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function updateQuantity(index, qty) {
            selectedParts[index].quantity = parseInt(qty) || 1;
            updateSelectedPartsList();
        }

        function removeFromCart(index) {
            selectedParts.splice(index, 1);
            updateSelectedPartsList();
            updateSubmitButtonVisibility();
        }

        // 제출 버튼 표시 상태 업데이트
        function updateSubmitButtonVisibility() {
            const submitButton = document.getElementById('submitButtonContainer');

            // 자재가 1개 이상 선택되었을 때만 표시
            if (selectedParts.length > 0) {
                submitButton.style.display = 'block';
            } else {
                submitButton.style.display = 'none';
            }
        }

        // Form 제출
        document.getElementById('repairForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const itemid = document.querySelector('input[name="itemid"]').value;
            const as_end_result = document.querySelector('select[name="as_end_result"]').value;
            const product_name = document.querySelector('select[name="product_name"]').value;

            if (!as_end_result) {
                alert('수리 방법을 선택하세요.');
                return;
            }

            if (selectedParts.length === 0) {
                alert('선택된 자재가 없습니다.');
                return;
            }

            // selectedParts를 JSON으로 변환하여 전송
            const partsData = JSON.stringify(selectedParts);

            // AJAX로 저장
            fetch('as_repair_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=save_repair_step&itemid=' + itemid + '&as_end_result=' + encodeURIComponent(as_end_result) + '&product_name=' + encodeURIComponent(product_name) + '&parts_data=' + encodeURIComponent(partsData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('수리 정보가 저장되었습니다.');
                        window.location.href = 'as_requests.php';
                    } else {
                        alert('오류: ' + (data.message || '알 수 없는 오류가 발생했습니다.'));
                    }
                })
                .catch(error => {
                    alert('통신 오류: ' + error);
                });
        });

        // 기존 등록된 자재 로드
        function loadExistingParts() {
            const existingPartsData = <?php echo json_encode($existing_parts ?? array()); ?>;

            if (existingPartsData && existingPartsData.length > 0) {
                selectedParts = existingPartsData;
                updateSelectedPartsList();
                updateSubmitButtonVisibility();
            }
        }

        // 페이지 로드 시 카테고리 초기화 및 기존 자재 로드
        window.addEventListener('DOMContentLoaded', () => {
            initializeCategories();
            loadParts();
            loadExistingParts();
        });
    </script>
</body>

</html>
<?php mysql_close($connect); ?>