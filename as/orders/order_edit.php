<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: ../login.php');
    exit;
}

// MySQL 호환성 레이어 로드
require_once '../mysql_compat.php';

// 데이터베이스 연결
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

$user_name = $_SESSION['member_id'];
$current_page = 'orders';

// 수정할 주문 ID
$sell_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sell_id <= 0) {
    die('유효하지 않은 주문 ID입니다.');
}

// 주문 정보 조회
$order_result = @mysql_query("SELECT s20_sellid, s20_meid, ex_company, s20_total_cost FROM step20_sell WHERE s20_sellid = $sell_id");
if (!$order_result || mysql_num_rows($order_result) == 0) {
    die('해당 주문을 찾을 수 없습니다.');
}

$order = mysql_fetch_assoc($order_result);

// 기존 카트 아이템 조회
$items_result = @mysql_query("SELECT s21_accid, s21_uid, s21_quantity, cost1 FROM step21_sell_cart WHERE s21_sellid = $sell_id");
$existing_items = array();
if ($items_result) {
    while ($row = mysql_fetch_assoc($items_result)) {
        $existing_items[] = $row;
    }
}

// AJAX 요청 처리
$action = isset($_GET['action']) ? $_GET['action'] : '';
$response = array('success' => false, 'message' => '');

// 삭제 기능
if ($action === 'delete_cart_item') {
    $accid = isset($_POST['accid']) ? trim($_POST['accid']) : '';

    if (empty($accid)) {
        $response['message'] = '유효하지 않은 항목입니다.';
        echo json_encode($response);
        exit;
    }

    // accid가 숫자인지 확인 (임시 ID의 경우 '-'를 포함할 수 있음)
    if (strpos($accid, '-') === false) {
        // 정상 ID
        $accid_int = intval($accid);
        if ($accid_int <= 0) {
            $response['message'] = '유효하지 않은 항목입니다.';
            echo json_encode($response);
            exit;
        }

        // step21_sell_cart에서 해당 항목 삭제
        $delete_query = "DELETE FROM step21_sell_cart WHERE s21_accid = $accid_int";
        $delete_result = @mysql_query($delete_query);

        if ($delete_result) {
            $response['success'] = true;
            $response['message'] = '항목이 삭제되었습니다.';

            // 총액 재계산
            $total_query = "SELECT SUM(cost1 * s21_quantity) as total FROM step21_sell_cart WHERE s21_sellid = $sell_id";
            $total_result = @mysql_query($total_query);
            if ($total_result) {
                $total_row = mysql_fetch_assoc($total_result);
                $new_total = $total_row['total'] ?? 0;

                // step20_sell의 총액 업데이트
                @mysql_query("UPDATE step20_sell SET s20_total_cost = $new_total WHERE s20_sellid = $sell_id");
                $response['new_total'] = $new_total;
            }
        } else {
            $response['message'] = '삭제 중 오류가 발생했습니다.';
        }
    } else {
        // 임시 ID (아직 DB에 저장되지 않은 항목) - 클라이언트에서만 처리
        $response['success'] = true;
        $response['message'] = '항목이 삭제되었습니다.';
    }
    echo json_encode($response);
    exit;
}

// 수량 업데이트
if ($action === 'update_quantity') {
    $accid = isset($_POST['accid']) ? trim($_POST['accid']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

    if (empty($accid) || $quantity <= 0) {
        $response['message'] = '유효하지 않은 요청입니다.';
        echo json_encode($response);
        exit;
    }

    // accid가 숫자인지 확인 (임시 ID의 경우 '-'를 포함할 수 있음)
    if (strpos($accid, '-') === false) {
        // 정상 ID
        $accid_int = intval($accid);
        if ($accid_int <= 0) {
            $response['message'] = '유효하지 않은 요청입니다.';
            echo json_encode($response);
            exit;
        }

        // 수량 업데이트
        $update_query = "UPDATE step21_sell_cart SET s21_quantity = $quantity WHERE s21_accid = $accid_int";
        $update_result = @mysql_query($update_query);

        if ($update_result) {
            $response['success'] = true;

            // 총액 재계산
            $total_query = "SELECT SUM(cost1 * s21_quantity) as total FROM step21_sell_cart WHERE s21_sellid = $sell_id";
            $total_result = @mysql_query($total_query);
            if ($total_result) {
                $total_row = mysql_fetch_assoc($total_result);
                $new_total = $total_row['total'] ?? 0;

                // step20_sell의 총액 업데이트
                @mysql_query("UPDATE step20_sell SET s20_total_cost = $new_total WHERE s20_sellid = $sell_id");
                $response['new_total'] = $new_total;
            }
        } else {
            $response['message'] = '업데이트 중 오류가 발생했습니다.';
        }
    } else {
        // 임시 ID (아직 DB에 저장되지 않은 항목) - 클라이언트에서만 처리
        $response['success'] = true;
        $response['message'] = '임시 항목 수량이 업데이트되었습니다.';
    }
    echo json_encode($response);
    exit;
}

// 자재 추가
if ($action === 'add_part') {
    $part_id = isset($_POST['part_id']) ? intval($_POST['part_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if ($part_id <= 0) {
        $response['message'] = '유효하지 않은 자재입니다.';
        echo json_encode($response);
        exit;
    }

    // 자재 가격 조회
    $part_query = @mysql_query("SELECT s1_cost_c_1 FROM step1_parts WHERE s1_uid = $part_id");
    if (!$part_query || mysql_num_rows($part_query) == 0) {
        $response['message'] = '자재을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }

    $part_row = mysql_fetch_assoc($part_query);
    $cost = floatval($part_row['s1_cost_c_1']);

    // 중복 체크: 같은 자재가 이미 존재하는지 확인
    $duplicate_query = "SELECT s21_accid, s21_quantity FROM step21_sell_cart WHERE s21_sellid = $sell_id AND s21_uid = $part_id";
    $duplicate_result = @mysql_query($duplicate_query);

    if ($duplicate_result && mysql_num_rows($duplicate_result) > 0) {
        // 중복된 자재가 존재 - 수량을 증가
        $duplicate_row = mysql_fetch_assoc($duplicate_result);
        $existing_accid = $duplicate_row['s21_accid'];
        $existing_qty = $duplicate_row['s21_quantity'];
        $new_qty = $existing_qty + $quantity;

        // 수량 업데이트
        $update_query = "UPDATE step21_sell_cart SET s21_quantity = $new_qty WHERE s21_accid = $existing_accid";
        $update_result = @mysql_query($update_query);

        if ($update_result) {
            $response['success'] = true;
            $response['is_duplicate'] = true;

            // 총액 재계산
            $total_query = "SELECT SUM(cost1 * s21_quantity) as total FROM step21_sell_cart WHERE s21_sellid = $sell_id";
            $total_result = @mysql_query($total_query);
            if ($total_result) {
                $total_row = mysql_fetch_assoc($total_result);
                $new_total = $total_row['total'] ?? 0;

                // step20_sell의 총액 업데이트
                @mysql_query("UPDATE step20_sell SET s20_total_cost = $new_total WHERE s20_sellid = $sell_id");
                $response['new_total'] = $new_total;
            }
        } else {
            $response['message'] = '수량 업데이트 중 오류가 발생했습니다.';
        }
    } else {
        // 새로운 자재 - 카트에 추가
        $insert_query = "INSERT INTO step21_sell_cart (s21_sellid, s21_uid, s21_quantity, cost1, cost_name) VALUES ($sell_id, $part_id, $quantity, $cost, '')";
        $insert_result = @mysql_query($insert_query);

        if ($insert_result) {
            $response['success'] = true;
            $response['is_duplicate'] = false;

            // 총액 재계산
            $total_query = "SELECT SUM(cost1 * s21_quantity) as total FROM step21_sell_cart WHERE s21_sellid = $sell_id";
            $total_result = @mysql_query($total_query);
            if ($total_result) {
                $total_row = mysql_fetch_assoc($total_result);
                $new_total = $total_row['total'] ?? 0;

                // step20_sell의 총액 업데이트
                @mysql_query("UPDATE step20_sell SET s20_total_cost = $new_total WHERE s20_sellid = $sell_id");
                $response['new_total'] = $new_total;
            }
        } else {
            $response['message'] = '자재 추가 중 오류가 발생했습니다.';
        }
    }
    echo json_encode($response);
    exit;
}

// 자재 목록 조회
if ($action === 'get_parts') {
    $search_key = isset($_POST['search_key']) ? trim($_POST['search_key']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';

    // WHERE 조건 구성
    $where = "1=1";

    // 카테고리 필터 (all이 아닐 때만 적용)
    if (!empty($category) && $category !== 'all') {
        $where .= " AND p.s1_caid = '" . mysql_real_escape_string($category) . "'";
    }

    // 검색어
    if (!empty($search_key)) {
        $search_esc = mysql_real_escape_string($search_key);
        $where .= " AND p.s1_name LIKE '%$search_esc%'";
    }

    $query = "SELECT p.s1_uid, p.s1_name, p.s1_caid, c.s5_category, p.s1_cost_c_1
              FROM step1_parts p
              LEFT JOIN step5_category c ON p.s1_caid = c.s5_caid
              WHERE $where
              ORDER BY p.s1_uid DESC
              LIMIT 50";

    $result = @mysql_query($query);
    $parts = array();

    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $parts[] = array(
                's1_uid' => $row['s1_uid'],
                's1_name' => $row['s1_name'],
                's1_caid' => $row['s1_caid'],
                's5_category' => $row['s5_category'],
                'price' => floatval($row['s1_cost_c_1'])
            );
        }
    }

    echo json_encode(['success' => true, 'parts' => $parts]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>구매 신청 수정 - AS 시스템</title>
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
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background: #764ba2;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #f0f4ff;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        button.delete-btn {
            background: #e74c3c;
            padding: 5px 10px;
            font-size: 12px;
        }

        button.delete-btn:hover {
            background: #c0392b;
        }

        .info-box {
            background: #f9f9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
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
            margin-right: 5px;
            margin-bottom: 10px;
        }

        .category-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message.show {
            display: block;
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
        <a href="../dashboard.php" class="nav-item">대시보드</a>
        <a href="../as_task/as_requests.php" class="nav-item">AS 작업</a>
        <a href="orders.php" class="nav-item <?php echo $current_page === 'orders' ? 'active' : ''; ?>">자재 판매</a>
        <a href="../parts/parts.php" class="nav-item">자재 관리</a>
        <a href="../customers/members.php" class="nav-item">고객 관리</a>
        <a href="../products/products.php" class="nav-item">제품 관리</a>
        <a href="../stat/statistics.php" class="nav-item">통계/분석</a>
    </div>

    <div class="container">
        <div class="content">
            <h2>📝 구매 신청 수정</h2>

            <div id="errorMessage" class="error-message"></div>
            <div id="successMessage" class="success-message"></div>

            <div class="info-box">
                <strong>주문 번호:</strong> <?php echo htmlspecialchars($sell_id); ?><br>
                <strong>업체명:</strong> <?php echo htmlspecialchars($order['ex_company']); ?><br>
                <strong>현재 총액:</strong> <span
                    id="currentTotal"><?php echo number_format($order['s20_total_cost']); ?></span>원
            </div>

            <!-- 기존 자재 목록 -->
            <div class="form-group">
                <label>현재 자재 목록</label>
                <table>
                    <thead>
                        <tr>
                            <th>자재명</th>
                            <th>단가</th>
                            <th>수량</th>
                            <th>합계</th>
                            <th style="width: 100px;">작업</th>
                        </tr>
                    </thead>
                    <tbody id="existingItemsBody">
                        <?php
                        if (!empty($existing_items)) {
                            foreach ($existing_items as $item) {
                                // 자재명 조회
                                $part_query = @mysql_query("SELECT s1_name FROM step1_parts WHERE s1_uid = {$item['s21_uid']}");
                                $part_name = $part_query && mysql_num_rows($part_query) > 0 ?
                                    mysql_result($part_query, 0, 0) : '자재 ID: ' . $item['s21_uid'];

                                $item_total = $item['cost1'] * $item['s21_quantity'];
                                ?>
                                <tr id="item_<?php echo $item['s21_accid']; ?>">
                                    <td><?php echo htmlspecialchars($part_name); ?></td>
                                    <td><?php echo number_format($item['cost1']); ?></td>
                                    <td>
                                        <input type="number" min="1" value="<?php echo $item['s21_quantity']; ?>"
                                            onchange="updateQuantity(<?php echo $item['s21_accid']; ?>, this.value)"
                                            style="width: 80px;">
                                    </td>
                                    <td id="total_<?php echo $item['s21_accid']; ?>"><?php echo number_format($item_total); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button class="delete-btn"
                                            onclick="deleteItem(<?php echo $item['s21_accid']; ?>)">삭제</button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <!-- 버튼 -->
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-submit" onclick="location.href='orders.php'">수정</button>
                <button type=" button" class="btn-cancel" onclick="location.href='orders.php'">취소</button>

            </div>
            <!-- 자재 추가 섹션 -->
            <div class="form-group">
                <label>자재 추가</label>

                <!-- 카테고리 버튼 -->
                <div style="margin-bottom: 10px;">
                    <button type="button" class="category-btn active" onclick="filterByCategory('all')">모든 카테고리</button>
                    <div id="categoryButtons" style="display: inline-block;"></div>
                </div>

                <!-- 검색 -->
                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <input type="text" id="partSearch" placeholder="자재명을 입력해주세요."
                        onkeypress="if(event.key==='Enter') searchParts();">
                    <button type="button" onclick="searchParts()" style="width: 100px;">검색</button>
                </div>

                <!-- 자재 테이블 -->
                <table>
                    <thead>
                        <tr>
                            <th>번호</th>
                            <th>카테고리</th>
                            <th>자재명</th>
                            <th>가격</th>
                            <th style="width: 80px;">수량</th>
                            <th style="width: 80px;">추가</th>
                        </tr>
                    </thead>
                    <tbody id="partsTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; color: #999;">자재을 검색해주세요.</td>
                        </tr>
                    </tbody>
                </table>
            </div>


        </div>
    </div>

    <script>
        let selectedCategory = 'all';

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

            // 페이지 로드 시 기본으로 모든 자재 로드
            loadParts();
        }

        function filterByCategory(categoryId) {
            selectedCategory = categoryId;

            // 모든 카테고리 버튼에서 active 제거
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // 선택된 카테고리에 해당하는 버튼에 active 추가
            if (categoryId === 'all') {
                // "모든 카테고리" 버튼에 active 추가
                document.querySelectorAll('.category-btn').forEach(btn => {
                    if (btn.textContent === '모든 카테고리') {
                        btn.classList.add('active');
                    }
                });
            } else {
                // 해당 카테고리 버튼에 active 추가
                document.querySelectorAll('.category-btn').forEach(btn => {
                    if (btn.textContent === categoryId) {
                        btn.classList.add('active');
                    }
                });
            }

            // 자재 로드
            loadParts();
        }

        function searchParts() {
            loadParts();
        }

        function loadParts() {
            const searchKey = document.getElementById('partSearch').value.trim();

            fetch('order_edit.php?id=<?php echo $sell_id; ?>&action=get_parts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'search_key=' + encodeURIComponent(searchKey) + '&category=' + encodeURIComponent(selectedCategory)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayPartsList(data.parts);
                    }
                });
        }

        function displayPartsList(parts) {
            const tbody = document.getElementById('partsTableBody');
            tbody.innerHTML = '';

            if (parts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">자재이 없습니다.</td></tr>';
                return;
            }

            parts.forEach(part => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${part.s1_uid}</td>
                    <td>${part.s5_category || '-'}</td>
                    <td>${part.s1_name}</td>
                    <td style="text-align: right;">${parseInt(part.price).toLocaleString()}</td>
                    <td><input type="number" id="qty_${part.s1_uid}" value="1" min="1" style="width: 60px;"></td>
                    <td style="text-align: center;"><button type="button" onclick="addPart(${part.s1_uid}, ${part.price})" style="background: #27ae60; padding: 5px 10px; font-size: 12px;">추가</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function addPart(partId, price) {
            const qty = parseInt(document.getElementById('qty_' + partId).value) || 1;

            // DB에 자재 추가 (중복 체크는 DB에서 수행)
            fetch('order_edit.php?id=<?php echo $sell_id; ?>&action=add_part', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'part_id=' + partId + '&quantity=' + qty
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // 중복인 경우 페이지 새로고침 (DB 내용과 UI 동기화)
                        if (data.is_duplicate) {
                            showSuccess('수량이 증가되었습니다.');
                            location.reload(); // UI 새로고침으로 DB와 동기화
                        } else {
                            // 새로운 자재인 경우 페이지 새로고침
                            showSuccess('자재이 추가되었습니다.');
                            location.reload(); // UI 새로고침으로 DB와 동기화
                        }
                    } else {
                        showError(data.message);
                    }
                });
        }

        function updateQuantity(accid, newQty) {
            const qty = parseInt(newQty) || 1;

            // 임시 항목인지 확인 (accid에 '-'가 포함되어 있으면 임시 ID)
            if (accid.toString().includes('-')) {
                // 클라이언트에서만 처리 (임시 항목)
                const row = document.getElementById('item_' + accid);
                if (row) {
                    // 해당 행에서 가격 찾기
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 2) {
                        const unitPrice = parseInt(cells[1].textContent.replace(/,/g, ''));
                        const newTotal = unitPrice * qty;
                        document.getElementById('total_' + accid).textContent = newTotal.toLocaleString();
                    }
                }
                showSuccess('수량이 업데이트되었습니다.');
                return;
            }

            fetch('order_edit.php?id=<?php echo $sell_id; ?>&action=update_quantity', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'accid=' + accid + '&quantity=' + qty
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        updateCurrentTotal(data.new_total);
                        showSuccess('수량이 업데이트되었습니다.');
                    } else {
                        showError(data.message);
                    }
                });
        }

        function deleteItem(accid) {
            if (!confirm('이 항목을 삭제하시겠습니까?')) {
                return;
            }

            // 임시 항목인지 확인 (accid에 '-'가 포함되어 있으면 임시 ID)
            if (accid.toString().includes('-')) {
                // 클라이언트에서만 처리 (임시 항목)
                document.getElementById('item_' + accid).remove();
                showSuccess('항목이 삭제되었습니다.');
                return;
            }

            fetch('order_edit.php?id=<?php echo $sell_id; ?>&action=delete_cart_item', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'accid=' + accid
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('item_' + accid).remove();
                        updateCurrentTotal(data.new_total);
                        showSuccess('항목이 삭제되었습니다.');
                    } else {
                        showError(data.message);
                    }
                });
        }

        function updateCurrentTotal(newTotal) {
            document.getElementById('currentTotal').textContent = parseInt(newTotal).toLocaleString();
        }

        function showError(msg) {
            const elem = document.getElementById('errorMessage');
            elem.textContent = msg;
            elem.classList.add('show');
            setTimeout(() => elem.classList.remove('show'), 5000);
        }

        function showSuccess(msg) {
            const elem = document.getElementById('successMessage');
            elem.textContent = msg;
            elem.classList.add('show');
            setTimeout(() => elem.classList.remove('show'), 5000);
        }

        // 초기화
        window.addEventListener('load', () => {
            initializeCategories();
        });
    </script>
</body>

</html>
<?php mysql_close($connect); ?>