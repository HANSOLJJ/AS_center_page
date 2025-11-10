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
$current_page = 'parts';

// 현재 탭 설정
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'tab1';
$success_message = '';

// DELETE 요청 처리 (GET 기반)
if (isset($_GET['action']) && strpos($_GET['action'], 'delete_') === 0) {
    $action = $_GET['action'];

    // 외래 키 제약 비활성화
    mysql_query("SET FOREIGN_KEY_CHECKS = 0");

    $delete_success = false;
    if ($action === 'delete_part') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id > 0) {
            $delete_result = mysql_query("DELETE FROM step1_parts WHERE s1_uid = $id");
            $delete_success = ($delete_result !== false && mysql_affected_rows() > 0);
            $success_message = $delete_success ? 'AS 자재가 정상적으로 삭제되었습니다.' : '';
        }
    } elseif ($action === 'delete_category') {
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!empty($id)) {
            $id_esc = mysql_real_escape_string($id);
            $delete_result = mysql_query("DELETE FROM step5_category WHERE s5_caid = '$id_esc'");
            $delete_success = ($delete_result !== false && mysql_affected_rows() > 0);
            $success_message = $delete_success ? '카테고리가 정상적으로 삭제되었습니다.' : '';
        }
    }

    // 외래 키 제약 다시 활성화
    mysql_query("SET FOREIGN_KEY_CHECKS = 1");

    // 성공시 기능 탭으로 리다이렉트
    if ($delete_success) {
        header('Location: parts.php?tab=' . urlencode($current_tab) . '&deleted=1');
        exit;
    }
}

// 삭제 스링 메시지 확인
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $success_message = "✓ 정상적으로 삭제되었습니다.";
}

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 검색 조건
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : '';
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// 탭별 데이터 처리
$tab_info = array(
    'tab1' => array('name' => 'AS 자재 관리', 'table' => 'step1_parts', 'button_text' => '새 자재 등록', 'add_link' => 'parts_add.php'),
    'tab2' => array('name' => '자재 카테고리 관리', 'table' => 'step5_category', 'button_text' => '새 카테고리 등록', 'add_link' => 'category_add.php'),
    'tab3' => array('name' => '탭3', 'table' => '', 'button_text' => '새 항목 등록', 'add_link' => '#'),
    'tab4' => array('name' => '탭4', 'table' => '', 'button_text' => '새 항목 등록', 'add_link' => '#'),
    'tab5' => array('name' => '탭5', 'table' => '', 'button_text' => '새 항목 등록', 'add_link' => '#'),
);

// 현재 탭 정보
$tab_config = $tab_info[$current_tab];
$table = $tab_config['table'];
$show_table = !empty($table);

// 카테고리 목록 조회 (Tab1일 때만)
$categories = array();
if ($current_tab === 'tab1') {
    $cat_result = mysql_query("SELECT s5_caid, s5_category FROM step5_category ORDER BY s5_caid ASC");
    if ($cat_result) {
        while ($cat_row = mysql_fetch_assoc($cat_result)) {
            $categories[] = $cat_row;
        }
    }
}

// 탭별 데이터 처리
$where = "1=1";

// 검색 조건 추가
if ($show_table) {
    if ($current_tab === 'tab1') {
        // 카테고리 필터
        if ($selected_category !== 'all' && $selected_category !== '') {
            $where .= " AND p.s1_caid = '" . mysql_real_escape_string($selected_category) . "'";
        }

        // 자재명 검색
        if (!empty($search_keyword)) {
            $search_keyword_esc = mysql_real_escape_string($search_keyword);
            $where .= " AND p.s1_name LIKE '%" . $search_keyword_esc . "%'";
        }
    } elseif ($current_tab === 'tab2') {
        // 카테고리명 검색
        if (!empty($search_keyword)) {
            $where .= " AND s5_category LIKE '%" . mysql_real_escape_string($search_keyword) . "%'";
        }
    }
}

// 데이터 초기화
$total_count = 0;
$total_pages = 0;
$result = null;

// 테이블이 설정된 경우만 쿼리 실행
if ($show_table && !empty($table)) {
    if ($current_tab === 'tab1') {
        // 자재 데이터 처리
        $count_result = mysql_query("
            SELECT COUNT(*) as cnt FROM step1_parts p
            LEFT JOIN step5_category c ON p.s1_caid = c.s5_caid
            WHERE $where
        ");
        $count_row = mysql_fetch_assoc($count_result);
        $total_count = $count_row['cnt'];
        $total_pages = ceil($total_count / $limit);

        $result = mysql_query("
            SELECT p.s1_uid, p.s1_name, p.s1_caid, c.s5_category, p.s1_cost_c_1, 
                   p.s1_cost_a_1, p.s1_cost_a_2, p.s1_cost_n_1, p.s1_cost_n_2, p.s1_cost_s_1
            FROM step1_parts p
            LEFT JOIN step5_category c ON p.s1_caid = c.s5_caid
            WHERE $where
            ORDER BY p.s1_uid DESC
            LIMIT $offset, $limit
        ");
    } elseif ($current_tab === 'tab2') {
        // 카테고리 데이터 처리
        $count_result = mysql_query("
            SELECT COUNT(*) as cnt FROM step5_category
            WHERE $where
        ");
        $count_row = mysql_fetch_assoc($count_result);
        $total_count = $count_row['cnt'];
        $total_pages = ceil($total_count / $limit);

        $result = mysql_query("
            SELECT s5_caid, s5_category
            FROM step5_category
            WHERE $where
            ORDER BY s5_caid DESC
            LIMIT $offset, $limit
        ");
    }
}

// 카테고리 조회 (AJAX 요청)
if (isset($_GET['action']) && $_GET['action'] === 'get_categories') {
    header('Content-Type: application/json; charset=utf-8');

    $categories = array();
    $cat_result = mysql_query("SELECT s5_caid, s5_category FROM step5_category ORDER BY s5_caid ASC");
    if ($cat_result) {
        while ($cat_row = mysql_fetch_assoc($cat_result)) {
            $categories[] = $cat_row;
        }
    }

    echo json_encode(['categories' => $categories]);
    exit;
}

// 자동완성 데이터 조회 (AJAX 요청일 경우)
if (isset($_GET['action']) && $_GET['action'] === 'autocomplete') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'name';

    header('Content-Type: application/json; charset=utf-8');

    $suggestions = array();
    if (!empty($q)) {
        if ($type === 'name') {
            $query = "SELECT DISTINCT s1_name FROM step1_parts WHERE s1_name LIKE '%" . mysql_real_escape_string($q) . "%' LIMIT 10";
        } elseif ($type === 'category') {
            $query = "SELECT DISTINCT s5_category FROM step5_category WHERE s5_category LIKE '%" . mysql_real_escape_string($q) . "%' LIMIT 10";
        }

        if (isset($query)) {
            $res = mysql_query($query);
            while ($row = mysql_fetch_assoc($res)) {
                $field = $type === 'name' ? 's1_name' : 's5_category';
                $suggestions[] = array(
                    'value' => $row[$field],
                    'label' => $row[$field]
                );
            }
        }
    }

    echo json_encode($suggestions);
    exit;
}


?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>자재 관리 - AS 시스템</title>
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
            font-size: 14px;
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
            max-width: 1400px;
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

        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
        }

        .tab-button {
            padding: 12px 24px;
            background: #f0f0f0;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
            border-radius: 5px 5px 0 0;
        }

        .tab-button:hover {
            background: #e0e0e0;
        }

        .tab-button.active {
            background: #667eea;
            color: white;
            border-bottom: 3px solid #667eea;
        }

        .message {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            background: #efe;
            border: 1px solid #9f9;
            color: #3c3;
        }

        .message.error {
            background: #fee;
            border-color: #f99;
            color: #c33;
        }

        .search-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .search-box select,
        .search-box input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box input[type="text"] {
            flex: 0 0 auto;
            /* flex 확장 비활성화 */
            width: 220px;
            /* 원하는 폭으로 고정 */
            min-width: unset;
            /* min-width 속성 제거 */
        }

        .search-box button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .search-box button:hover {
            background: #764ba2;
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
        }

        .register-btn:hover {
            background: #229954;
        }

        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            word-wrap: break-word;
            font-size: 14px;
        }

        th:last-child,
        td:last-child {
            border-right: none;
        }

        th {
            background: #667eea;
            color: white;
            font-weight: 700;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .header-sub {
            background: #667eea;
            color: white;
            font-weight: 600;
            font-size: 12px;
            border-bottom: 1px solid #ddd;
        }

        .action-link {
            padding: 5px 10px;
            margin: 0 3px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
            font-size: 12px;
            cursor: pointer;
        }

        .edit-link {
            background: #3498db;
            color: white;
        }

        .edit-link:hover {
            background: #2980b9;
        }

        .delete-link {
            background: #e74c3c;
            color: white;
        }

        .delete-link:hover {
            background: #c0392b;
        }

        /* Column 너비 정의 - Tab1 (AS 자재 관리) */

        table.parts-table col.c-name {
            width: auto;
        }

        table.parts-table col.c-category {
            width: 10%;
        }

        table.parts-table col.c-cost-c {
            width: 7%;
        }

        table.parts-table col.c-cost-a1 {
            width: 7%;
        }

        table.parts-table col.c-cost-a2 {
            width: 7%;
        }

        table.parts-table col.c-cost-n1 {
            width: 7%;
        }

        table.parts-table col.c-cost-n2 {
            width: 7%;
        }

        table.parts-table col.c-cost-s {
            width: 6%;
        }

        table.parts-table col.c-edit {
            width: 6%;
        }

        table.parts-table col.c-del {
            width: 6%;
        }

        /* Column 너비 정의 - Tab2 (자재 카테고리 관리) */
        table.parts-table col.c-no {
            width: 5%;
        }

        table.parts-table col.c-id {
            width: 5%;
        }

        table.parts-table col.c-name {
            width: auto;
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

        .blockUI {
            background: rgba(0, 0, 0, 0.6);
        }

        .blockMsg {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .blockMsg h2 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .price-cell {
            text-align: center;
            font-size: 16px;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: #999;
        }

        .empty-state p {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .empty-state strong {
            display: block;
            margin-top: 15px;
            color: #667eea;
            font-size: 14px;
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
        <a href="orders.php" class="nav-item">자재 판매</a>
        <a href="parts.php" class="nav-item <?php echo $current_page === 'parts' ? 'active' : ''; ?>">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
        <a href="as_statistics.php" class="nav-item">통계/분석</a>
    </div>

    <div class="container">
        <div class="content">
            <h2>📦 자재 관리</h2>

            <div class="tabs">
                <button class="tab-button <?php echo $current_tab === 'tab1' ? 'active' : ''; ?>"
                    onclick="location.href='parts.php?tab=tab1'">AS 자재 관리</button>
                <button class="tab-button <?php echo $current_tab === 'tab2' ? 'active' : ''; ?>"
                    onclick="location.href='parts.php?tab=tab2'">자재 카테고리 관리</button>
                <button class="tab-button <?php echo $current_tab === 'tab3' ? 'active' : ''; ?>"
                    onclick="location.href='parts.php?tab=tab3'">탭3</button>
                <button class="tab-button <?php echo $current_tab === 'tab4' ? 'active' : ''; ?>"
                    onclick="location.href='parts.php?tab=tab4'">탭4</button>
                <button class="tab-button <?php echo $current_tab === 'tab5' ? 'active' : ''; ?>"
                    onclick="location.href='parts.php?tab=tab5'">탭5</button>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="message">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($current_tab === 'tab1'): ?>
                <!-- 카테고리 필터 버튼 (Tab1) -->
                <div class="search-box" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    <span style="font-weight: 500; color: #666; margin-right: 10px;">카테고리:</span>
                    <a href="parts.php?tab=tab1"
                        class="category-btn <?php echo $selected_category === 'all' ? 'active' : ''; ?>"
                        style="padding: 8px 16px; background: <?php echo $selected_category === 'all' ? '#667eea' : '#e0e0e0'; ?>; color: <?php echo $selected_category === 'all' ? 'white' : '#333'; ?>; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 13px; transition: all 0.3s; display: inline-block;">모든
                        카테고리</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="parts.php?tab=tab1&category=<?php echo urlencode($cat['s5_caid']); ?>"
                            class="category-btn <?php echo $selected_category === $cat['s5_caid'] ? 'active' : ''; ?>"
                            style="padding: 8px 16px; background: <?php echo $selected_category === $cat['s5_caid'] ? '#667eea' : '#f0f0f0'; ?>; color: <?php echo $selected_category === $cat['s5_caid'] ? 'white' : '#333'; ?>; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 13px; transition: all 0.3s; display: inline-block;"><?php echo htmlspecialchars($cat['s5_category']); ?></a>
                    <?php endforeach; ?>
                </div>

                <!-- 자재명 검색 (Tab1) -->
                <form method="GET" class="search-box" id="search-form" style="margin-top: 15px;">
                    <input type="hidden" name="tab" value="tab1">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
                    <input type="text" id="search_keyword" name="search_keyword" placeholder="자재명으로 검색..."
                        value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="submit">검색</button>
                    <a href="parts.php?tab=tab1&category=<?php echo urlencode($selected_category); ?>"
                        style="padding: 10px 20px; background: #95a5a6; color: white; border-radius: 5px; text-decoration: none;">초기화</a>
                </form>

            <?php else: ?>
                <!-- 기본 검색 박스 (Tab2, 3, 4, 5) -->
                <form method="GET" class="search-box" id="search-form">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
                    <input type="text" id="search_keyword" name="search_keyword" placeholder="검색어를 입력하세요"
                        value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="submit">검색</button>
                    <a href="parts.php?tab=<?php echo htmlspecialchars($current_tab); ?>"
                        style="padding: 10px 20px; background: #95a5a6; color: white; border-radius: 5px; text-decoration: none;">초기화</a>
                </form>
            <?php endif; ?>

            <div class="action-buttons">
                <button class="register-btn" onclick="location.href='<?php echo $tab_config['add_link']; ?>'" <?php echo ($tab_config['add_link'] === '#') ? 'disabled' : ''; ?>>+
                    <?php echo $tab_config['button_text']; ?></button>
            </div>

            <div class="info-text">
                총 <?php echo $total_count; ?>개의 항목 (페이지: <?php echo $page; ?>/<?php echo max(1, $total_pages); ?>)
            </div>

            <table id="data-table" class="parts-table">
                <colgroup>
                    <?php if ($current_tab === 'tab1'): ?>
                        <col class="c-id">
                        <col class="c-name">
                        <col class="c-category">
                        <col class="c-cost-c">
                        <col class="c-cost-a1">
                        <col class="c-cost-a2">
                        <col class="c-cost-n1">
                        <col class="c-cost-n2">
                        <col class="c-cost-s">
                        <col class="c-edit">
                        <col class="c-del">
                    <?php elseif ($current_tab === 'tab2'): ?>
                        <col class="c-no">
                        <col class="c-id">
                        <col class="c-name">
                        <col class="c-edit">
                        <col class="c-del">
                    <?php else: ?>
                        <col class="c-item1">
                        <col class="c-item2">
                        <col class="c-item3">
                        <col class="c-item4">
                        <col class="c-edit">
                        <col class="c-del">
                    <?php endif; ?>
                </colgroup>
                <thead>
                    <tr>
                        <?php if ($current_tab === 'tab1'): ?>
                            <th>번호</th>
                            <th>자재명</th>
                            <th>카테고리</th>
                            <th>AS center 공급가</th>
                            <th colspan="2">대리점 공급가</th>
                            <th colspan="2">일반판매 공급가</th>
                            <th>특별공급가</th>
                            <th colspan="2">관리</th>
                        <?php elseif ($current_tab === 'tab2'): ?>
                            <th>번호</th>
                            <th>ID</th>
                            <th>카테고리명</th>
                            <th colspan="2">관리</th>
                        <?php else: ?>
                            <th>항목1</th>
                            <th>항목2</th>
                            <th>항목3</th>
                            <th>항목4</th>
                            <th colspan="2">관리</th>
                        <?php endif; ?>
                    </tr>
                    <?php if ($current_tab === 'tab1'): ?>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th class="header-sub">개별판매</th>
                            <th class="header-sub">수리시</th>
                            <th class="header-sub">개별판매</th>
                            <th class="header-sub">수리시</th>
                            <th></th>
                            <th>수정</th>
                            <th>삭제</th>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php
                    if ($show_table && $result && mysql_num_rows($result) > 0) {
                        if ($current_tab === 'tab1') {
                            while ($row = mysql_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['s1_uid']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['s1_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['s5_category'] ?: '미분류') . "</td>";
                                echo "<td class='price-cell'>" . number_format($row['s1_cost_c_1']) . "</td>";
                                echo "<td class='price-cell'>" . number_format($row['s1_cost_a_1']) . "</td>";
                                echo "<td class='price-cell'>" . number_format($row['s1_cost_a_2']) . "</td>";
                                echo "<td class='price-cell'>" . number_format($row['s1_cost_n_1']) . "</td>";
                                echo "<td class='price-cell'>" . number_format($row['s1_cost_n_2']) . "</td>";
                                echo "<td class='price-cell'>" . number_format($row['s1_cost_s_1']) . "</td>";
                                echo "<td>";
                                echo "<a href='parts_edit.php?id=" . $row['s1_uid'] . "' class='action-link edit-link'>수정</a>";
                                echo "</td>";
                                echo "<td>";
                                echo "<a href='parts.php?action=delete_part&id=" . $row['s1_uid'] . "&tab=" . urlencode($current_tab) . "' class='action-link delete-link' onclick=\"return confirm('삭제하시겠습니까?');\">삭제</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } elseif ($current_tab === 'tab2') {
                            $row_num = $total_count - $offset;
                            while ($row = mysql_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row_num-- . "</td>";
                                echo "<td>" . htmlspecialchars($row['s5_caid']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['s5_category']) . "</td>";
                                echo "<td>";
                                echo "<a href='category_edit.php?id=" . urlencode($row['s5_caid']) . "' class='action-link edit-link'>수정</a>";
                                echo "</td>";
                                echo "<td>";
                                echo "<a href='parts.php?action=delete_category&id=" . urlencode($row['s5_caid']) . "&tab=" . urlencode($current_tab) . "' class='action-link delete-link' onclick=\"return confirm('삭제하시겠습니까?');\">삭제</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                    } elseif ($show_table) {
                        $colspan = ($current_tab === 'tab1') ? 11 : ($current_tab === 'tab2' ? 5 : 6);
                        echo "<tr><td colspan='$colspan' style='border-right: none;'>데이터가 없습니다.</td></tr>";
                    } else {
                        echo "<tr>";
                        echo "<td style='width: 20%; border-right: 1px solid #ddd;'>-</td>";
                        echo "<td style='width: 20%; border-right: 1px solid #ddd;'>-</td>";
                        echo "<td style='width: 20%; border-right: 1px solid #ddd;'>-</td>";
                        echo "<td style='width: 20%; border-right: 1px solid #ddd;'>-</td>";
                        echo "<td style='width: 20%;'>-</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

            <?php if ($show_table && $total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $search_params = '&tab=' . urlencode($current_tab);
                    if ($current_tab === 'tab1' && $selected_category !== 'all') {
                        $search_params .= '&category=' . urlencode($selected_category);
                    }
                    if ($search_keyword) {
                        $search_params .= '&search_keyword=' . urlencode($search_keyword);
                    }

                    if ($page > 1) {
                        echo "<a href='parts.php?page=" . ($page - 1) . $search_params . "'>← 이전</a>";
                    }

                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo "<a href='parts.php?page=1" . $search_params . "'>1</a>";
                        if ($start_page > 2)
                            echo "<span>...</span>";
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo "<span class='current'>" . $i . "</span>";
                        } else {
                            echo "<a href='parts.php?page=" . $i . $search_params . "'>" . $i . "</a>";
                        }
                    }

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1)
                            echo "<span>...</span>";
                        echo "<a href='parts.php?page=" . $total_pages . $search_params . "'>" . $total_pages . "</a>";
                    }

                    if ($page < $total_pages) {
                        echo "<a href='parts.php?page=" . ($page + 1) . $search_params . "'>다음 →</a>";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!$show_table): ?>
                <div class="empty-state">
                    <p>📋 이 탭의 데이터베이스 테이블이 아직 설정되지 않았습니다.</p>
                    <strong>곧 자료가 추가될 예정입니다.</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // 로딩 표시
        function showLoading() {
            var $loading = $('<div class="loading show">' +
                '<div class="loading-content">' +
                '<div class="spinner"></div>' +
                '<h3>처리 중...</h3>' +
                '<p>삭제 중입니다.</p>' +
                '</div>' +
                '</div>');
            $('body').append($loading);
            return $loading;
        }

        // 로딩 숨기기
        function hideLoading($loading) {
            if ($loading) {
                $loading.remove();
            }
        }



        $(function () {
            var currentTab = '<?php echo $current_tab; ?>';

            if (currentTab === 'tab1') {
                // 자재명 검색 자동완성
                $('#search_keyword').autocomplete('parts.php?action=autocomplete&type=name', {
                    minChars: 1,
                    width: 300,
                    matchContains: true,
                    autoFill: false,
                    selectFirst: false
                });
            }
        });

    </script>
</body>

</html>
<?php mysql_close($connect); ?>