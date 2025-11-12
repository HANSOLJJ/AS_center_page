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

// 요청 방식에 따른 처리
$action = isset($_GET['action']) ? $_GET['action'] : '';
$response = array('success' => false, 'message' => '');

// AJAX 요청 처리
if ($action === 'search_member') {
    $search_name = isset($_POST['search_name']) ? trim($_POST['search_name']) : '';

    if (!empty($search_name)) {
        $search_esc = mysql_real_escape_string($search_name);
        $result = @mysql_query("SELECT s11_meid, s11_com_name, s11_phone1, s11_phone2, s11_phone3, s11_sec FROM step11_member WHERE s11_com_name LIKE '%$search_esc%' LIMIT 10");

        if ($result && mysql_num_rows($result) > 0) {
            $members = array();
            while ($row = mysql_fetch_assoc($result)) {
                $members[] = $row;
            }
            $response['success'] = true;
            $response['members'] = $members;
        } else {
            $response['success'] = false;
            $response['message'] = '검색 결과가 없습니다. 새로 등록해주세요.';
        }
    }
    echo json_encode($response);
    exit;
}

if ($action === 'add_member') {
    $com_name = isset($_POST['com_name']) ? trim($_POST['com_name']) : '';
    $phone1 = isset($_POST['phone1']) ? trim($_POST['phone1']) : '';
    $phone2 = isset($_POST['phone2']) ? trim($_POST['phone2']) : '';
    $phone3 = isset($_POST['phone3']) ? trim($_POST['phone3']) : '';
    $sec = isset($_POST['sec']) ? trim($_POST['sec']) : '일반';

    if (empty($com_name) || empty($phone1) || empty($phone2) || empty($phone3)) {
        $response['message'] = '모든 항목을 입력해주세요.';
        echo json_encode($response);
        exit;
    }

    // 업체 종류 유효성 검사
    $valid_sec = array('일반', '대리점', '딜러');
    if (!in_array($sec, $valid_sec)) {
        $sec = '일반';
    }

    $com_name_esc = mysql_real_escape_string($com_name);
    $phone1_esc = mysql_real_escape_string($phone1);
    $phone2_esc = mysql_real_escape_string($phone2);
    $phone3_esc = mysql_real_escape_string($phone3);
    $sec_esc = mysql_real_escape_string($sec);

    $query = "INSERT INTO step11_member (s11_sec, s11_com_name, s11_phone1, s11_phone2, s11_phone3, s11_phone4, s11_phone5, s11_phone6, s11_com_num1, s11_com_num2, s11_com_num3, s11_com_zip1, s11_com_zip2, s11_oaddr, s11_com_sec1, s11_com_sec2) VALUES ('$sec_esc', '$com_name_esc', '$phone1_esc', '$phone2_esc', '$phone3_esc', '0', '0', '0', '000', '00', '00000', '000', '00', '', '', '')";

    $result = @mysql_query($query);
    if ($result) {
        $new_id = mysql_insert_id();
        $response['success'] = true;
        $response['member_id'] = $new_id;
        $response['com_name'] = $com_name;
        $response['message'] = '업체가 등록되었습니다.';
    } else {
        $response['message'] = '등록 중 오류가 발생했습니다.';
    }
    echo json_encode($response);
    exit;
}

// 삭제 기능
if ($action === 'delete_order') {
    $sell_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'request';

    if ($sell_id <= 0) {
        die('유효하지 않은 주문 ID입니다.');
    }

    // 고아 데이터 방지: 자식(step21_sell_cart)을 먼저 삭제
    $delete_cart_query = "DELETE FROM step21_sell_cart WHERE s21_sellid = $sell_id";
    $delete_cart_result = @mysql_query($delete_cart_query);

    if (!$delete_cart_result) {
        echo "<script>alert('카트 항목 삭제 중 오류가 발생했습니다: " . mysql_error() . "'); history.back();</script>";
        exit;
    }

    // 카트 삭제 성공 후 부모(step20_sell) 삭제
    $delete_order_query = "DELETE FROM step20_sell WHERE s20_sellid = $sell_id";
    $delete_order_result = @mysql_query($delete_order_query);

    if (!$delete_order_result) {
        echo "<script>alert('주문 삭제 중 오류가 발생했습니다: " . mysql_error() . "'); history.back();</script>";
        exit;
    }

    // 모두 성공
    header('Location: orders.php?tab=' . urlencode($tab) . '&deleted=1');
    exit;
}

if ($action === 'save_order') {
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;

    // JSON 형식의 items 문자열을 배열로 변환
    $items_json = isset($_POST['items']) ? $_POST['items'] : '[]';
    $items = json_decode($items_json, true);
    if (!is_array($items)) {
        $items = array();
    }

    // 디버깅 로그
    error_log("save_order 시작: member_id=$member_id, items_count=" . count($items));

    if (empty($member_id) || empty($items)) {
        $response['message'] = '업체와 자재를 선택해주세요. (member_id=' . $member_id . ', items=' . count($items) . ')';
        echo json_encode($response);
        exit;
    }

    // step20_sell에 주문 정보 저장
    $now = date('Y-m-d H:i:s');
    $ex_company = '';
    $ex_tel = '';

    // 업체명, 전화번호, 회원 ID, 회원 구분 조회
    $member_result = @mysql_query("SELECT s11_meid, s11_com_name, s11_phone1, s11_phone2, s11_phone3, s11_sec FROM step11_member WHERE s11_meid = $member_id");
    if ($member_result && mysql_num_rows($member_result) > 0) {
        $member_row = mysql_fetch_assoc($member_result);
        $s20_meid = intval($member_row['s11_meid']);
        $ex_company = $member_row['s11_com_name'];
        $ex_tel = $member_row['s11_phone1'] . '-' . $member_row['s11_phone2'] . '-' . $member_row['s11_phone3'];
        $mem_type = $member_row['s11_sec']; // 일반, 대리점, 딜러
    } else {
        error_log("업체 정보를 찾을 수 없음: member_id=$member_id");
        $s20_meid = 0;
        $mem_type = '';
    }

    $sell_in_date = $now;
    $as_level = '1'; // 요청 상태

    $mem_type_esc = mysql_real_escape_string($mem_type);
    $insert_query = "INSERT INTO step20_sell (s20_sell_in_date,s20_meid, ex_sec1, ex_company, ex_tel, s20_sell_level, s20_sell_center, s20_total_cost)
    VALUES ('$sell_in_date',  $s20_meid, '$mem_type_esc', '" . mysql_real_escape_string($ex_company) . "', '" . mysql_real_escape_string($ex_tel) . "', '$as_level', 'center1283763850', 0)";

    error_log("step20_sell insert query: $insert_query");

    $insert_result = @mysql_query($insert_query);
    if (!$insert_result) {
        $response['message'] = '주문 저장 중 오류가 발생했습니다: ' . mysql_error();
        error_log("step20_sell insert 실패: " . mysql_error());
        echo json_encode($response);
        exit;
    }

    error_log("step20_sell insert 성공");

    $sell_id = mysql_insert_id();
    $total_cost = 0;

    // 자재 항목 저장
    foreach ($items as $item) {
        $part_id = intval($item['part_id']);
        $quantity = intval($item['quantity']);

        if ($part_id <= 0 || $quantity <= 0)
            continue;

        // step1_parts에서 가격, 부품명 조회
        $part_query = @mysql_query("SELECT s1_cost_c_1, s1_name FROM step1_parts WHERE s1_uid = $part_id");
        $part_name = '';
        $cost = 0;
        if ($part_query && mysql_num_rows($part_query) > 0) {
            $part_row = mysql_fetch_assoc($part_query);
            $cost = floatval($part_row['s1_cost_c_1']);
            $part_name = mysql_real_escape_string($part_row['s1_name']);
        }

        $item_total = $cost * $quantity;
        $total_cost += $item_total;

        // step21_sell_cart에 저장 (s21_signdate는 NULL - 입금 확인 시 업데이트, cost_name은 부품명)
        $cart_query = "INSERT INTO step21_sell_cart (s21_sellid, s21_uid, s21_quantity, s21_signdate, cost1, cost_name) VALUES ($sell_id, $part_id, $quantity, NULL, $cost, '$part_name')";
        $cart_result = @mysql_query($cart_query);
        if (!$cart_result) {
            error_log("Cart insert error for sell_id=$sell_id, part_id=$part_id: " . mysql_error());
        }
    }

    // 총액 업데이트
    $update_query = "UPDATE step20_sell SET s20_total_cost = $total_cost WHERE s20_sellid = $sell_id";
    @mysql_query($update_query);

    $response['success'] = true;
    $response['message'] = '주문이 저장되었습니다.';
    $response['sell_id'] = $sell_id;
    echo json_encode($response);
    exit;
}

// 자재 목록 조회 (AJAX)
if ($action === 'get_parts') {
    $search_key = isset($_POST['search_key']) ? trim($_POST['search_key']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : 'all';
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    // 회원 구분 조회
    $sec = '';
    if ($member_id > 0) {
        $member_query = @mysql_query("SELECT s11_sec FROM step11_member WHERE s11_meid = $member_id");
        if ($member_query && mysql_num_rows($member_query) > 0) {
            $member_row = mysql_fetch_assoc($member_query);
            $sec = $member_row['s11_sec'];
        }
    }

    // WHERE 조건 구성
    $where = "1=1";

    // 카테고리 필터
    if ($category !== 'all' && !empty($category)) {
        $where .= " AND p.s1_caid = '" . mysql_real_escape_string($category) . "'";
    }

    // 검색어
    if (!empty($search_key)) {
        $search_esc = mysql_real_escape_string($search_key);
        $where .= " AND p.s1_name LIKE '%$search_esc%'";
    }

    // 총 개수 조회
    $count_query = "SELECT COUNT(*) as total FROM step1_parts p WHERE $where";
    $count_result = @mysql_query($count_query);
    $count_row = mysql_fetch_assoc($count_result);
    $total_count = intval($count_row['total']);
    $total_pages = ceil($total_count / $per_page);

    $query = "SELECT p.s1_uid, p.s1_name, p.s1_caid, c.s5_category,
                     p.s1_cost_c_1, p.s1_cost_a_2, p.s1_cost_n_2
              FROM step1_parts p
              LEFT JOIN step5_category c ON p.s1_caid = c.s5_caid
              WHERE $where
              ORDER BY p.s1_uid DESC
              LIMIT $per_page OFFSET $offset";

    $result = @mysql_query($query);
    $parts = array();

    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            // 회원 구분에 따라 가격 선택
            $price = 0;
            if ($sec === '일반') {
                $price = floatval($row['s1_cost_n_2']);
            } elseif ($sec === '대리점') {
                $price = floatval($row['s1_cost_a_2']);
            } else { // 딜러, AS센터공급가
                $price = floatval($row['s1_cost_c_1']);
            }

            $parts[] = array(
                's1_uid' => $row['s1_uid'],
                's1_name' => $row['s1_name'],
                's1_caid' => $row['s1_caid'],
                's5_category' => $row['s5_category'],
                'price' => $price
            );
        }
    }

    echo json_encode([
        'success' => true,
        'parts' => $parts,
        'page' => $page,
        'total_pages' => $total_pages,
        'total_count' => $total_count
    ]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>자재 판매 신청 - AS 시스템</title>
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

        .member-search {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .member-search input {
            flex: 1;
        }

        .member-search button {
            margin: 0;
            white-space: nowrap;
        }

        .member-info {
            background: #f9f9ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            margin-bottom: 20px;
            display: none;
        }

        .member-info.show {
            display: block;
        }

        .member-select {
            display: none;
            background: #f9f9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            max-height: 200px;
            overflow-y: auto;
        }

        .member-select.show {
            display: block;
        }

        .member-option {
            padding: 8px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            border-radius: 3px;
            cursor: pointer;
            background: white;
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
            color: #333;
        }

        .category-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .member-option:hover {
            background: #e8f0ff;
        }

        .member-option.selected {
            background: #667eea;
            color: white;
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
            <h2>🛒 자재 판매 요청</h2>

            <div id="successMessage" class="success-message"></div>
            <div id="errorMessage" class="error-message"></div>

            <!-- Step 1: 업체명 확인 -->
            <div class="step-container">
                <label>1단계: 업체명 검색</label>
                <div class="member-search">
                    <input type="text" id="searchMember" placeholder="업체명을 입력하세요"
                        onkeypress="if(event.key==='Enter') searchMember();">
                    <button onclick="searchMember()">검색</button>
                    <button onclick="showNewMemberForm()" style="background: #27ae60;">고객 등록</button>
                </div>
                <!-- 검색 결과 -->
                <div id="memberSelect" class="member-select"></div>

                <!-- 선택된 업체 정보 -->
                <div id="memberInfo" class="member-info">
                    <strong>선택된 업체:</strong> <span id="selectedMemberName"></span>
                    <br><strong>전화:</strong> <span id="selectedMemberPhone"></span>
                    <br><strong>고객타입:</strong> <span id="selectedMemberType"></span>
                    <input type="hidden" id="selectedMemberId">
                </div>

            </div>

            <!-- 새 업체 등록 폼 -->
            <div id="newMemberForm"
                style="display:none; background: #f9f9ff; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4>새 업체 등록</h4>
                <div class="form-group">
                    <label>업체명</label>
                    <input type="text" id="newComName" placeholder="업체명을 입력하세요">
                </div>
                <div class="form-group">
                    <label>전화번호</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="text" id="newPhone1" value="010" style="flex: 1;">
                        <span>-</span>
                        <input type="text" id="newPhone2" value="1234" style="flex: 1;">
                        <span>-</span>
                        <input type="text" id="newPhone3" value="5678" style="flex: 1;">
                    </div>
                </div>
                <div class="form-group">
                    <label>업체 종류</label>
                    <div style="display: flex; gap: 20px; margin-top: 10px;">
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                            <input type="radio" name="comSec" value="일반" checked style="cursor: pointer;">
                            일반
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                            <input type="radio" name="comSec" value="대리점" style="cursor: pointer;">
                            대리점
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                            <input type="radio" name="comSec" value="딜러" style="cursor: pointer;">
                            AS센터 공급가(딜러)
                        </label>
                    </div>
                </div>
                <button onclick="addNewMember()" style="background: #27ae60;">등록</button>
                <button onclick="cancelNewMemberForm()" style="background: #95a5a6;">취소</button>
            </div>

            <!-- Step 2: 자재 선택 -->
            <div id="step2Container" class="step-container blue" style="display: none;">
                <label>2단계: 자재 선택</label>
                <!--Preview: 선택된 자재 목록 -->
                <div id="previewContainer" class="form-group" style="display: none;">
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

                <!-- 자재 판매 등록 버튼 -->
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <div id="submitButtonContainer" style="display: none;">
                        <button onclick="saveOrder()" class="btn-submit">자재 판매 등록</button>
                    </div>
                    <button onclick="location.href='orders.php'" class="btn-cancel">취소</button>
                </div>
                <!-- 카테고리 필터 -->
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button onclick="filterByCategory('all')" class="category-btn active">모든 카테고리</button>
                        <div id="categoryButtons"></div>
                    </div>
                </div>

                <!-- 검색 -->
                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                    <input type="text" id="partSearch" placeholder="자재명 검색" style="flex: 1;"
                        onkeypress="if(event.key==='Enter') searchParts();">
                    <button onclick="searchParts()">검색</button>
                </div>

                <!-- 자재 테이블 -->
                <table class="parts-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #ddd;">
                            <th style=" padding: 10px; text-align: left; border-right: 1px solid #ddd;">번호
                            </th>
                            <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">카테고리
                            </th>
                            <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">자재명
                            </th>
                            <th style="padding: 10px; text-align: right; border-right: 1px solid #ddd;">가격
                            </th>
                            <th style="padding: 10px; text-align: center; border-right: 1px solid #ddd;">수량
                            </th>
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


        </div>
    </div>

    <script>
        let selectedMemberId = null;
        let selectedItems = [];

        function searchMember() {
            const searchName = document.getElementById('searchMember').value.trim();
            if (!searchName) {
                alert('업체명을 입력하세요.');
                return;
            }

            fetch('order_handler.php?action=search_member', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'search_name=' + encodeURIComponent(searchName)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayMemberList(data.members);
                    } else {
                        showError(data.message);
                        document.getElementById('memberSelect').innerHTML = '';
                    }
                });
        }

        function displayMemberList(members) {
            const select = document.getElementById('memberSelect');
            select.innerHTML = '';
            members.forEach(member => {
                const div = document.createElement('div');
                div.className = 'member-option';

                // 고객 타입 표시 (딜러는 특별히 표시)
                let typeDisplay = member.s11_sec;
                if (member.s11_sec === '딜러') {
                    typeDisplay = '딜러(AS center 공급가)';
                }

                div.innerHTML = `${member.s11_com_name} (${member.s11_phone1}-${member.s11_phone2}-${member.s11_phone3}) <span style="font-size: 12px; color: #999; margin-left: 8px;">${typeDisplay}</span>`;
                div.onclick = () => selectMember(member.s11_meid, member.s11_com_name, member.s11_phone1, member.s11_phone2, member.s11_phone3, member.s11_sec);
                select.appendChild(div);
            });
            select.classList.add('show');
        }

        function selectMember(id, name, phone1, phone2, phone3, sec) {
            selectedMemberId = id;
            document.getElementById('selectedMemberId').value = id;
            document.getElementById('selectedMemberName').textContent = name;
            document.getElementById('selectedMemberPhone').textContent = phone1 + '-' + phone2 + '-' + phone3;

            // 고객 타입 표시 (딜러는 특별히 표시)
            let typeDisplay = sec;
            if (sec === '딜러') {
                typeDisplay = '딜러(AS center 공급가)';
            }
            document.getElementById('selectedMemberType').textContent = typeDisplay;

            document.getElementById('memberInfo').classList.add('show');
            document.getElementById('memberSelect').classList.remove('show');
            document.getElementById('newMemberForm').style.display = 'none';
            document.getElementById('searchMember').value = '';
            showSuccess('업체가 선택되었습니다.');

            //preview 컨테이너, Step 2 컨테이너 표시
            document.getElementById('previewContainer').style.display = 'block';
            document.getElementById('step2Container').style.display = 'block';

            // 회원 선택 시 자재 목록 다시 로드 (가격 업데이트)
            loadParts();

            // 제출 버튼 표시 상태 업데이트
            updateSubmitButtonVisibility();
        }

        function showNewMemberForm() {
            document.getElementById('newMemberForm').style.display = 'block';
            document.getElementById('memberSelect').classList.remove('show');
        }

        function cancelNewMemberForm() {
            document.getElementById('newMemberForm').style.display = 'none';
        }

        function addNewMember() {
            const comName = document.getElementById('newComName').value.trim();
            const phone1 = document.getElementById('newPhone1').value.trim();
            const phone2 = document.getElementById('newPhone2').value.trim();
            const phone3 = document.getElementById('newPhone3').value.trim();
            const sec = document.querySelector('input[name="comSec"]:checked').value;

            if (!comName || !phone1 || !phone2 || !phone3) {
                showError('모든 항목을 입력해주세요.');
                return;
            }

            fetch('order_handler.php?action=add_member', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'com_name=' + encodeURIComponent(comName) + '&phone1=' + encodeURIComponent(phone1) + '&phone2=' + encodeURIComponent(phone2) + '&phone3=' + encodeURIComponent(phone3) + '&sec=' + encodeURIComponent(sec)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        selectMember(data.member_id, comName, phone1, phone2, phone3);
                        document.getElementById('newMemberForm').style.display = 'none';
                        document.getElementById('newComName').value = '';
                        document.getElementById('newPhone1').value = '';
                        document.getElementById('newPhone2').value = '';
                        document.getElementById('newPhone3').value = '';
                        document.querySelector('input[name="comSec"]').checked = true;
                        showSuccess(data.message);
                    } else {
                        showError(data.message);
                    }
                });
        }

        let selectedCategory = 'all';

        // 제출 버튼 표시 상태 업데이트
        function updateSubmitButtonVisibility() {
            const submitButton = document.getElementById('submitButtonContainer');

            // 업체명이 선택되고 자재가 1개 이상 추가되었을 때만 표시
            if (selectedMemberId && selectedItems.length > 0) {
                submitButton.style.display = 'flex';
            } else {
                submitButton.style.display = 'none';
            }
        }

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

        let currentPage = 1;
        let totalPages = 1;

        function loadParts(page = 1) {
            const searchKey = document.getElementById('partSearch').value.trim();
            const memberId = selectedMemberId || 0;
            currentPage = page;

            fetch('order_handler.php?action=get_parts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'search_key=' + encodeURIComponent(searchKey) + '&category=' + encodeURIComponent(selectedCategory) + '&member_id=' + memberId + '&page=' + page
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        totalPages = data.total_pages || 1;
                        displayPartsList(data.parts);
                        displayPagination(data.page, data.total_pages);
                    } else {
                        console.error('자재 검색 실패:', data);
                    }
                })
                .catch(error => {
                    console.error('자재 검색 에러:', error);
                });
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
                    <td style="padding: 10px; text-align: center;"><button onclick="addToCart(${part.s1_uid}, '${part.s1_name.replace(/'/g, "\\'")}', ${part.price})" style="background: #27ae60; color: white; padding: 5px 15px; border: none; border-radius: 3px; cursor: pointer;">추가</button></td>
                `;
                tbody.appendChild(tr);
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

        function addToCart(partId, partName, cost) {
            const qty = parseInt(document.getElementById('qty_' + partId).value) || 1;

            const existingIndex = selectedItems.findIndex(item => item.part_id === partId);
            if (existingIndex >= 0) {
                selectedItems[existingIndex].quantity += qty;
            } else {
                selectedItems.push({
                    part_id: partId,
                    part_name: partName,
                    cost: cost,
                    quantity: qty
                });
            }

            document.getElementById('qty_' + partId).value = '1';
            updateSelectedPartsList();
            updateSubmitButtonVisibility();
            showSuccess('자재가 추가되었습니다.');
        }

        function updateSelectedPartsList() {
            const tbody = document.getElementById('selectedPartsBody');
            tbody.innerHTML = '';

            if (selectedItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">선택된 자재가 없습니다.</td></tr>';
                return;
            }

            selectedItems.forEach((item, index) => {
                const total = item.cost * item.quantity;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.part_name}</td>
                    <td>${parseInt(item.cost).toLocaleString()}</td>
                    <td><input type="number" value="${item.quantity}" min="1" onchange="updateQuantity(${index}, this.value)" style="width: 80px;"></td>
                    <td>${parseInt(total).toLocaleString()}</td>
                    <td><button onclick="removeFromCart(${index})" style="background: #e74c3c;">삭제</button></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function updateQuantity(index, qty) {
            selectedItems[index].quantity = parseInt(qty) || 1;
            updateSelectedPartsList();
        }

        function removeFromCart(index) {
            selectedItems.splice(index, 1);
            updateSelectedPartsList();
            updateSubmitButtonVisibility();
        }

        function saveOrder() {
            // 버튼 중복 클릭 방지
            const submitBtn = document.querySelector('button[onclick="saveOrder()"]');
            if (submitBtn.disabled) {
                return;
            }

            if (!selectedMemberId) {
                alert('업체를 선택해주세요.');
                return;
            }

            if (selectedItems.length === 0) {
                alert('자재를 선택해주세요.');
                return;
            }

            // 버튼 비활성화
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
            const originalText = submitBtn.textContent;
            submitBtn.textContent = '등록 중...';

            // items 배열을 JSON 형식으로 변환
            const itemsArray = selectedItems.map(item => ({
                part_id: item.part_id,
                quantity: item.quantity
            }));

            // URL Encoded 형식으로 변환
            let body = 'member_id=' + encodeURIComponent(selectedMemberId) +
                '&items=' + encodeURIComponent(JSON.stringify(itemsArray));

            fetch('order_handler.php?action=save_order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        setTimeout(() => {
                            location.href = 'orders.php?tab=request';
                        }, 1500);
                    } else {
                        alert('❌ ' + data.message);
                        // 실패 시 버튼 복구
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('에러 발생:', error);
                    alert('요청 중 오류가 발생했습니다: ' + error);
                    // 실패 시 버튼 복구
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                    submitBtn.textContent = originalText;
                });
        }

        function showSuccess(msg) {
            const el = document.getElementById('successMessage');
            el.textContent = msg;
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 4000);
        }

        function showError(msg) {
            const el = document.getElementById('errorMessage');
            el.textContent = msg;
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 4000);
        }

        // 초기 자재 목록 로드
        window.addEventListener('DOMContentLoaded', () => {
            initializeCategories();
            loadParts();
        });
    </script>
</body>

</html>
<?php mysql_close($connect); ?>