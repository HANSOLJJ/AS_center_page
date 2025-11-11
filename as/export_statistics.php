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

// 날짜 범위 파라미터
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$range = isset($_GET['range']) ? $_GET['range'] : 'month';

// 기간 설정
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');
$year_start = date('Y-01-01');

if ($range === 'today') {
    $start_date = $today;
    $end_date = $today;
} elseif ($range === 'week') {
    $start_date = $week_start;
    $end_date = $today;
} elseif ($range === 'month') {
    $start_date = $month_start;
    $end_date = $today;
} elseif ($range === 'year') {
    $start_date = $year_start;
    $end_date = $today;
} elseif ($range === '' || $range === 'all') {
    $start_date = '';
    $end_date = '';
}

// ===== 데이터 조회 함수들 =====

// 종합 통계
function getStatistics($connect, $start_date, $end_date)
{
    $as_where = (!empty($start_date) && !empty($end_date))
        ? "WHERE DATE(s13_as_in_date) BETWEEN '$start_date' AND '$end_date'"
        : "";

    $as_query = "SELECT
        COUNT(*) as total_as,
        SUM(CASE WHEN s13_as_level NOT IN ('2','3','4','5') THEN 1 ELSE 0 END) as as_request,
        SUM(CASE WHEN s13_as_level IN ('2','3','4') THEN 1 ELSE 0 END) as as_working,
        SUM(CASE WHEN s13_as_level = '5' THEN 1 ELSE 0 END) as as_completed,
        SUM(COALESCE(ex_total_cost, 0)) as total_as_cost
        FROM step13_as
        $as_where";

    $as_result = mysql_query($as_query);
    $as_stats = mysql_fetch_assoc($as_result) ?? array();

    $sales_where = (!empty($start_date) && !empty($end_date))
        ? "WHERE DATE(s20_sell_in_date) BETWEEN '$start_date' AND '$end_date'"
        : "";

    $sales_query = "SELECT
        COUNT(*) as total_sales,
        SUM(CASE WHEN s20_sell_level = '1' THEN 1 ELSE 0 END) as sales_request,
        SUM(CASE WHEN s20_sell_level = '2' THEN 1 ELSE 0 END) as sales_completed,
        SUM(COALESCE(s20_total_cost, 0)) as total_sales_cost
        FROM step20_sell
        $sales_where";

    $sales_result = mysql_query($sales_query);
    $sales_stats = mysql_fetch_assoc($sales_result) ?? array();

    return array('as' => $as_stats, 'sales' => $sales_stats);
}

// 월별 AS 통계
function getMonthlyASStats($connect)
{
    $query = "SELECT
        DATE_FORMAT(s13_as_out_date, '%Y-%m') as month,
        SUM(CASE WHEN s13_as_level = '5' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN s13_as_level = '5' THEN COALESCE(ex_total_cost, 0) ELSE 0 END) as total_cost
        FROM step13_as
        WHERE s13_as_level = '5' AND s13_as_out_date IS NOT NULL
        GROUP BY DATE_FORMAT(s13_as_out_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 월별 판매 통계
function getMonthlySalesStats($connect)
{
    $query = "SELECT
        DATE_FORMAT(s20_sell_out_date, '%Y-%m') as month,
        SUM(CASE WHEN s20_sell_level = '2' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN s20_sell_level = '2' THEN COALESCE(s20_total_cost, 0) ELSE 0 END) as total_cost
        FROM step20_sell
        WHERE s20_sell_level = '2' AND s20_sell_out_date IS NOT NULL
        GROUP BY DATE_FORMAT(s20_sell_out_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// TOP3 수리 제품
function getTopRepairProducts($connect, $start_date, $end_date)
{
    $where_clause = (!empty($start_date) && !empty($end_date))
        ? "WHERE a.s13_as_level = '5' AND DATE(a.s13_as_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE a.s13_as_level = '5'";

    $query = "SELECT
        b.s14_model,
        MAX(b.cost_name) as cost_name,
        COUNT(*) as count
        FROM step14_as_item b
        LEFT JOIN step13_as a ON b.s14_asid = a.s13_asid
        $where_clause
        GROUP BY b.s14_model
        ORDER BY count DESC
        LIMIT 3";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// TOP3 수리 자재
function getTopRepairParts($connect, $start_date, $end_date)
{
    $where_clause = (!empty($start_date) && !empty($end_date))
        ? "WHERE a.s13_as_level = '5' AND DATE(a.s13_as_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE a.s13_as_level = '5'";

    $query = "SELECT
        c.s18_uid,
        c.cost_name,
        SUM(c.s18_quantity) as total_qty,
        COUNT(*) as item_count
        FROM step18_as_cure_cart c
        LEFT JOIN step14_as_item b ON c.s18_aiid = b.s14_aiid
        LEFT JOIN step13_as a ON b.s14_asid = a.s13_asid
        $where_clause
        GROUP BY c.s18_uid, c.cost_name
        ORDER BY total_qty DESC
        LIMIT 3";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// TOP3 판매 자재
function getTopSaleParts($connect, $start_date, $end_date)
{
    $where_clause = (!empty($start_date) && !empty($end_date))
        ? "WHERE s.s20_sell_level = '2' AND DATE(s.s20_sell_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE s.s20_sell_level = '2'";

    $query = "SELECT
        c.s21_uid,
        c.cost_name,
        SUM(c.s21_quantity) as total_qty,
        COUNT(*) as item_count
        FROM step21_sell_cart c
        LEFT JOIN step20_sell s ON c.s21_sellid = s.s20_sellid
        $where_clause
        GROUP BY c.s21_uid, c.cost_name
        ORDER BY total_qty DESC
        LIMIT 3";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 상세 AS 목록
function getDetailedASList($connect, $start_date, $end_date)
{
    $where_clause = (!empty($start_date) && !empty($end_date))
        ? "WHERE a.s13_as_level = '5' AND DATE(a.s13_as_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE a.s13_as_level = '5'";

    $query = "SELECT
        a.s13_asid,
        a.s13_as_out_no,
        DATE_FORMAT(a.s13_as_in_date, '%Y-%m-%d') as request_date,
        DATE_FORMAT(a.s13_as_out_date, '%Y-%m-%d') as complete_date,
        b.ex_company,
        b.ex_tel,
        c.s14_model,
        c.s14_poor,
        COALESCE(a.ex_total_cost, 0) as total_cost
        FROM step13_as a
        LEFT JOIN step11_member b ON a.s13_mid = b.ex_mid
        LEFT JOIN step14_as_item c ON a.s13_asid = c.s14_asid
        $where_clause
        ORDER BY a.s13_as_out_date DESC
        LIMIT 200";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 상세 판매 목록
function getDetailedSalesList($connect, $start_date, $end_date)
{
    $where_clause = (!empty($start_date) && !empty($end_date))
        ? "WHERE s.s20_sell_level = '2' AND DATE(s.s20_sell_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE s.s20_sell_level = '2'";

    $query = "SELECT
        s.s20_sellid,
        s.s20_sell_out_no,
        DATE_FORMAT(s.s20_sell_in_date, '%Y-%m-%d') as request_date,
        DATE_FORMAT(s.s20_sell_out_date, '%Y-%m-%d') as complete_date,
        m.ex_company,
        m.ex_tel,
        GROUP_CONCAT(c.cost_name, '(', c.s21_quantity, '개)' SEPARATOR ' | ') as items,
        COALESCE(s.s20_total_cost, 0) as total_cost
        FROM step20_sell s
        LEFT JOIN step11_member m ON s.s20_meid = m.ex_mid
        LEFT JOIN step21_sell_cart c ON s.s20_sellid = c.s21_sellid
        $where_clause
        GROUP BY s.s20_sellid
        ORDER BY s.s20_sell_out_date DESC
        LIMIT 200";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 데이터 조회
$stats = getStatistics($connect, $start_date, $end_date);
$monthly_as = getMonthlyASStats($connect);
$monthly_sales = getMonthlySalesStats($connect);
$top_products = getTopRepairProducts($connect, $start_date, $end_date);
$top_parts = getTopRepairParts($connect, $start_date, $end_date);
$top_sale_parts = getTopSaleParts($connect, $start_date, $end_date);
$detail_as = getDetailedASList($connect, $start_date, $end_date);
$detail_sales = getDetailedSalesList($connect, $start_date, $end_date);

// ===== EXCEL 파일 생성 (HTML 호환 포맷) =====

$filename = 'AS_통계_' . date('Y-m-d_H-i-s') . '.xls';

// Excel 호환 HTML 헤더
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=" . iconv('utf-8', 'cp949', $filename));
header("Content-Description: PHP Generated Data");
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
    .header-cell { font-weight: bold; background-color: #4472C4; color: white; text-align: center; border: 1px solid black; }
    .data-cell { border: 1px solid #D3D3D3; padding: 5px; }
    .section-title { font-weight: bold; background-color: #D9E1F2; border: 1px solid black; padding: 5px; }
    .stat-header { font-weight: bold; background-color: #FFF2CC; border: 1px solid black; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    tr { height: 25px; }
    td { padding: 5px; }
</style>
</head>
<body>

<!-- Sheet 1: 요약 통계 -->
<table>
    <tr>
        <td colspan="4" class="section-title">📊 통계 보고서 - 요약</td>
    </tr>
    <tr>
        <td class="stat-header">기간</td>
        <td colspan="3" class="data-cell">
            <?php
            if (empty($start_date) && empty($end_date)) {
                echo "전체 기간";
            } else {
                echo htmlspecialchars($start_date) . " ~ " . htmlspecialchars($end_date);
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="stat-header">생성일</td>
        <td colspan="3" class="data-cell"><?php echo date('Y-m-d H:i:s'); ?></td>
    </tr>
    <tr>
        <td colspan="4" style="height: 10px;"></td>
    </tr>

    <!-- AS 통계 -->
    <tr>
        <td colspan="4" class="section-title">🔧 AS 통계</td>
    </tr>
    <tr>
        <td class="header-cell">항목</td>
        <td class="header-cell">건수</td>
        <td class="header-cell">매출</td>
        <td class="header-cell">비고</td>
    </tr>
    <tr>
        <td class="data-cell">전체 AS</td>
        <td class="data-cell" align="right"><?php echo number_format($stats['as']['total_as'] ?? 0); ?></td>
        <td class="data-cell" align="right"><?php echo number_format(intval($stats['as']['total_as_cost'] ?? 0)); ?></td>
        <td class="data-cell">요청 포함</td>
    </tr>
    <tr>
        <td class="data-cell">AS 완료</td>
        <td class="data-cell" align="right"><?php echo number_format($stats['as']['as_completed'] ?? 0); ?></td>
        <td class="data-cell" align="right"><?php echo number_format(intval($stats['as']['total_as_cost'] ?? 0)); ?></td>
        <td class="data-cell">최종 완료</td>
    </tr>
    <tr>
        <td colspan="4" style="height: 10px;"></td>
    </tr>

    <!-- 판매 통계 -->
    <tr>
        <td colspan="4" class="section-title">🔋 판매 통계</td>
    </tr>
    <tr>
        <td class="header-cell">항목</td>
        <td class="header-cell">건수</td>
        <td class="header-cell">매출</td>
        <td class="header-cell">비고</td>
    </tr>
    <tr>
        <td class="data-cell">전체 판매</td>
        <td class="data-cell" align="right"><?php echo number_format($stats['sales']['total_sales'] ?? 0); ?></td>
        <td class="data-cell" align="right"><?php echo number_format(intval($stats['sales']['total_sales_cost'] ?? 0)); ?></td>
        <td class="data-cell">요청 포함</td>
    </tr>
    <tr>
        <td class="data-cell">판매 완료</td>
        <td class="data-cell" align="right"><?php echo number_format($stats['sales']['sales_completed'] ?? 0); ?></td>
        <td class="data-cell" align="right"><?php echo number_format(intval($stats['sales']['total_sales_cost'] ?? 0)); ?></td>
        <td class="data-cell">입금 확인</td>
    </tr>
    <tr>
        <td colspan="4" style="height: 10px;"></td>
    </tr>

    <!-- TOP3 수리 제품 -->
    <tr>
        <td colspan="4" class="section-title">TOP3 수리 제품</td>
    </tr>
    <tr>
        <td class="header-cell">순위</td>
        <td class="header-cell">제품명</td>
        <td class="header-cell">건수</td>
        <td class="header-cell"></td>
    </tr>
    <?php
    foreach ($top_products as $idx => $product) {
        echo '<tr>';
        echo '<td class="data-cell" align="center">' . ($idx + 1) . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($product['cost_name']) . '</td>';
        echo '<td class="data-cell" align="right">' . $product['count'] . '</td>';
        echo '<td class="data-cell"></td>';
        echo '</tr>';
    }
    ?>
    <tr>
        <td colspan="4" style="height: 10px;"></td>
    </tr>

    <!-- TOP3 수리 자재 -->
    <tr>
        <td colspan="4" class="section-title">TOP3 수리 자재</td>
    </tr>
    <tr>
        <td class="header-cell">순위</td>
        <td class="header-cell">자재명</td>
        <td class="header-cell">사용량</td>
        <td class="header-cell"></td>
    </tr>
    <?php
    foreach ($top_parts as $idx => $part) {
        echo '<tr>';
        echo '<td class="data-cell" align="center">' . ($idx + 1) . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($part['cost_name']) . '</td>';
        echo '<td class="data-cell" align="right">' . $part['total_qty'] . '</td>';
        echo '<td class="data-cell"></td>';
        echo '</tr>';
    }
    ?>
    <tr>
        <td colspan="4" style="height: 10px;"></td>
    </tr>

    <!-- TOP3 판매 자재 -->
    <tr>
        <td colspan="4" class="section-title">TOP3 판매 자재</td>
    </tr>
    <tr>
        <td class="header-cell">순위</td>
        <td class="header-cell">자재명</td>
        <td class="header-cell">판매량</td>
        <td class="header-cell"></td>
    </tr>
    <?php
    foreach ($top_sale_parts as $idx => $part) {
        echo '<tr>';
        echo '<td class="data-cell" align="center">' . ($idx + 1) . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($part['cost_name']) . '</td>';
        echo '<td class="data-cell" align="right">' . $part['total_qty'] . '</td>';
        echo '<td class="data-cell"></td>';
        echo '</tr>';
    }
    ?>
</table>

<!-- Sheet 2: 월별 AS 통계 -->
<table>
    <tr>
        <td colspan="3" class="section-title">월별 AS 현황</td>
    </tr>
    <tr>
        <td class="header-cell">월</td>
        <td class="header-cell" align="right">완료건수</td>
        <td class="header-cell" align="right">매출</td>
    </tr>
    <?php
    foreach ($monthly_as as $row) {
        echo '<tr>';
        echo '<td class="data-cell">' . htmlspecialchars($row['month']) . '</td>';
        echo '<td class="data-cell" align="right">' . number_format($row['completed']) . '</td>';
        echo '<td class="data-cell" align="right">' . number_format(intval($row['total_cost'])) . '</td>';
        echo '</tr>';
    }
    ?>
</table>

<!-- Sheet 3: 월별 판매 통계 -->
<table>
    <tr>
        <td colspan="3" class="section-title">월별 판매 현황</td>
    </tr>
    <tr>
        <td class="header-cell">월</td>
        <td class="header-cell" align="right">완료건수</td>
        <td class="header-cell" align="right">매출</td>
    </tr>
    <?php
    foreach ($monthly_sales as $row) {
        echo '<tr>';
        echo '<td class="data-cell">' . htmlspecialchars($row['month']) . '</td>';
        echo '<td class="data-cell" align="right">' . number_format($row['completed']) . '</td>';
        echo '<td class="data-cell" align="right">' . number_format(intval($row['total_cost'])) . '</td>';
        echo '</tr>';
    }
    ?>
</table>

<!-- Sheet 4: 상세 AS 목록 -->
<table>
    <tr>
        <td colspan="9" class="section-title">상세 AS 목록</td>
    </tr>
    <tr>
        <td class="header-cell">완료번호</td>
        <td class="header-cell">요청일</td>
        <td class="header-cell">완료일</td>
        <td class="header-cell">고객명</td>
        <td class="header-cell">연락처</td>
        <td class="header-cell">제품명</td>
        <td class="header-cell">불량증상</td>
        <td class="header-cell" align="right">금액</td>
        <td class="header-cell"></td>
    </tr>
    <?php
    foreach ($detail_as as $row) {
        echo '<tr>';
        echo '<td class="data-cell">' . htmlspecialchars($row['s13_as_out_no'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['request_date'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['complete_date'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['ex_company'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['ex_tel'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['s14_model'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['s14_poor'] ?? '-') . '</td>';
        echo '<td class="data-cell" align="right">' . number_format(intval($row['total_cost'])) . '</td>';
        echo '<td class="data-cell"></td>';
        echo '</tr>';
    }
    ?>
</table>

<!-- Sheet 5: 상세 판매 목록 -->
<table>
    <tr>
        <td colspan="8" class="section-title">상세 판매 목록</td>
    </tr>
    <tr>
        <td class="header-cell">완료번호</td>
        <td class="header-cell">요청일</td>
        <td class="header-cell">완료일</td>
        <td class="header-cell">고객명</td>
        <td class="header-cell">연락처</td>
        <td class="header-cell">판매자재</td>
        <td class="header-cell" align="right">금액</td>
        <td class="header-cell"></td>
    </tr>
    <?php
    foreach ($detail_sales as $row) {
        echo '<tr>';
        echo '<td class="data-cell">' . htmlspecialchars($row['s20_sell_out_no'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['request_date'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['complete_date'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['ex_company'] ?? '-') . '</td>';
        echo '<td class="data-cell">' . htmlspecialchars($row['ex_tel'] ?? '-') . '</td>';
        echo '<td class="data-cell" style="max-width: 150px;">' . htmlspecialchars($row['items'] ?? '-') . '</td>';
        echo '<td class="data-cell" align="right">' . number_format(intval($row['total_cost'])) . '</td>';
        echo '<td class="data-cell"></td>';
        echo '</tr>';
    }
    ?>
</table>

</body>
</html>
<?php
mysql_close($connect);
?>
