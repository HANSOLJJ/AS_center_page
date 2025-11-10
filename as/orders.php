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
$current_page = 'orders';

// 탭 선택
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'request';

// 삭제 메시지 초기화
$success_message = '';
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $success_message = '✓ 정상적으로 삭제되었습니다.';
}

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 기간 필터 설정
$range = isset($_GET['range']) ? $_GET['range'] : '';
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');
$year_start = date('Y-01-01');

// 검색 조건 (GET으로 받아서 검색 유지)
$search_customer = isset($_GET['search_customer']) ? trim($_GET['search_customer']) : (isset($_POST['search_customer']) ? trim($_POST['search_customer']) : '');
$search_phone = isset($_GET['search_phone']) ? trim($_GET['search_phone']) : (isset($_POST['search_phone']) ? trim($_POST['search_phone']) : '');
$search_start_date = isset($_GET['search_start_date']) ? $_GET['search_start_date'] : (isset($_POST['search_start_date']) ? $_POST['search_start_date'] : '');
$search_end_date = isset($_GET['search_end_date']) ? $_GET['search_end_date'] : (isset($_POST['search_end_date']) ? $_POST['search_end_date'] : '');

// WHERE 조건 생성
$where = "1=1";

// 탭에 따른 상태 필터
if ($tab == 'request') {
    $where .= " AND s20_sell_level != '2'";
} elseif ($tab == 'completed') {
    $where .= " AND s20_sell_level = '2'";
}

// 기간 검색 (탭별로 다른 날짜 필드 사용)
// 판매요청: s20_sell_in_date (접수일자), 판매완료: s20_sell_out_date (완료일자)
$date_field = ($tab == 'completed') ? 's20_sell_out_date' : 's20_sell_in_date';

if (!empty($search_start_date)) {
    $where .= " AND DATE($date_field) >= '" . mysql_real_escape_string($search_start_date) . "'";
}
if (!empty($search_end_date)) {
    $where .= " AND DATE($date_field) <= '" . mysql_real_escape_string($search_end_date) . "'";
}

// 고객명 검색
if (!empty($search_customer)) {
    $where .= " AND ex_company LIKE '%" . mysql_real_escape_string($search_customer) . "%'";
}

// 전화번호 검색
if (!empty($search_phone)) {
    $phone_esc = mysql_real_escape_string($search_phone);
    $where .= " AND (CONCAT(ex_tel) LIKE '%" . $phone_esc . "%')";
}

// 전체 개수 조회 (중복 제거를 위해 DISTINCT 사용)
$count_result = @mysql_query("SELECT COUNT(DISTINCT s20_sellid) as cnt FROM step20_sell WHERE $where");
if (!$count_result) {
    $total_count = 0;
    $total_pages = 0;
    $count_row = array('cnt' => 0);
} else {
    $count_row = mysql_fetch_assoc($count_result);
    $total_count = $count_row['cnt'] ?? 0;
    $total_pages = ceil($total_count / $limit);
}

// Step 1: 페이지네이션된 s20_sellid 목록 먼저 조회
// 탭별로 다른 정렬 기준 사용: 판매요청은 접수일자, 판매완료는 완료일자
$order_by = ($tab == 'completed')
    ? "s20_sell_out_date DESC, s20_sellid DESC"
    : "s20_sell_in_date DESC, s20_sellid DESC";

$id_result = @mysql_query("
    SELECT s20_sellid, s20_sell_in_date, s20_sell_out_date
    FROM step20_sell
    WHERE $where
    GROUP BY s20_sellid
    ORDER BY $order_by
    LIMIT $offset, $limit
");

// 조회된 ID들을 배열로 저장
$sellid_list = array();
if ($id_result) {
    while ($row = mysql_fetch_assoc($id_result)) {
        $sellid_list[] = $row['s20_sellid'];
    }
}

// 데이터를 미리 그룹핑해서 배열로 저장 (두 탭이 같은 데이터를 사용할 수 있도록)
$sales_data = array();

if (!empty($sellid_list)) {
    $sellid_str = implode(',', array_map('intval', $sellid_list));

    // Step 2: 해당 ID들의 모든 데이터와 카트 아이템 조회
    // 탭별로 다른 정렬 기준 사용: 판매요청은 접수일자, 판매완료는 완료일자
    $order_by_step2 = ($tab == 'completed')
        ? "s20_sell_out_date DESC, s20_sellid DESC, s21_accid ASC"
        : "s20_sell_in_date DESC, s20_sellid DESC, s21_accid ASC";

    $result = @mysql_query("
        SELECT s20_sellid, s20_sell_in_date, s20_sell_out_date, s20_sell_out_no, s20_sell_out_no2, ex_company, ex_tel, s20_sell_level, s20_total_cost,
               s21_accid, s21_uid, p.s1_name, cost_name, s21_quantity, cost1
        FROM step20_sell
        LEFT JOIN step21_sell_cart ON s20_sellid = CAST(s21_sellid AS UNSIGNED)
        LEFT JOIN step1_parts p ON s21_uid = p.s1_uid
        WHERE s20_sellid IN ($sellid_str)
        ORDER BY $order_by_step2
    ");

    // 결과를 그룹핑해서 배열로 저장
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $sellid = $row['s20_sellid'];
            if (!isset($sales_data[$sellid])) {
                $sales_data[$sellid] = array(
                    'info' => $row,
                    'items' => array()
                );
            }
            if ($row['s21_accid']) {
                $sales_data[$sellid]['items'][] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>자재 판매 - AS 시스템</title>
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
            max-width: 100%;
            margin: 0;
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin: 0 40px;
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
        }

        /* 성공 메시지 스타일 */
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
            border: 1px solid #c3e6cb;
        }

        .message.show {
            display: block;
        }

        /* 탭 스타일 */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f9f9ff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .search-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .date-filter-buttons {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }

        .date-filter-btn {
            padding: 8px 16px;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s;
        }

        .date-filter-btn:hover {
            background: #f0f4ff;
        }

        .date-filter-btn.active {
            background: #667eea;
            color: white;
        }

        .date-filter-controls {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .search-box input[type="text"],
        .search-box input[type="date"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 150px;
        }

        .search-box button[type="submit"],
        .search-box button[type="button"] {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            white-space: nowrap;
        }

        .search-box button[type="submit"]:hover,
        .search-box button[type="button"]:hover {
            background: #5568d3;
        }

        .search-box a {
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            white-space: nowrap;
        }

        .search-box a:hover {
            background: #7f8c8d;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .register-btn {
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
        }

        .register-btn:hover {
            background: #229954;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            font-size: 13px;
        }

        th,
        td {
            padding: 10px 8px;
            text-align: center;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            word-break: break-word;
        }

        th:last-child,
        td:last-child {
            border-right: none;
        }

        th {
            background: #f0f4ff;
            color: #667eea;
            font-weight: 600;
        }

        /* 테이블 행 transition */
        table.orders tbody tr {
            transition: background 0.3s ease;
        }

        /* rowspan이 있는 셀들 스타일 */
        td[rowspan] {
            vertical-align: middle;
            text-align: center;
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

        .info-text {
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
        }

        /* 공통: 고정 레이아웃 */
        table.orders {
            table-layout: fixed;
            width: 100%;
        }

        table.orders thead tr:nth-child(1) th {
            background: #667eea;
            color: white;
            font-weight: 700;
            padding: 12px 8px;
        }

        table.orders thead tr:nth-child(2) th {
            background: #667eea;
            font-weight: 600;
            font-size: 12px;
            color: white;
            padding: 8px;
        }


        /* 공통: 판매요청 탭 기준의 열 폭(왼쪽 5열 + 판매목록 3열 = 절대 고정) */
        table.orders col.c-no {
            width: 6%;
        }

        /* 번호 */

        table.orders col.c-date {
            width: 10%;
        }

        /* 접수일자 */

        table.orders col.c-company {
            width: 15%;
        }

        /* 업체명 */

        table.orders col.c-phone {
            width: 12%;
        }

        /* 전화번호 */

        table.orders col.c-partname {
            width: auto%;
        }

        /* 자재명(가변) */

        table.orders col.c-qty {
            width: 3%;
        }

        /* 수량(좁게) */

        table.orders col.c-parttotal {
            width: 5%;
        }

        /* 자재별 총액(좁게) */

        table.orders col.c-grandtotal {
            width: 8%;
        }

        /* 총액(좁게) */

        table.orders col.c-edit {
            width: 5%;
        }

        /* 관리-수정 */

        table.orders col.c-del {
            width: 5%;
        }

        /* 관리-삭제 */

        table.orders col.c-done {
            width: 5%;
        }

        /* 관리-완료 */

        table.orders.orders--completed col.c-receipt {
            width: 6%;
        }

        /* 영수증 */

        table.orders col.c-cancel {
            width: 5%;
        }

        /* 취소 */


        /* 숫자/전화번호 보기 좋게 */
        table.orders .ta-right {
            text-align: right;
        }

        table.orders .ta-center {
            text-align: center;
        }

        table.orders .nowrap {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* 행 배경색 - data-bg-index 속성 기반 */
        table.orders tbody tr[data-bg-index="0"] {
            background: #ffffff;
        }

        table.orders tbody tr[data-bg-index="1"] {
            background: #f5f5f5;
        }

        table.orders tbody tr[data-bg-index="2"] {
            background: #f0f7ff;
        }

        table.orders tbody tr[data-bg-index="3"] {
            background: #f0fff0;
        }

        /* 행 Hover 효과 - group-hover 클래스 */
        table.orders tbody tr.group-hover[data-bg-index="0"] {
            background: #efefef !important;
        }

        table.orders tbody tr.group-hover[data-bg-index="1"] {
            background: #e8e8e8 !important;
        }

        table.orders tbody tr.group-hover[data-bg-index="2"] {
            background: #d9e9ff !important;
        }

        table.orders tbody tr.group-hover[data-bg-index="3"] {
            background: #d9ffd9 !important;
        }

        /* 추가 자재행 경계선 유지 */
        tr.item-row td:last-child {
            border-right: 1px solid #ddd !important;
        }



        .action-btn {
            display: inline-block;
            padding: 8px 10px;
            font-size: 12px;
            line-height: 1.2;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
            box-sizing: border-box;
            text-decoration: none;

        }

        .action-btn.edit {
            background: #3498db;
        }

        .action-btn.edit:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .action-btn.delete {
            background: #e74c3c;
        }

        .action-btn.delete:hover {
            background: #c0392b;
            transform: scale(1.05);
        }

        .action-btn.cancel {
            background: #e74c3c;
        }

        .action-btn.cancel:hover {
            background: #c0392b;
            transform: scale(1.05);
        }

        .action-btn.done {
            background: #27ae60;
        }

        .action-btn.done:hover {
            background: #27ae60;
            transform: scale(1.05);
        }

        .action-btn.view {
            background: #27ae60;
        }

        .action-btn.view:hover {
            background: #27ae60;
            transform: scale(1.05);
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
        <a href="as_requests.php" class="nav-item">AS 작업</a>
        <a href="orders.php" class="nav-item <?php echo $current_page === 'orders' ? 'active' : ''; ?>">자재 판매</a>
        <a href="parts.php" class="nav-item">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
        <a href="as_statistics.php" class="nav-item">통계/분석</a>
    </div>

    <div class="container">
        <div class="content">
            <h2>🔋 자재 판매</h2>

            <!-- 성공 메시지 표시 -->
            <?php if (!empty($success_message)): ?>
                <div class="message show">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- 탭 버튼 -->
            <div class="tabs">
                <button class="tab-btn <?php echo $tab === 'request' ? 'active' : ''; ?>"
                    onclick="switchTab('request')">판매 요청</button>
                <button class="tab-btn <?php echo $tab === 'completed' ? 'active' : ''; ?>"
                    onclick="switchTab('completed')">판매 완료</button>
            </div>

            <!-- 판매 요청 탭 -->
            <div class="tab-content <?php echo $tab === 'request' ? 'active' : ''; ?>" id="tab-request">
                <!-- 검색 박스 -->
                <form method="GET" class="search-box date-filter" id="search-form-request">
                    <input type="hidden" name="tab" value="request">

                    <div class="date-filter-buttons">
                        <button type="button" class="date-filter-btn <?php echo $range === 'today' ? 'active' : ''; ?>" onclick="setOrderDateRange('today', 'search-form-request')">오늘</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'week' ? 'active' : ''; ?>" onclick="setOrderDateRange('week', 'search-form-request')">금주</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'month' ? 'active' : ''; ?>" onclick="setOrderDateRange('month', 'search-form-request')">금월</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'year' ? 'active' : ''; ?>" onclick="setOrderDateRange('year', 'search-form-request')">금년</button>
                    </div>

                    <div class="date-filter-controls">
                        <input type="date" name="search_start_date" placeholder="시작 날짜"
                            value="<?php echo htmlspecialchars($search_start_date); ?>">
                        <span style="color: #999;">~</span>
                        <input type="date" name="search_end_date" placeholder="종료 날짜"
                            value="<?php echo htmlspecialchars($search_end_date); ?>">
                        <input type="text" name="search_customer" placeholder="고객명"
                            value="<?php echo htmlspecialchars($search_customer); ?>">
                        <input type="text" name="search_phone" placeholder="전화번호"
                            value="<?php echo htmlspecialchars($search_phone); ?>">
                        <input type="hidden" name="range" value="<?php echo htmlspecialchars($range); ?>">
                        <button type="submit">검색</button>
                        <a href="orders.php?tab=request"
                            style="padding: 10px 20px; background: #95a5a6; color: white; border-radius: 5px; text-decoration: none;">초기화</a>
                    </div>
                </form>

                <script>
                function setOrderDateRange(range, formId) {
                    const form = document.getElementById(formId);
                    const today = new Date();
                    let startDate, endDate;

                    const year = today.getFullYear();
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const day = String(today.getDate()).padStart(2, '0');
                    const todayStr = year + '-' + month + '-' + day;

                    if (range === 'today') {
                        startDate = todayStr;
                        endDate = todayStr;
                    } else if (range === 'week') {
                        const dayOfWeek = today.getDay();
                        const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
                        const monday = new Date(today.setDate(diff));
                        startDate = monday.getFullYear() + '-' + String(monday.getMonth() + 1).padStart(2, '0') + '-' + String(monday.getDate()).padStart(2, '0');
                        endDate = todayStr;
                    } else if (range === 'month') {
                        startDate = year + '-' + month + '-01';
                        endDate = todayStr;
                    } else if (range === 'year') {
                        startDate = year + '-01-01';
                        endDate = todayStr;
                    }

                    form.search_start_date.value = startDate;
                    form.search_end_date.value = endDate;
                    form.range.value = range;
                    form.submit();
                }
                </script>

                <!-- 액션 버튼 -->
                <div class="action-buttons">
                    <button class="register-btn" onclick="location.href='order_handler.php'">+ NEW 판매 요청 등록</button>
                </div>

                <!-- 정보 텍스트 -->
                <div class="info-text">
                    총 <?php echo $total_count; ?>개의 판매 요청 (페이지:
                    <?php echo $page; ?>/<?php echo max(1, $total_pages); ?>)
                </div>

                <!-- 테이블 -->
                <table class="orders orders--request">
                    <colgroup>
                        <col class="c-no">
                        <col class="c-date">
                        <col class="c-company">
                        <col class="c-phone">
                        <col class="c-partname">
                        <col class="c-qty">
                        <col class="c-parttotal">
                        <col class="c-grandtotal">
                        <col class="c-edit">
                        <col class="c-del">
                        <col class="c-done">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>번호</th>
                            <th>접수일자</th>
                            <th>업체명</th>
                            <th>전화번호</th>
                            <th colspan="2">판매 품목</th>
                            <th colspan="2">가격</th>
                            <th colspan="3">관리</th>
                        </tr>
                        <tr style="background: #f5f5f5; font-weight: 500;">
                            <th colspan="4"></th>
                            <th style="flex: 2;">자재명</th>
                            <th>수량</th>
                            <th>자재별</th>
                            <th>총액</th>
                            <th>판매 완료</th>
                            <th>수정</th>
                            <th>삭제</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($sales_data)) {
                            // 판매 건마다 표시
                            $display_count = $total_count - $offset;
                            $sale_index = 0;
                            $bg_colors = array('#ffffff', '#f5f5f5', '#f0f7ff', '#f0fff0');
                            foreach ($sales_data as $sellid => $data) {
                                $row = $data['info'];
                                $items = $data['items'];
                                $sell_date = $row['s20_sell_in_date'] ? date('Y-m-d', strtotime($row['s20_sell_in_date'])) : '-';

                                // 판매 건별 배경색 순환 적용 (4가지 색)
                                $bg_color = $bg_colors[$sale_index % 4];

                                // 자재 총액의 합 계산
                                $total_price = 0;
                                foreach ($items as $item) {
                                    $item_price = ($item['cost1'] ?? 0) * ($item['s21_quantity'] ?? 0);
                                    $total_price += $item_price;
                                }

                                // 첫 번째 자재 행
                                if (!empty($items)) {
                                    $first_item = $items[0];
                                    $item_total = ($first_item['cost1'] ?? 0) * ($first_item['s21_quantity'] ?? 0);

                                    // 아이템이 1개만 있으면 첫 번째 행에 경계선 추가
                                    $is_single_item = count($items) === 1;
                                    $first_border = $is_single_item ? 'border-bottom: 2px solid #bbb;' : '';

                                    echo "<tr style='$first_border' data-bg-index='" . ($sale_index % 4) . "' data-sellid='$sellid'>";
                                    echo "<td rowspan='" . count($items) . "'>" . $display_count . "</td>";
                                    echo "<td rowspan='" . count($items) . "'>" . htmlspecialchars($sell_date) . "</td>";
                                    echo "<td rowspan='" . count($items) . "'>" . htmlspecialchars($row['ex_company']) . "</td>";
                                    echo "<td rowspan='" . count($items) . "'>" . htmlspecialchars($row['ex_tel'] ?? '-') . "</td>";
                                    echo "<td style='font-weight: bold;'>" . htmlspecialchars($first_item['s1_name'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($first_item['s21_quantity'] ?? '-') . "</td>";
                                    echo "<td>" . number_format($item_total) . "</td>";
                                    echo "<td rowspan='" . count($items) . "' style='font-weight: bold;'>" . number_format($total_price) . "</td>";
                                    echo "<td rowspan='" . count($items) . "'><button onclick='completeSale(" . $row['s20_sellid'] . ")' class='action-btn done'>완료</button></td>";
                                    echo "<td rowspan='" . count($items) . "'><a href='order_edit.php?id=" . $row['s20_sellid'] . "' class='action-btn edit'>수정</a></td>";
                                    echo "<td rowspan='" . count($items) . "'><a href='order_handler.php?action=delete_order&id=" . $row['s20_sellid'] . "&tab=request' class='action-btn delete' onclick=\"return confirm('삭제하시겠습니까?');\">삭제</a></td>";
                                    echo "</td>";
                                    echo "</tr>";

                                    // 나머지 자재 행들
                                    for ($i = 1; $i < count($items); $i++) {
                                        $item = $items[$i];
                                        $item_total = ($item['cost1'] ?? 0) * ($item['s21_quantity'] ?? 0);
                                        $is_last_item = ($i === count($items) - 1);
                                        $border_style = $is_last_item ? 'border-bottom: 2px solid #bbb;' : '';

                                        echo "<tr class='item-row' style='$border_style' data-bg-index='" . ($sale_index % 4) . "' data-sellid='$sellid'>";
                                        echo "<td style='font-weight: bold;'>" . htmlspecialchars($item['s1_name'] ?? '-') . "</td>";
                                        echo "<td>" . htmlspecialchars($item['s21_quantity'] ?? '-') . "</td>";
                                        echo "<td>" . number_format($item_total) . "</td>";
                                        echo "</tr>";
                                    }

                                } else {
                                    // 자재 없는 경우
                                    echo "<tr style='border-bottom: 2px solid #bbb;' data-bg-index='" . ($sale_index % 4) . "' data-sellid='$sellid'>";
                                    echo "<td>" . $display_count . "</td>";
                                    echo "<td>" . htmlspecialchars($sell_date) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ex_company']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ex_tel'] ?? '-') . "</td>";
                                    echo "<td colspan='3' style='color: #999;'>-</td>";
                                    echo "<td style='font-weight: bold;'>" . number_format($total_price) . "</td>";
                                    echo "<td><button onclick='completeSale(" . $row['s20_sellid'] . ")' class='action-btn done'>완료</button></td>";
                                    echo "<td><a href='order_edit.php?id=" . $row['s20_sellid'] . "' class='action-btn edit'>수정</a></td>";
                                    echo "<td><a href='order_handler.php?action=delete_order&id=" . $row['s20_sellid'] . "&tab=request' class='action-btn delete' onclick=\"return confirm('삭제하시겠습니까?');\">삭제</a></td>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                $display_count--;
                                $sale_index++;
                            }
                        } else {
                            echo "<tr><td colspan='11' style='text-align: center; color: #999;'>판매 요청이 없습니다.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- 페이지네이션 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $search_params = '&tab=request';
                        if ($search_start_date)
                            $search_params .= '&search_start_date=' . urlencode($search_start_date);
                        if ($search_end_date)
                            $search_params .= '&search_end_date=' . urlencode($search_end_date);
                        if ($search_customer)
                            $search_params .= '&search_customer=' . urlencode($search_customer);
                        if ($search_phone)
                            $search_params .= '&search_phone=' . urlencode($search_phone);

                        if ($page > 1) {
                            echo "<a href='orders.php?page=" . ($page - 1) . $search_params . "'>← 이전</a>";
                        }

                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            echo "<a href='orders.php?page=1" . $search_params . "'>1</a>";
                            if ($start_page > 2)
                                echo "<span>...</span>";
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo "<span class='current'>" . $i . "</span>";
                            } else {
                                echo "<a href='orders.php?page=" . $i . $search_params . "'>" . $i . "</a>";
                            }
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1)
                                echo "<span>...</span>";
                            echo "<a href='orders.php?page=" . $total_pages . $search_params . "'>" . $total_pages . "</a>";
                        }

                        if ($page < $total_pages) {
                            echo "<a href='orders.php?page=" . ($page + 1) . $search_params . "'>다음 →</a>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 판매 완료 탭 -->
            <div class="tab-content <?php echo $tab === 'completed' ? 'active' : ''; ?>" id="tab-completed">
                <!-- 검색 박스 -->
                <form method="GET" class="search-box date-filter" id="search-form-completed">
                    <input type="hidden" name="tab" value="completed">

                    <div class="date-filter-buttons">
                        <button type="button" class="date-filter-btn <?php echo $range === 'today' ? 'active' : ''; ?>" onclick="setOrderDateRange('today', 'search-form-completed')">오늘</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'week' ? 'active' : ''; ?>" onclick="setOrderDateRange('week', 'search-form-completed')">금주</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'month' ? 'active' : ''; ?>" onclick="setOrderDateRange('month', 'search-form-completed')">금월</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'year' ? 'active' : ''; ?>" onclick="setOrderDateRange('year', 'search-form-completed')">금년</button>
                    </div>

                    <div class="date-filter-controls">
                        <input type="date" name="search_start_date" placeholder="시작 날짜"
                            value="<?php echo htmlspecialchars($search_start_date); ?>">
                        <span style="color: #999;">~</span>
                        <input type="date" name="search_end_date" placeholder="종료 날짜"
                            value="<?php echo htmlspecialchars($search_end_date); ?>">
                        <input type="text" name="search_customer" placeholder="고객명"
                            value="<?php echo htmlspecialchars($search_customer); ?>">
                        <input type="text" name="search_phone" placeholder="전화번호"
                            value="<?php echo htmlspecialchars($search_phone); ?>">
                        <input type="hidden" name="range" value="<?php echo htmlspecialchars($range); ?>">
                        <button type="submit">검색</button>
                        <a href="orders.php?tab=completed"
                            style="padding: 10px 20px; background: #95a5a6; color: white; border-radius: 5px; text-decoration: none;">초기화</a>
                    </div>
                </form>

                <!-- 정보 텍스트 -->
                <div class="info-text">
                    총 <?php echo $total_count; ?>개의 판매 완료 (페이지:
                    <?php echo $page; ?>/<?php echo max(1, $total_pages); ?>)
                </div>

                <!-- 테이블 -->
                <table class="orders orders--completed">
                    <colgroup>
                        <col class="c-no">
                        <col class="c-date">
                        <col class="c-company">
                        <col class="c-phone">
                        <col class="c-partname">
                        <col class="c-qty">
                        <col class="c-parttotal">
                        <col class="c-grandtotal">
                        <col class="c-done">
                        <col class="c-cancel">
                        <col class="c-receipt">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>번호</th>
                            <th>완료일자</th>
                            <th>업체명</th>
                            <th>전화번호</th>
                            <th colspan="2">판매 품목</th>
                            <th colspan="2">가격</th>
                            <th colspan="3">관리</th>
                        </tr>
                        <tr style="background: #f5f5f5; font-weight: 500;">
                            <th colspan="4"></th>
                            <th>자재명</th>
                            <th>수량</th>
                            <th>자재별</th>
                            <th>총액</th>
                            <th>판매 완료</th>
                            <th>취소</th>
                            <th>영수증</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($sales_data)) {
                            // 판매 건마다 표시
                            $display_count = $total_count - $offset;
                            $sale_index = 0;
                            $bg_colors = array('#ffffff', '#f5f5f5', '#f0f7ff', '#f0fff0');
                            foreach ($sales_data as $sellid => $data) {
                                $row = $data['info'];
                                $items = $data['items'];
                                // 판매완료 탭: 완료일자(s20_sell_out_date) 기준, 접수일자(s20_sell_in_date) 부기
                                $completion_date = $row['s20_sell_out_date'] ? substr($row['s20_sell_out_date'], 0, 10) : '-';
                                $receipt_date = $row['s20_sell_in_date'] ? substr($row['s20_sell_in_date'], 0, 10) : '-';
                                $date_display = "<div style='line-height: 1.2;'>" . htmlspecialchars($completion_date) . "<br><span style='font-size: 10px; color: #999;'>(접수: " . htmlspecialchars($receipt_date) . ")</span></div>";

                                // 판매 건별 배경색 순환 적용 (4가지 색)
                                $bg_color = $bg_colors[$sale_index % 4];

                                // 자재 총액의 합 계산
                                $total_price = 0;
                                foreach ($items as $item) {
                                    $item_price = ($item['cost1'] ?? 0) * ($item['s21_quantity'] ?? 0);
                                    $total_price += $item_price;
                                }

                                // 첫 번째 자재 행
                                if (!empty($items)) {
                                    $first_item = $items[0];
                                    $item_total = ($first_item['cost1'] ?? 0) * ($first_item['s21_quantity'] ?? 0);

                                    // 아이템이 1개만 있으면 첫 번째 행에 경계선 추가
                                    $is_single_item = count($items) === 1;
                                    $first_border = $is_single_item ? 'border-bottom: 2px solid #bbb;' : '';

                                    echo "<tr style='$first_border' data-bg-index='" . ($sale_index % 4) . "' data-sellid='$sellid'>";
                                    echo "<td rowspan='" . count($items) . "'>" . $display_count . "</td>";
                                    echo "<td rowspan='" . count($items) . "'>" . $date_display . "</td>";
                                    echo "<td rowspan='" . count($items) . "'>" . htmlspecialchars($row['ex_company']) . "</td>";
                                    echo "<td rowspan='" . count($items) . "'>" . htmlspecialchars($row['ex_tel'] ?? '-') . "</td>";
                                    echo "<td style='font-weight: bold;'>" . htmlspecialchars($first_item['s1_name'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($first_item['s21_quantity'] ?? '-') . "</td>";
                                    echo "<td>" . number_format($item_total) . "</td>";
                                    echo "<td rowspan='" . count($items) . "' style='font-weight: bold;'>" . number_format($total_price) . "</td>";
                                    echo "<td rowspan='" . count($items) . "' style='background: #d4edda; color: #155724; font-weight: 600;'>✓ 완료</td>";
                                    echo "<td rowspan='" . count($items) . "'><button onclick='cancelSale(" . $row['s20_sellid'] . ")' class='action-btn cancel'>취소</button></td>";
                                    echo "<td rowspan='" . count($items) . "'><a href='receipt.php?id=" . $row['s20_sellid'] . "' target='_blank' class='action-btn view'>보기</a></td>";
                                    echo "</tr>";

                                    // 나머지 자재 행들
                                    for ($i = 1; $i < count($items); $i++) {
                                        $item = $items[$i];
                                        $item_total = ($item['cost1'] ?? 0) * ($item['s21_quantity'] ?? 0);
                                        $is_last_item = ($i === count($items) - 1);
                                        $border_style = $is_last_item ? 'border-bottom: 2px solid #bbb;' : '';

                                        echo "<tr class='item-row' style='$border_style' data-bg-index='" . ($sale_index % 4) . "' data-sellid='$sellid'>";
                                        echo "<td style='font-weight: bold;'>" . htmlspecialchars($item['s1_name'] ?? '-') . "</td>";
                                        echo "<td>" . htmlspecialchars($item['s21_quantity'] ?? '-') . "</td>";
                                        echo "<td>" . number_format($item_total) . "</td>";
                                        echo "</tr>";
                                    }

                                } else {
                                    // 자재 없는 경우
                                    echo "<tr style='border-bottom: 2px solid #bbb;' data-bg-index='" . ($sale_index % 4) . "' data-sellid='$sellid'>";
                                    echo "<td>" . $display_count . "</td>";
                                    echo "<td>" . $date_display . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ex_company']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ex_tel'] ?? '-') . "</td>";
                                    echo "<td colspan='3' style='color: #999;'>-</td>";
                                    echo "<td style='font-weight: bold;'>" . number_format($total_price) . "</td>";
                                    echo "<td style='background: #d4edda; color: #155724; font-weight: 600;'>✓ 완료</td>";
                                    echo "<td><button onclick='cancelSale(" . $row['s20_sellid'] . ")' class='action-btn cancel'>취소</button></td>";
                                    echo "<td><a href='receipt.php?id=" . $row['s20_sellid'] . "' target='_blank' class='action-btn view'>보기</a></td>";
                                    echo "</tr>";
                                }
                                $display_count--;
                                $sale_index++;
                            }
                        } else {
                            echo "<tr><td colspan='11' style='text-align: center; color: #999;'>판매 완료 데이터가 없습니다.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- 페이지네이션 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $search_params = '&tab=completed';
                        if ($search_start_date)
                            $search_params .= '&search_start_date=' . urlencode($search_start_date);
                        if ($search_end_date)
                            $search_params .= '&search_end_date=' . urlencode($search_end_date);
                        if ($search_customer)
                            $search_params .= '&search_customer=' . urlencode($search_customer);
                        if ($search_phone)
                            $search_params .= '&search_phone=' . urlencode($search_phone);

                        if ($page > 1) {
                            echo "<a href='orders.php?page=" . ($page - 1) . $search_params . "'>← 이전</a>";
                        }

                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) {
                            echo "<a href='orders.php?page=1" . $search_params . "'>1</a>";
                            if ($start_page > 2)
                                echo "<span>...</span>";
                        }

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo "<span class='current'>" . $i . "</span>";
                            } else {
                                echo "<a href='orders.php?page=" . $i . $search_params . "'>" . $i . "</a>";
                            }
                        }

                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1)
                                echo "<span>...</span>";
                            echo "<a href='orders.php?page=" . $total_pages . $search_params . "'>" . $total_pages . "</a>";
                        }

                        if ($page < $total_pages) {
                            echo "<a href='orders.php?page=" . ($page + 1) . $search_params . "'>다음 →</a>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleTodayDate(formId, buttonEl) {
            // 오늘 날짜 구하기
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const date = String(today.getDate()).padStart(2, '0');
            const todayString = `${year}-${month}-${date}`;

            // 해당 폼의 날짜 필드 찾아서 설정
            const form = document.getElementById(formId);
            if (!form) {
                console.error('Form not found: ' + formId);
                return false;
            }

            const startDateInput = form.querySelector('input[name="search_start_date"]');
            const endDateInput = form.querySelector('input[name="search_end_date"]');

            if (!startDateInput || !endDateInput) {
                console.error('Date inputs not found in form');
                return false;
            }

            const isOn = buttonEl.getAttribute('data-today') === 'on';

            if (isOn) {
                // off로 변경: 날짜 필드 초기화
                startDateInput.value = '';
                endDateInput.value = '';
                buttonEl.setAttribute('data-today', 'off');
                buttonEl.style.background = '#3498db';
            } else {
                // on으로 변경: 오늘 날짜 설정
                startDateInput.value = todayString;
                endDateInput.value = todayString;
                buttonEl.setAttribute('data-today', 'on');
                buttonEl.style.background = '#27ae60';
            }

            // 폼 자동 제출 (약간의 지연 후 제출)
            setTimeout(() => {
                form.submit();
            }, 100);

            return false;
        }

        function completeSale(sellId) {
            if (confirm('확인하시겠습니까?')) {
                window.location.href = 'order_payment.php?id=' + sellId + '&action=complete';
            }
        }

        function cancelSale(sellId) {
            if (confirm('판매 완료를 취소하시겠습니까?')) {
                window.location.href = 'order_payment.php?id=' + sellId + '&action=cancel';
            }
        }

        function switchTab(tabName) {
            // 모든 탭 콘텐츠 숨기기
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

            // 선택된 탭 표시
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');

            // URL 업데이트
            window.location.href = 'orders.php?tab=' + tabName;
        }

        // 페이지 로드 시 "오늘" 버튼 색상 초기화 및 그룹 호버 기능
        window.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.today-btn').forEach(btn => {
                if (btn.getAttribute('data-today') === 'on') {
                    btn.style.background = '#27ae60';
                } else {
                    btn.style.background = '#3498db';
                }
            });

            // 테이블 행 그룹 호버 기능 - 더 간단한 방식
            // 두 개의 테이블 모두에 적용
            const ordersTables = document.querySelectorAll('table.orders');
            ordersTables.forEach(table => {
                const tbody = table.querySelector('tbody');
                if (tbody) {
                    const rows = tbody.querySelectorAll('tr[data-sellid]');
                    rows.forEach(row => {
                        // mouseover 이벤트 사용 (더 안정적)
                        row.addEventListener('mouseover', function (e) {
                            const sellid = this.getAttribute('data-sellid');
                            if (sellid) {
                                // 같은 테이블 내에서 같은 sellid를 가진 모든 행에 클래스 추가
                                tbody.querySelectorAll(`tr[data-sellid="${sellid}"]`).forEach(tr => {
                                    tr.classList.add('group-hover');
                                });
                            }
                        });

                        row.addEventListener('mouseout', function (e) {
                            const sellid = this.getAttribute('data-sellid');
                            if (sellid) {
                                // 같은 테이블 내에서 같은 sellid를 가진 모든 행에서 클래스 제거
                                tbody.querySelectorAll(`tr[data-sellid="${sellid}"]`).forEach(tr => {
                                    tr.classList.remove('group-hover');
                                });
                            }
                        });
                    });
                }
            });
        });
    </script>
</body>

</html>
<?php mysql_close($connect); ?>