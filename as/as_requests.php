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

// 탭 선택
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'request';
$current_tab = in_array($tab, ['request', 'working', 'completed']) ? $tab : 'request';

// 페이지 처리
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 검색 조건 (GET으로 받아서 검색 유지)
$search_start_date = isset($_GET['search_start_date']) ? $_GET['search_start_date'] : (isset($_POST['search_start_date']) ? $_POST['search_start_date'] : '');
$search_end_date = isset($_GET['search_end_date']) ? $_GET['search_end_date'] : (isset($_POST['search_end_date']) ? $_POST['search_end_date'] : '');
$search_customer = isset($_GET['search_customer']) ? trim($_GET['search_customer']) : (isset($_POST['search_customer']) ? trim($_POST['search_customer']) : '');
$search_phone = isset($_GET['search_phone']) ? trim($_GET['search_phone']) : (isset($_POST['search_phone']) ? trim($_POST['search_phone']) : '');

// 삭제 액션 처리
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $tab_param = isset($_GET['tab']) ? $_GET['tab'] : 'request';
    $delete_type = '';

    if (isset($_GET['itemid']) && intval($_GET['itemid']) > 0) {
        // s14_aiid 기준 삭제 (해당 아이템만)
        $delete_itemid = intval($_GET['itemid']);

        // step18_as_cure_cart에서 먼저 삭제 (외래키 제약 고려)
        $delete_cure_query = "DELETE FROM step18_as_cure_cart WHERE s18_aiid = $delete_itemid";
        @mysql_query($delete_cure_query);

        // step14_as_item 삭제
        $delete_query = "DELETE FROM step14_as_item WHERE s14_aiid = $delete_itemid";
        @mysql_query($delete_query);
        $delete_type = 'item';
    } elseif (isset($_GET['asid']) && intval($_GET['asid']) > 0) {
        // s13_asid 기준 삭제 (AS 요청 전체)
        $delete_asid = intval($_GET['asid']);

        // step18_as_cure_cart에서 먼저 삭제 (s18_asid 기준)
        $delete_cure_query = "DELETE FROM step18_as_cure_cart WHERE s18_asid = $delete_asid";
        @mysql_query($delete_cure_query);

        // step14_as_item 삭제
        $delete_items_query = "DELETE FROM step14_as_item WHERE s14_asid = $delete_asid";
        @mysql_query($delete_items_query);

        // step13_as 삭제
        $delete_as_query = "DELETE FROM step13_as WHERE s13_asid = $delete_asid";
        @mysql_query($delete_as_query);
        $delete_type = 'as';
    }

    // 해당 탭으로 리다이렉트
    $redirect_url = "as_requests.php?tab=$tab_param&deleted=1";
    if ($delete_type) {
        $redirect_url .= "&delete_type=$delete_type";
    }
    header("Location: $redirect_url");
    exit;
}

// 삭제 메시지
$deleted = isset($_GET['deleted']) ? true : false;
$delete_type = isset($_GET['delete_type']) ? $_GET['delete_type'] : '';

// 탭별 WHERE 조건
$where_conditions = array();

switch ($current_tab) {
    case 'request':
        $where_conditions[] = "a.s13_as_level NOT IN ('2', '3', '4', '5')";
        $tab_title = 'AS 요청';
        break;
    case 'working':
        $where_conditions[] = "a.s13_as_level IN ('2', '3', '4')";
        $tab_title = 'AS 진행';
        break;
    case 'completed':
        $where_conditions[] = "a.s13_as_level = '5'";
        $tab_title = 'AS 완료';
        break;
}

// 기간 검색 (탭별로 다른 날짜 필드 사용)
// 요청/작업: s13_as_in_date (접수일자), 완료: s13_as_out_date (출고일)
$date_field = ($current_tab == 'completed') ? 's13_as_out_date' : 's13_as_in_date';

if (!empty($search_start_date)) {
    $where_conditions[] = "DATE($date_field) >= '" . mysql_real_escape_string($search_start_date) . "'";
}
if (!empty($search_end_date)) {
    $where_conditions[] = "DATE($date_field) <= '" . mysql_real_escape_string($search_end_date) . "'";
}

// 고객명 검색
if (!empty($search_customer)) {
    $where_conditions[] = "a.ex_company LIKE '%" . mysql_real_escape_string($search_customer) . "%'";
}

// 전화번호 검색 (step11_member의 전화번호 필드 사용)
if (!empty($search_phone)) {
    $phone_esc = mysql_real_escape_string($search_phone);
    $where_conditions[] = "(CONCAT(m.s11_phone1, m.s11_phone2, m.s11_phone3) LIKE '%" . $phone_esc . "%' OR CONCAT(m.s11_phone1, '-', m.s11_phone2, '-', m.s11_phone3) LIKE '%" . $phone_esc . "%')";
}

// DB 쿼리 실행
{
    $where = implode(' AND ', $where_conditions);

    // 총 개수 조회
    $count_query = "SELECT COUNT(DISTINCT a.s13_asid) as total FROM step13_as a
                    LEFT JOIN step11_member m ON a.s13_meid = m.s11_meid
                    WHERE $where";
    $count_result = @mysql_query($count_query);
    $count_row = mysql_fetch_assoc($count_result);
    $total_count = $count_row['total'] ?? 0;
    $total_pages = ceil($total_count / $per_page);

    // 먼저 페이징을 위해 DISTINCT asid 조회
    // 탭별로 다른 정렬 기준 사용: 완료탭은 AS 완료일 기준, 나머지는 AS ID 역순
    if ($current_tab === 'completed') {
        $asid_query = "SELECT a.s13_asid
                       FROM step13_as a
                       LEFT JOIN step11_member m ON a.s13_meid = m.s11_meid
                       WHERE $where
                       ORDER BY a.s13_as_out_date DESC, a.s13_asid DESC
                       LIMIT $per_page OFFSET $offset";
    } else {
        $asid_query = "SELECT a.s13_asid
                       FROM step13_as a
                       LEFT JOIN step11_member m ON a.s13_meid = m.s11_meid
                       WHERE $where
                       ORDER BY a.s13_asid DESC
                       LIMIT $per_page OFFSET $offset";
    }

    $asid_result = @mysql_query($asid_query);
    $target_asids = array();

    if ($asid_result && mysql_num_rows($asid_result) > 0) {
        while ($row = mysql_fetch_assoc($asid_result)) {
            $target_asids[] = $row['s13_asid'];
        }
    }

    $as_list = array();

    if (!empty($target_asids)) {
        $asid_list = implode(',', array_map('intval', $target_asids));

        // 실제 데이터 조회
        // working, completed 탭에서는 step18_as_cure_cart와 step19_as_result 조인 추가
        if ($current_tab === 'working' || $current_tab === 'completed') {
            $order_by = ($current_tab === 'completed') ? "a.s13_as_out_date DESC" : "a.s13_asid DESC";
            $query = "SELECT a.*,
                             m.s11_phone1, m.s11_phone2, m.s11_phone3,
                             b.s14_aiid, b.s14_model, b.s14_poor, b.s14_asrid, b.s14_cart,
                             md.s15_model_name, pd.s16_poor,
                             res.s19_result,
                             c.s18_accid, c.s18_uid, c.cost_name, c.s18_quantity, c.cost1
                      FROM step13_as a
                      LEFT JOIN step11_member m ON a.s13_meid = m.s11_meid
                      LEFT JOIN step14_as_item b ON a.s13_asid = b.s14_asid
                      LEFT JOIN step15_as_model md ON b.s14_model = md.s15_amid
                      LEFT JOIN step16_as_poor pd ON b.s14_poor = pd.s16_apid
                      LEFT JOIN step19_as_result res ON b.s14_asrid = res.s19_asrid
                      LEFT JOIN step18_as_cure_cart c ON b.s14_aiid = c.s18_aiid
                      WHERE a.s13_asid IN ($asid_list)
                      ORDER BY $order_by, b.s14_aiid ASC, c.s18_accid ASC";
        } else {
            $query = "SELECT a.*,
                             m.s11_phone1, m.s11_phone2, m.s11_phone3,
                             b.s14_aiid, b.s14_model, b.s14_poor, b.s14_asrid, b.as_end_result,
                             md.s15_model_name, pd.s16_poor,
                             c.s18_accid, c.s18_uid, c.cost_name, c.s18_quantity, c.cost1
                      FROM step13_as a
                      LEFT JOIN step11_member m ON a.s13_meid = m.s11_meid
                      LEFT JOIN step14_as_item b ON a.s13_asid = b.s14_asid
                      LEFT JOIN step15_as_model md ON b.s14_model = md.s15_amid
                      LEFT JOIN step16_as_poor pd ON b.s14_poor = pd.s16_apid
                      LEFT JOIN step18_as_cure_cart c ON b.s14_aiid = c.s18_aiid
                      WHERE a.s13_asid IN ($asid_list)
                      ORDER BY a.s13_asid DESC, b.s14_aiid ASC, c.s18_accid ASC";
        }

        $result = mysql_query($query);
        $grouped_list = array(); // asid별로 그룹화

        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $asid = $row['s13_asid'];
                if (!isset($grouped_list[$asid])) {
                    $grouped_list[$asid] = array(
                        'as_info' => $row,
                        'items' => array()
                    );
                }
                if ($row['s14_aiid']) {
                    $aiid = $row['s14_aiid'];

                    // 모든 탭에서 cure_parts를 그룹화
                    // 해당 aiid가 이미 존재하는지 확인
                    $item_exists = false;
                    foreach ($grouped_list[$asid]['items'] as &$item) {
                        if ($item['s14_aiid'] === $aiid) {
                            // 이미 존재하면 cure_parts에만 추가
                            if ($row['s18_accid']) {
                                $item['cure_parts'][] = array(
                                    's18_accid' => $row['s18_accid'],
                                    's18_uid' => $row['s18_uid'],
                                    'cost_name' => $row['cost_name'],
                                    's18_quantity' => $row['s18_quantity'],
                                    'cost1' => $row['cost1']
                                );
                            }
                            $item_exists = true;
                            break;
                        }
                    }

                    // 새로운 item인 경우
                    if (!$item_exists) {
                        $new_item = $row;
                        $new_item['cure_parts'] = array();
                        if ($row['s18_accid']) {
                            $new_item['cure_parts'][] = array(
                                's18_accid' => $row['s18_accid'],
                                's18_uid' => $row['s18_uid'],
                                'cost_name' => $row['cost_name'],
                                's18_quantity' => $row['s18_quantity'],
                                'cost1' => $row['cost1']
                            );
                        }
                        $grouped_list[$asid]['items'][] = $new_item;
                    }
                }
            }
        }

        // 순서 유지하면서 as_list 구성
        foreach ($target_asids as $asid) {
            if (isset($grouped_list[$asid])) {
                $as_list[$asid] = $grouped_list[$asid];
            }
        }
    }
}

// 상태 레이블 함수
function getStatusLabel($level)
{
    $labels = array(
        '1' => '요청',
        '2' => '작업중',
        '3' => '완료',
        '4' => '출고완료'
    );
    return $labels[$level] ?? '불명';
}

// 상태별 색상
function getStatusColor($level)
{
    $colors = array(
        '1' => '#3498db',  // 파란색
        '2' => '#f39c12',  // 주황색
        '3' => '#9b59b6',  // 보라색
        '4' => '#27ae60'   // 초록색
    );
    return $colors[$level] ?? '#95a5a6';
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AS 작업 관리 - AS 시스템</title>
    <style>
        /* ===== Layout & Typography ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        /* ===== Header / Nav ===== */
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

        /* ===== Page Sections ===== */
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

        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

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

        .search-box select {
            min-width: 100px;
        }

        .search-box input {
            min-width: 150px;
        }

        .search-box button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 500;
        }

        .search-box a.btn-reset {
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }

        .info-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
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

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        /* ===== Buttons ===== */
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
            transition: all 0.2s;
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

        .action-btn.view {
            background: #27ae60;
        }

        .action-btn.view:hover {
            background: #229954;
            transform: scale(1.05);
        }

        /* ===== Table (orders.php 스타일) ===== */
        table.as-table {
            margin-top: 20px;
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            border: 1px solid #ddd;
        }

        table.as-table thead tr:nth-child(1) th {
            background: #667eea;
            color: white;
            font-weight: 700;
            padding: 12px 8px;
        }

        table.as-table thead tr:nth-child(2) th {
            background: #667eea;
            font-weight: 600;
            font-size: 12px;
            color: white;
            padding: 8px;
        }

        table.as-table th,
        table.as-table td {
            padding: 10px 8px;
            text-align: center;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            word-break: break-word;
            vertical-align: middle;
        }

        table.as-table th:last-child,
        table.as-table td:last-child {
            border-right: 1px solid #ddd !important;
        }

        /* 배경색 순환 (orders.php처럼 4가지) */
        table.as-table tbody tr[data-bg="0"] {
            background: #ffffff;
        }

        table.as-table tbody tr[data-bg="1"] {
            background: #f5f5f5;
        }

        table.as-table tbody tr[data-bg="2"] {
            background: #f0f7ff;
        }

        table.as-table tbody tr[data-bg="3"] {
            background: #f0fff0;
        }

        /* Hover 효과 - 각 배경색별 (group-hover 클래스 사용) */
        table.as-table tbody tr {
            transition: background 0.3s ease;
        }

        table.as-table tbody tr.group-hover[data-bg="0"] {
            background: #efefef !important;
        }

        table.as-table tbody tr.group-hover[data-bg="1"] {
            background: #e8e8e8 !important;
        }

        table.as-table tbody tr.group-hover[data-bg="2"] {
            background: #d9e9ff !important;
        }

        table.as-table tbody tr.group-hover[data-bg="3"] {
            background: #d9ffd9 !important;
        }

        /* 모델명 + 증상 행: 왼쪽 정렬 */
        table.as-table tbody tr.item-row-first td {
            text-align: left;
            padding: 8px 10px;
        }

        /* 수리 버튼 */
        .action-btn[style*="background: #f39c12"] {
            background: #f39c12 !important;
        }

        .action-btn[style*="background: #f39c12"]:hover {
            background: #d68910 !important;
        }

        /* Column 너비 정의 (orders.php 패턴) */
        table.as-table col.c-no {
            width: 5%;
        }

        /* 번호 */
        table.as-table col.c-date {
            width: 8%;
        }

        /* 접수일자 */
        table.as-table col.c-company {
            width: 14%;
        }

        /* 업체명 */
        table.as-table col.c-phone {
            width: 8%;
        }

        /* 연락처 */
        table.as-table col.c-ship-method {
            width: 4%;
        }

        /* 수탁 */

        table.as-table col.c-model {
            width: 6%;
        }

        /* 모델 */

        table.as-table col.c-as-task {
            width: 5%;
        }

        /*AS 내역*/

        table.as-table col.c-as-parts {
            width: auto;
        }

        /*수리 내역*/
        table.as-table col.c-as-totalcost {
            width: 8%;
        }

        /*비용*/


        /*AS작업*/

        /*관리 */
        table.as-table col.c-admin {
            width: 5%;
        }



        /* 삭제 */
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
        <a href="as_statistics.php" class="nav-item">통계/분석</a>
    </div>

    <div class="container">
        <div class="content">
            <h2>🔧AS 작업</h2>

            <?php if ($deleted): ?>
                <div class="message success show">
                    <?php
                    if ($delete_type === 'item') {
                        echo 'AS 요청 제품이 삭제되었습니다.';
                    } else {
                        echo 'AS 요청이 삭제되었습니다.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- 탭 -->
            <div class="tabs">
                <button class="tab-btn <?php echo $current_tab === 'request' ? 'active' : ''; ?>"
                    onclick="location.href='as_requests.php?tab=request'">
                    AS 요청 (<?php
                    $req_count = @mysql_query("SELECT COUNT(*) as cnt FROM step13_as WHERE s13_as_level NOT IN ('2','3','4','5')");
                    $req_row = mysql_fetch_assoc($req_count);
                    echo $req_row['cnt'] ?? 0;
                    ?>)
                </button>
                <button class="tab-btn <?php echo $current_tab === 'working' ? 'active' : ''; ?>"
                    onclick="location.href='as_requests.php?tab=working'">
                    AS 진행 (<?php
                    $work_count = @mysql_query("SELECT COUNT(*) as cnt FROM step13_as WHERE s13_as_level IN ('2','3','4')");
                    $work_row = mysql_fetch_assoc($work_count);
                    echo $work_row['cnt'] ?? 0;
                    ?>)
                </button>
                <button class="tab-btn <?php echo $current_tab === 'completed' ? 'active' : ''; ?>"
                    onclick="location.href='as_requests.php?tab=completed'">
                    AS 완료 (<?php
                    $comp_count = @mysql_query("SELECT COUNT(*) as cnt FROM step13_as WHERE s13_as_level='5'");
                    $comp_row = mysql_fetch_assoc($comp_count);
                    echo $comp_row['cnt'] ?? 0;
                    ?>)
                </button>
            </div>

            <!-- 액션 버튼 (요청 탭에서만 표시) -->
            <?php if ($current_tab === 'request'): ?>
                <div style="margin-bottom: 20px;">
                    <button onclick="location.href='as_request_handler.php'"
                        style="padding: 10px 20px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                        + NEW AS 요청 등록
                    </button>
                </div>
            <?php endif; ?>

            <!-- 검색 -->
            <form method="GET" class="search-box" id="search-form-tab">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
                <input type="date" name="search_start_date" placeholder="시작 날짜"
                    value="<?php echo htmlspecialchars($search_start_date); ?>">
                <span style="color: #999;">~</span>
                <input type="date" name="search_end_date" placeholder="종료 날짜"
                    value="<?php echo htmlspecialchars($search_end_date); ?>">
                <button type="button" class="today-btn" id="today-btn-tab" style="padding: 10px 15px; font-size: 13px;"
                    data-today="<?php echo (!empty($search_start_date) && $search_start_date === $search_end_date) ? 'on' : 'off'; ?>"
                    onclick="toggleTodayDate('search-form-tab', this)">오늘</button>

                <input type="text" name="search_customer" placeholder="고객명"
                    value="<?php echo htmlspecialchars($search_customer); ?>">
                <input type="text" name="search_phone" placeholder="전화번호"
                    value="<?php echo htmlspecialchars($search_phone); ?>">

                <button type="submit">검색</button>
                <a href="as_requests.php?tab=<?php echo htmlspecialchars($current_tab); ?>" class="btn-reset">초기화</a>
            </form>

            <!-- 정보 텍스트 -->
                <div class="info-text">
                    총 <?php echo $total_count; ?>개의 AS 요청 (페이지: <?php echo $page; ?>/<?php echo max(1, $total_pages); ?>)
                </div>

                <?php if (empty($as_list)): ?>
                    <div class="empty-state">
                        <p>데이터가 없습니다.</p>
                    </div>
                <?php else: ?>

                    <!-- 테이블 -->

                    <table class="as-table">
                        <colgroup>
                            <col class="c-no">
                            <col class="c-date">
                            <col class="c-company">
                            <col class="c-phone">
                            <col class="c-ship-method">
                            <col class="c-model">

                            <!-- 수리 내역 3칸: AS 내역, 수리비 내역, 총액 -->
                            <col class="c-as-task">
                            <col class="c-as-parts">
                            <col class="c-as-totalcost">
                            <!-- 관리: 완료(working만), 수정, 삭제(request만), 보기(completed만) -->
                            <?php if ($current_tab === 'working' || $current_tab === 'completed'): ?>
                                <col class="c-admin"> <!-- 완료/보기 -->
                            <?php endif; ?>
                            <col class="c-admin"> <!-- 수정/이전 -->
                            <?php if ($current_tab === 'request'): ?>
                                <col class="c-admin"> <!-- 삭제 -->
                            <?php endif; ?>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>번호</th>
                                <th><?php echo ($current_tab === 'completed') ? 'AS 완료일' : '접수일자'; ?></th>
                                <th>업체명</th>
                                <th>연락처</th>
                                <th>수탁</th>
                                <th>입고품목</th>
                                <th colspan="3">수리 내역</th>
                                <th colspan="2">관리</th>


                            </tr>
                            <tr style="background: #f5f5f5; font-weight: 500;">
                                <th colspan="5"></th>
                                <th>모델</th>
                                <th>AS 내역</th>
                                <th>수리비 내역</th>
                                <th>총액</th>
                                <?php if ($current_tab === 'working'): ?>
                                    <th>완료</th>
                                <?php endif; ?>
                                <?php if ($current_tab === 'completed'): ?>
                                    <th>보기</th>
                                <?php endif; ?>
                                <th>수정</th>
                                <?php if ($current_tab === 'request'): ?>
                                    <th>삭제</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $row_num = 0;
                            foreach ($as_list as $asid => $group):
                                $as_info = $group['as_info'];
                                $items = $group['items'];
                                $item_count = count($items);
                                $row_num++;
                                $number = $total_count - ($offset + $row_num - 1);

                                // 첫 번째 아이템 또는 아이템이 없는 경우 rowspan 계산
                                $rowspan = max(1, $item_count);

                                // 행 배경색 결정 (4가지 색상 순환)
                                $bg_colors = array('#ffffff', '#f5f5f5', '#f0f7ff', '#f0fff0');
                                $bg_index = ($row_num - 1) % 4;
                                $bg_color = $bg_colors[$bg_index];
                                ?>
                                <!-- AS 요청 기본 정보 -->
                                <tr data-bg="<?php echo $bg_index; ?>" data-asid="<?php echo $asid; ?>">
                                    <td rowspan="<?php echo $rowspan; ?>" style="font-weight: 600;">
                                        <?php echo $number; ?>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <?php
                                        if ($current_tab === 'completed') {
                                            // AS 완료일 + 접수일자
                                            $out_date = $as_info['s13_as_out_date'] ? substr($as_info['s13_as_out_date'], 0, 10) : '-';
                                            $in_date = $as_info['s13_as_in_date'] ? substr($as_info['s13_as_in_date'], 0, 10) : '-';
                                            echo "<div style='line-height: 1.2;'>" . htmlspecialchars($out_date) . "<br><span style='font-size: 10px; color: #999;'>(접수: " . htmlspecialchars($in_date) . ")</span></div>";
                                        } else {
                                            // 다른 탭: 접수일자
                                            echo substr($as_info['s13_as_in_date'], 0, 10);
                                        }
                                        ?>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <?php echo htmlspecialchars($as_info['ex_company'] ?? '-'); ?>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <?php
                                        $phone = '';
                                        if (!empty($as_info['s11_phone1'])) {
                                            $phone = $as_info['s11_phone1'];
                                            if (!empty($as_info['s11_phone2'])) {
                                                $phone .= '-' . $as_info['s11_phone2'];
                                            }
                                            if (!empty($as_info['s11_phone3'])) {
                                                $phone .= '-' . $as_info['s11_phone3'];
                                            }
                                        }
                                        echo htmlspecialchars($phone ?: '-');
                                        ?>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <?php echo htmlspecialchars($as_info['s13_as_in_how'] ?? '-'); ?>
                                    </td>

                                    <!-- 모델 (모델명 + 불량증상) -->
                                    <?php if ($item_count > 0): ?>
                                        <td style="text-align: left;">
                                            <div style="font-weight: bold;">
                                                <?php echo htmlspecialchars($items[0]['s15_model_name'] ?? '-'); ?>
                                            </div>
                                            <div style="color: #666; font-size: 10px; margin-top: 2px;">
                                                <?php echo htmlspecialchars($items[0]['s16_poor'] ?? '-'); ?>
                                            </div>
                                        </td>
                                    <?php else: ?>
                                        <td style="color: #999;">
                                            -
                                        </td>
                                    <?php endif; ?>

                                    <!-- AS 내역 (as_end_result) -->
                                    <td style="text-align: center;">
                                        <?php echo htmlspecialchars($items[0]['as_end_result'] ?? '-'); ?>
                                    </td>

                                    <!-- 수리비 내역 (step18_as_cure_cart) -->
                                    <td style="text-align: center; font-size: 12px; vertical-align: top; padding: 8px;">
                                        <?php if (!empty($items[0]['cure_parts'])): ?>
                                            <div style="max-height: 100px; overflow-y: auto;">
                                                <?php foreach ($items[0]['cure_parts'] as $part): ?>
                                                    <div style="margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px solid #eee;">
                                                        <strong><?php echo htmlspecialchars($part['cost_name'] ?? '-'); ?></strong><br>
                                                        수량: <?php echo $part['s18_quantity'] ?? '-'; ?> /
                                                        비용:
                                                        <?php echo number_format(($part['cost1'] ?? 0) * ($part['s18_quantity'] ?? 1)); ?>원
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <!-- 총액 (step13_as.ex_total_cost) -->
                                    <td rowspan="<?php echo $rowspan; ?>" style="text-align: center; vertical-align: middle;">
                                        <strong style="color: #000; font-size: 14px; font-weight: 600;">
                                            <?php
                                            $total = intval($as_info['ex_total_cost'] ?? 0);
                                            echo $total > 0 ? number_format($total) . '원' : '-';
                                            ?>
                                        </strong>
                                    </td>

                                    <!-- 관리 섹션 -->
                                    <?php if ($current_tab === 'working'): ?>
                                        <td rowspan="<?php echo $rowspan; ?>">
                                            <button onclick="completeAS(<?php echo $as_info['s13_asid']; ?>)"
                                                class="action-btn view">완료</button>
                                        </td>
                                    <?php elseif ($current_tab === 'completed'): ?>
                                        <td rowspan="<?php echo $rowspan; ?>">
                                            <a href="as_request_view.php?id=<?php echo intval($as_info['s13_asid'] ?? 0); ?>"
                                                class="action-btn view" target="_blank">보기</a>
                                        </td>
                                    <?php else: ?>
                                    <?php endif; ?>
                                    <?php if ($item_count > 0): ?>
                                        <?php if ($current_tab === 'request'): ?>
                                            <!-- AS작업 (수리 버튼 right align) - 비working 탭 -->
                                            <td style="text-align: right; padding-right: 8px;">
                                                <button onclick="repairItem(<?php echo $items[0]['s14_aiid']; ?>)" class="action-btn"
                                                    style="font-size: 11px; padding: 5px 8px; background: #f39c12;">수리 작업 등록</button>
                                            </td>
                                        <?php else: ?>
                                            <td rowspan="<?php echo $rowspan; ?>">
                                                <a href="as_repair_handler.php?action=restore&itemid=<?php echo $items[0]['s14_aiid']; ?>"
                                                    class="action-btn edit"
                                                    onclick="return confirm('수리 작업을 초기화하고 요청 탭으로 되돌리시겠습니까?');">이전</a>
                                            </td>
                                        <?php endif; ?>
                                        <?php if ($current_tab === 'request'): ?>
                                            <td>
                                                <a href="as_requests.php?action=delete&itemid=<?php echo $items[0]['s14_aiid']; ?>&tab=<?php echo $current_tab; ?>"
                                                    class="action-btn delete" onclick="return confirm('삭제하시겠습니까?');">삭제</a>
                                            </td>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <!-- 아이템이 없을 때 -->
                                        <td>-</td>
                                        <?php if ($current_tab === 'request'): ?>
                                            <td>
                                                <a href="as_requests.php?action=delete&asid=<?php echo $asid; ?>&tab=<?php echo $current_tab; ?>"
                                                    class="action-btn delete" onclick="return confirm('삭제하시겠습니까?');">삭제</a>
                                            </td>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </tr>

                                <!-- 추가 품목들 -->
                                <?php for ($i = 1; $i < $item_count; $i++): ?>
                                    <tr data-bg="<?php echo $bg_index; ?>" data-asid="<?php echo $asid; ?>">
                                        <td style="text-align: left;">
                                            <div style="font-weight: bold;">
                                                <?php echo htmlspecialchars($items[$i]['s15_model_name'] ?? '-'); ?>
                                            </div>
                                            <div style="color: #666; font-size: 10px; margin-top: 2px;">
                                                <?php echo htmlspecialchars($items[$i]['s16_poor'] ?? '-'); ?>
                                            </div>
                                        </td>
                                        <!-- AS 내역 -->
                                        <td style="text-align: center;">
                                            <?php echo htmlspecialchars($items[$i]['as_end_result'] ?? '-'); ?>
                                        </td>

                                        <!-- 수리비 내역 -->
                                        <td style="text-align: center; font-size: 12px; vertical-align: top; padding: 8px;">
                                            <?php if (!empty($items[$i]['cure_parts'])): ?>
                                                <div style="max-height: 100px; overflow-y: auto;">
                                                    <?php foreach ($items[$i]['cure_parts'] as $part): ?>
                                                        <div style="margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px solid #eee;">
                                                            <strong><?php echo htmlspecialchars($part['cost_name'] ?? '-'); ?></strong><br>
                                                            수량: <?php echo $part['s18_quantity'] ?? '-'; ?> /
                                                            비용:
                                                            <?php echo number_format(($part['cost1'] ?? 0) * ($part['s18_quantity'] ?? 1)); ?>원
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <!-- 관리 섹션 (추가 품목들) -->
                                        <?php if ($current_tab === 'request'): ?>
                                            <td style="text-align: right; padding-right: 8px;">
                                                <button onclick="repairItem(<?php echo $items[$i]['s14_aiid']; ?>)" class="action-btn"
                                                    style="font-size: 11px; padding: 5px 8px; background: #f39c12;">수리 작업 등록</button>
                                            </td>
                                        <?php endif; ?>
                                        <?php if ($current_tab === 'request'): ?>
                                            <td>
                                                <a href="as_requests.php?action=delete&itemid=<?php echo $items[$i]['s14_aiid']; ?>&tab=<?php echo $current_tab; ?>"
                                                    class="action-btn delete" onclick="return confirm('삭제하시겠습니까?');">삭제</a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endfor; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>


                    <!-- 페이징 -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php
                            $search_params = '&tab=' . $current_tab;
                            if (!empty($search_start_date)) {
                                $search_params .= '&search_start_date=' . urlencode($search_start_date);
                            }
                            if (!empty($search_end_date)) {
                                $search_params .= '&search_end_date=' . urlencode($search_end_date);
                            }
                            if (!empty($search_customer)) {
                                $search_params .= '&search_customer=' . urlencode($search_customer);
                            }
                            if (!empty($search_phone)) {
                                $search_params .= '&search_phone=' . urlencode($search_phone);
                            }

                            if ($page > 1) {
                                echo "<a href='as_requests.php?page=" . ($page - 1) . $search_params . "'>← 이전</a>";
                            }

                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo "<a href='as_requests.php?page=1" . $search_params . "'>1</a>";
                                if ($start_page > 2)
                                    echo "<span>...</span>";
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $page) {
                                    echo "<span class='current'>" . $i . "</span>";
                                } else {
                                    echo "<a href='as_requests.php?page=" . $i . $search_params . "'>" . $i . "</a>";
                                }
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1)
                                    echo "<span>...</span>";
                                echo "<a href='as_requests.php?page=" . $total_pages . $search_params . "'>" . $total_pages . "</a>";
                            }

                            if ($page < $total_pages) {
                                echo "<a href='as_requests.php?page=" . ($page + 1) . $search_params . "'>다음 →</a>";
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

        </div>
    </div>

    <script>
        function toggleTodayDate(formId, button) {
            const form = document.getElementById(formId);
            const startDateInput = form.querySelector('input[name="search_start_date"]');
            const endDateInput = form.querySelector('input[name="search_end_date"]');
            const today = new Date().toISOString().split('T')[0];
            const currentState = button.getAttribute('data-today');

            if (currentState === 'off') {
                // 오늘로 설정
                startDateInput.value = today;
                endDateInput.value = today;
                button.setAttribute('data-today', 'on');
                button.style.background = '#27ae60';
                button.style.color = 'white';
            } else {
                // 초기화
                startDateInput.value = '';
                endDateInput.value = '';
                button.setAttribute('data-today', 'off');
                button.style.background = '';
                button.style.color = '';
            }
        }

        // 수리 버튼 클릭 핸들러
        function repairItem(itemid) {
            // 수리 페이지로 이동
            window.location.href = 'as_repair.php?itemid=' + itemid;
        }

        // AS 완료 버튼 클릭 핸들러
        function completeAS(asid) {
            if (confirm('이 AS 작업을 완료하시겠습니까?')) {
                // AJAX로 완료 처리
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'as_request_handler.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert('AS가 완료되었습니다.');
                        location.reload(); // 페이지 새로고침
                    } else {
                        alert('오류가 발생했습니다.');
                    }
                };
                xhr.send('action=completeAS&asid=' + asid);
            }
        }

        // 페이지 로드 시 "오늘" 버튼 상태 초기화
        document.addEventListener('DOMContentLoaded', function () {
            const todayBtn = document.getElementById('today-btn-tab');
            if (todayBtn) {
                if (todayBtn.getAttribute('data-today') === 'on') {
                    todayBtn.style.background = '#27ae60';
                    todayBtn.style.color = 'white';
                }
            }

            // 테이블 행 그룹 호버 기능 - 더 간단한 방식
            const asTable = document.querySelector('table.as-table');
            if (asTable) {
                const tbody = asTable.querySelector('tbody');
                if (tbody) {
                    const rows = tbody.querySelectorAll('tr[data-asid]');
                    rows.forEach(row => {
                        // mouseover 이벤트 사용 (더 안정적)
                        row.addEventListener('mouseover', function (e) {
                            const asid = this.getAttribute('data-asid');
                            if (asid) {
                                // 같은 asid를 가진 모든 행에 클래스 추가
                                tbody.querySelectorAll(`tr[data-asid="${asid}"]`).forEach(tr => {
                                    tr.classList.add('group-hover');
                                });
                            }
                        });

                        row.addEventListener('mouseout', function (e) {
                            const asid = this.getAttribute('data-asid');
                            if (asid) {
                                // 같은 asid를 가진 모든 행에서 클래스 제거
                                tbody.querySelectorAll(`tr[data-asid="${asid}"]`).forEach(tr => {
                                    tr.classList.remove('group-hover');
                                });
                            }
                        });
                    });
                }
            }
        });
    </script>
</body>

</html>
<?php mysql_close($connect); ?>