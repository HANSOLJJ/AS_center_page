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
$current_page = 'as_statistics';

// 탭 선택
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
$current_tab = in_array($tab, ['overview', 'monthly_report', 'as_analysis', 'sales_analysis']) ? $tab : 'overview';

// 기간 설정 (기본값: 금월)
$range = isset($_GET['range']) ? $_GET['range'] : 'month';
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
    // 전체 기간: 날짜 제한 없음
    $start_date = '';
    $end_date = '';
} else {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $today;
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $today;
}

// 통계 데이터 조회 함수
function getStatistics($connect, $start_date, $end_date)
{
    // AS 통계 (완료 기준: s13_as_out_date)
    $as_where = (!empty($start_date) && !empty($end_date))
        ? "WHERE s13_as_level = '5' AND DATE(s13_as_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE s13_as_level = '5'";

    $as_query = "SELECT
        COUNT(*) as total_as,
        SUM(CASE WHEN s13_as_level NOT IN ('2','3','4','5') THEN 1 ELSE 0 END) as as_request,
        SUM(CASE WHEN s13_as_level IN ('2','3','4') THEN 1 ELSE 0 END) as as_working,
        SUM(CASE WHEN s13_as_level = '5' THEN 1 ELSE 0 END) as as_completed,
        SUM(COALESCE(s13_total_cost, 0)) as total_as_cost
        FROM step13_as
        $as_where";

    $as_result = mysql_query($as_query);
    $as_stats = mysql_fetch_assoc($as_result) ?? array();

    // 자재 판매 통계 (완료 기준: s20_sell_out_date)
    $sales_where = (!empty($start_date) && !empty($end_date))
        ? "WHERE s20_sell_level = '2' AND DATE(s20_sell_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE s20_sell_level = '2'";

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

// 월별 AS 통계 (완료 기준)
function getMonthlyASStats($connect)
{
    $query = "SELECT
        DATE_FORMAT(s13_as_out_date, '%Y-%m') as month,
        SUM(CASE WHEN s13_as_level = '5' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN s13_as_level = '5' THEN COALESCE(s13_total_cost, 0) ELSE 0 END) as total_cost
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

// 월별 판매 통계 (완료 기준)
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

// TOP10 수리 모델 (step14_as_item에서 s14_model별 count, step13_as s13_as_out_date 기준)
function getTopRepairModels($connect, $start_date, $end_date)
{
    $where_clause = (!empty($start_date) && !empty($end_date))
        ? "WHERE a.s13_as_level = '5' AND DATE(a.s13_as_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE a.s13_as_level = '5'";

    $query = "SELECT
        b.s14_model,
        m.s15_model_name as model_name,
        COUNT(*) as repair_count
        FROM step14_as_item b
        LEFT JOIN step13_as a ON b.s14_asid = a.s13_asid
        LEFT JOIN step15_as_model m ON b.s14_model = m.s15_amid
        $where_clause
        GROUP BY b.s14_model, m.s15_model_name
        ORDER BY repair_count DESC
        LIMIT 10";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// TOP10 교체 자재 (step18_as_cure_cart에서 s18_uid별로 s18_quantity 합산, step13_as s13_as_out_date 기준)
function getTopReplacementParts($connect, $start_date, $end_date)
{
    $where_clause = (!empty($start_date) && !empty($end_date))
        ? "WHERE a.s13_as_level = '5' AND DATE(a.s13_as_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE a.s13_as_level = '5'";

    $query = "SELECT
        c.s18_uid,
        c.cost_name as part_name,
        SUM(c.s18_quantity) as total_quantity,
        COUNT(*) as usage_count
        FROM step18_as_cure_cart c
        LEFT JOIN step14_as_item b ON c.s18_aiid = b.s14_aiid
        LEFT JOIN step13_as a ON b.s14_asid = a.s13_asid
        $where_clause
        GROUP BY c.s18_uid, c.cost_name
        ORDER BY total_quantity DESC
        LIMIT 10";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// TOP3 수리 제품 (step14_as_item에서 s14_model별 count, step13_as s13_as_out_date 기준)
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

// TOP3 수리 자재 (step18_as_cure_cart에서 s18_uid와 s18_quantity 고려)
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

// 연도별 판매액 통계 (2012년부터)
function getYearlySalesStats($connect)
{
    $query = "SELECT
        YEAR(s20_sell_out_date) as year,
        SUM(COALESCE(s20_total_cost, 0)) as total_cost,
        COUNT(*) as count
        FROM step20_sell
        WHERE s20_sell_level = '2' AND s20_sell_out_date IS NOT NULL AND YEAR(s20_sell_out_date) >= 2012
        GROUP BY YEAR(s20_sell_out_date)
        ORDER BY year ASC";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 올해 월별 판매액 통계
function getCurrentYearMonthlySalesStats($connect)
{
    $current_year = date('Y');
    $query = "SELECT
        MONTH(s20_sell_out_date) as month,
        SUM(COALESCE(s20_total_cost, 0)) as total_cost,
        COUNT(*) as count
        FROM step20_sell
        WHERE s20_sell_level = '2' AND s20_sell_out_date IS NOT NULL AND YEAR(s20_sell_out_date) = $current_year
        GROUP BY MONTH(s20_sell_out_date)
        ORDER BY month ASC";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 연도별 AS 통계 (step13_as에서 s13_total_cost 기준)
function getYearlyASStats($connect)
{
    $query = "SELECT
        YEAR(s13_as_out_date) as year,
        SUM(COALESCE(s13_total_cost, 0)) as total_cost,
        COUNT(*) as count
        FROM step13_as
        WHERE s13_as_level = '5' AND s13_as_out_date IS NOT NULL AND YEAR(s13_as_out_date) >= 2012
        GROUP BY YEAR(s13_as_out_date)
        ORDER BY year ASC";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 올해 월별 AS 통계
function getCurrentYearMonthlyASStats($connect)
{
    $current_year = date('Y');
    $query = "SELECT
        MONTH(s13_as_out_date) as month,
        SUM(COALESCE(s13_total_cost, 0)) as total_cost,
        COUNT(*) as count
        FROM step13_as
        WHERE s13_as_level = '5' AND s13_as_out_date IS NOT NULL AND YEAR(s13_as_out_date) = $current_year
        GROUP BY MONTH(s13_as_out_date)
        ORDER BY month ASC";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// TOP10 판매 자재 (step20_sell에서 판매 완료 기준, step21_sell_cart에서 수량 합산)
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
        LIMIT 10";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 매출 포맷팅 함수 (모두 원으로 표시)
function formatRevenue($cost)
{
    $cost = intval($cost);
    return '<div class="revenue-amount">' . number_format($cost) . '</div><div class="revenue-unit">원</div>';
}

// 월간 통합 리포트 데이터 조회 (본사만)
function getMonthlyIntegratedReport($connect, $report_year, $report_month)
{
    $start_date = $report_year . '-' . $report_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));

    // AS 데이터 조회 (본사만)
    $as_query = "SELECT
        COUNT(*) as as_count,
        COALESCE(SUM(s13_total_cost), 0) as as_total_cost
        FROM step13_as
        WHERE s13_as_level = '5'
        AND s13_as_center = 'center1283763850'
        AND DATE(s13_as_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
            AND '" . mysql_real_escape_string($end_date) . "'";

    $as_result = mysql_query($as_query);
    $as_data = mysql_fetch_assoc($as_result);

    // 판매 데이터 조회 (본사만)
    $sell_query = "SELECT
        COUNT(*) as sell_count,
        COALESCE(SUM(s20_total_cost), 0) as sell_total_cost
        FROM step20_sell
        WHERE s20_sell_level = '2'
        AND s20_sell_center = 'center1283763850'
        AND DATE(s20_sell_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
            AND '" . mysql_real_escape_string($end_date) . "'";

    $sell_result = mysql_query($sell_query);
    $sell_data = mysql_fetch_assoc($sell_result);

    return array(
        'as_count' => intval($as_data['as_count']),
        'as_total_cost' => intval($as_data['as_total_cost']),
        'sell_count' => intval($sell_data['sell_count']),
        'sell_total_cost' => intval($sell_data['sell_total_cost'])
    );
}

// 통계 데이터 조회
$stats = getStatistics($connect, $start_date, $end_date);
$monthly_as = getMonthlyASStats($connect);
$monthly_sales = getMonthlySalesStats($connect);
$top_repair_models = getTopRepairModels($connect, $start_date, $end_date);
$top_replacement_parts = getTopReplacementParts($connect, $start_date, $end_date);
$top_products = getTopRepairProducts($connect, $start_date, $end_date);
$top_parts = getTopRepairParts($connect, $start_date, $end_date);
$top_sale_parts = getTopSaleParts($connect, $start_date, $end_date);

// 판매분석용 그래프 데이터
$yearly_sales = getYearlySalesStats($connect);
$current_year_monthly_sales = getCurrentYearMonthlySalesStats($connect);

// AS분석용 그래프 데이터
$yearly_as = getYearlyASStats($connect);
$current_year_monthly_as = getCurrentYearMonthlyASStats($connect);

// 월간 리포트 탭 데이터 조회
$report_year = isset($_GET['report_year']) ? intval($_GET['report_year']) : date('Y');
$report_month = isset($_GET['report_month']) ? $_GET['report_month'] : date('m');
if ($report_month < 1 || $report_month > 12) {
    $report_month = date('m');
}
$monthly_report_data = getMonthlyIntegratedReport($connect, $report_year, $report_month);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>통계/분석 - AS 시스템</title>
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

        .date-filter {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .date-filter-controls {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .date-filter-buttons {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }

        .date-filter-btn {
            padding: 8px 16px;
            background: white !important;
            color: #667eea !important;
            border: 2px solid #667eea;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s;
        }

        .date-filter-btn:hover {
            background: #f0f4ff !important;
        }

        .date-filter-btn.active {
            background: #667eea !important;
            color: white !important;
            border-color: #667eea !important;
        }

        .date-filter input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .date-filter button[type="submit"] {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }

        .date-filter button[type="submit"]:hover {
            background: #5568d3;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1.5fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stat-card.as-card {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .stat-card.sales-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .stat-card h4 {
            font-size: 13px;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 12px;
            opacity: 0.8;
        }

        .top-list {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
        }

        .top-list ol {
            margin: 0;
            padding-left: 20px;
        }

        .top-list li {
            padding: 8px 0;
            color: #666;
            line-height: 1.6;
        }

        .revenue-amount {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .revenue-unit {
            font-size: 11px;
            opacity: 0.7;
        }

        .table-section {
            margin-top: 30px;
        }

        .table-section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table thead {
            background: #667eea;
            color: white;
        }

        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }

        table tbody tr:hover {
            background: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
        <a href="parts.php" class="nav-item">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
        <a href="as_statistics.php"
            class="nav-item <?php echo $current_page === 'as_statistics' ? 'active' : ''; ?>">통계/분석</a>
    </div>

    <div class="container">
        <div class="content">
            <h2>📊 통계/분석</h2>

            <!-- 탭 -->
            <div class="tabs">
                <button class="tab-btn <?php echo $current_tab === 'overview' ? 'active' : ''; ?>"
                    onclick="location.href='as_statistics.php?tab=overview'">개요</button>
                <button class="tab-btn <?php echo $current_tab === 'monthly_report' ? 'active' : ''; ?>"
                    onclick="location.href='as_statistics.php?tab=monthly_report'">월간 리포트</button>
                <button class="tab-btn <?php echo $current_tab === 'as_analysis' ? 'active' : ''; ?>"
                    onclick="location.href='as_statistics.php?tab=as_analysis'">AS 분석</button>
                <button class="tab-btn <?php echo $current_tab === 'sales_analysis' ? 'active' : ''; ?>"
                    onclick="location.href='as_statistics.php?tab=sales_analysis'">판매 분석</button>
            </div>

            <!-- 기간 필터 -->
            <?php if ($current_tab === 'monthly_report'): ?>
                <!-- 월간 리포트 탭: 연도/월 선택 -->
                <div class="date-filter-controls" style="display: flex; align-items: center; gap: 10px;">
                    <label style="font-weight: 500; margin: 0;">기간 선택:</label>

                    <!-- 연도 선택 -->
                    <select id="report_year_select"
                        style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; width: 120px; min-width: 120px;"
                        onchange="updateMonthlyReport()">
                        <?php
                        $current_year = intval(date('Y'));
                        for ($y = $current_year; $y >= 2012; $y--) {
                            $isSelected = (isset($_GET['report_year']) && intval($_GET['report_year']) == $y) ? true : false;
                            $selAttr = $isSelected ? ' selected="selected"' : '';
                            echo '<option value="' . $y . '"' . $selAttr . '>' . $y . '년</option>' . "\n";
                        }
                        ?>
                    </select>

                    <!-- 월 선택 -->
                    <select id="report_month_select"
                        style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; width: 100px; min-width: 100px;"
                        onchange="updateMonthlyReport()">
                        <?php
                        $current_month = date('m');
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = (isset($_GET['report_month']) && $_GET['report_month'] == $m) ? 'selected' : '';
                            echo "<option value=\"" . str_pad($m, 2, '0', STR_PAD_LEFT) . "\" $selected>" . str_pad($m, 2, '0', STR_PAD_LEFT) . "월</option>";
                        }
                        ?>
                    </select>

                    <button type="button" onclick="downloadMonthlyReport()"
                        style="padding: 8px 20px; background: #8b5cf6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; margin-left: auto;">📥
                        월간 종합 리포트</button>
                </div>
            <?php elseif ($current_tab === 'overview'): ?>
                <!-- 개요 탭: 기존 기간 필터 + 리포트 버튼 -->
                <form method="GET" class="date-filter">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">

                    <div class="date-filter-buttons">
                        <button type="button" class="date-filter-btn <?php echo $range === '' ? 'active' : ''; ?>"
                            onclick="setDateRange('all', this.form); this.form.submit();">전체 기간</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'today' ? 'active' : ''; ?>"
                            onclick="setDateRange('today', this.form); this.form.submit();">오늘</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'week' ? 'active' : ''; ?>"
                            onclick="setDateRange('week', this.form); this.form.submit();">금주</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'month' ? 'active' : ''; ?>"
                            onclick="setDateRange('month', this.form); this.form.submit();">금월</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'year' ? 'active' : ''; ?>"
                            onclick="setDateRange('year', this.form); this.form.submit();">금년</button>
                    </div>

                    <div class="date-filter-controls">
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        <span style="color: #999;">~</span>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        <input type="hidden" id="range-input-stat" name="range"
                            value="<?php echo htmlspecialchars($range); ?>">
                        <button type="submit">검색</button>
                        <button type="button" onclick="downloadReport('export_as_report.php')"
                            style="margin-left: 10px; padding: 8px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block;">📥
                            AS 리포트</button>
                        <button type="button" onclick="downloadReport('export_sales_report.php')"
                            style="margin-left: 5px; padding: 8px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block;">📥
                            판매 리포트</button>
                    </div>
                </form>
            <?php else: ?>
                <!-- AS 분석, 판매 분석 탭: 기간 필터만 (리포트 버튼 없음) -->
                <form method="GET" class="date-filter">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">

                    <div class="date-filter-buttons">
                        <button type="button" class="date-filter-btn <?php echo $range === '' ? 'active' : ''; ?>"
                            onclick="setDateRange('all', this.form); this.form.submit();">전체 기간</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'today' ? 'active' : ''; ?>"
                            onclick="setDateRange('today', this.form); this.form.submit();">오늘</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'week' ? 'active' : ''; ?>"
                            onclick="setDateRange('week', this.form); this.form.submit();">금주</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'month' ? 'active' : ''; ?>"
                            onclick="setDateRange('month', this.form); this.form.submit();">금월</button>
                        <button type="button" class="date-filter-btn <?php echo $range === 'year' ? 'active' : ''; ?>"
                            onclick="setDateRange('year', this.form); this.form.submit();">금년</button>
                    </div>

                    <div class="date-filter-controls">
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        <span style="color: #999;">~</span>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        <input type="hidden" id="range-input-stat" name="range"
                            value="<?php echo htmlspecialchars($range); ?>">
                        <button type="submit">검색</button>
                    </div>
                </form>
            <?php endif; ?>

            <script>
                // 리포트 다운로드 함수 (브라우저 다운로드 다이얼로그 표시)
                function downloadReport(filename) {
                    const startDate = document.querySelector('input[name="start_date"]').value;
                    const endDate = document.querySelector('input[name="end_date"]').value;
                    const range = document.getElementById('range-input-stat').value;

                    const url = filename + '?start_date=' + encodeURIComponent(startDate) +
                        '&end_date=' + encodeURIComponent(endDate) +
                        '&range=' + encodeURIComponent(range);

                    // 브라우저 다운로드 다이얼로그 표시 (열기/저장 선택 가능)
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = true;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                // 월간 리포트 기간 업데이트 함수 (페이지 새로고침)
                function updateMonthlyReport() {
                    const reportYear = document.getElementById('report_year_select').value;
                    const reportMonth = document.getElementById('report_month_select').value;
                    window.location.href = 'as_statistics.php?tab=monthly_report&report_year=' + reportYear + '&report_month=' + reportMonth;
                }

                // 월간 종합 리포트 다운로드 함수
                function downloadMonthlyReport() {
                    const reportYear = document.getElementById('report_year_select').value;
                    const reportMonth = document.getElementById('report_month_select').value;

                    const url = 'export_monthly_report.php?report_year=' + encodeURIComponent(reportYear) +
                        '&report_month=' + encodeURIComponent(reportMonth);

                    // 브라우저 다운로드 다이얼로그 표시
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = true;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }

                function setDateRange(range, form) {
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
                    } else if (range === 'all') {
                        startDate = '';
                        endDate = '';
                    }

                    form.start_date.value = startDate;
                    form.end_date.value = endDate;
                    document.getElementById('range-input-stat').value = (range === 'all' ? '' : range);
                    // range 버튼 시 자동 submit하지 않음
                }

                // form 제출 시 range 값이 설정되지 않은 경우 초기화
                document.addEventListener('DOMContentLoaded', function () {
                    const dateForm = document.querySelector('.date-filter');
                    if (dateForm) {
                        dateForm.addEventListener('submit', function (e) {
                            // 사용자가 직접 입력한 날짜는 range를 초기화
                            if (document.activeElement.name === 'start_date' ||
                                document.activeElement.name === 'end_date' ||
                                document.activeElement.type === 'submit') {
                                dateForm.range.value = '';
                            }
                        });
                    }
                });
            </script>

            <?php if ($current_tab === 'overview'): ?>
                <!-- 개요 탭 -->
                <h3 style="color: #667eea; margin-bottom: 20px; font-size: 16px;">📈 종합 통계</h3>

                <!-- AS 통계 -->
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #3b82f6; margin-bottom: 15px; font-size: 14px;">🔧 AS 통계</h3>
                    <div class="stats-grid">
                        <div class="stat-card as-card">
                            <h4>AS 완료</h4>
                            <div class="number"><?php echo number_format($stats['as']['as_completed'] ?? 0); ?></div>
                            <div class="label">건</div>
                        </div>
                        <div class="stat-card as-card">
                            <h4>TOP3 수리 제품</h4>
                            <div class="top-list">
                                <ol>
                                    <?php foreach ($top_products as $idx => $product): ?>
                                        <li><?php echo htmlspecialchars($product['cost_name']); ?> <span
                                                style="font-weight: bold; color: #2563eb;"><?php echo $product['count']; ?>건</span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($top_products) === 0): ?>
                                        <li>데이터 없음</li>
                                    <?php endif; ?>
                                </ol>
                            </div>
                        </div>
                        <div class="stat-card as-card">
                            <h4>TOP3 수리 자재</h4>
                            <div class="top-list">
                                <ol>
                                    <?php foreach ($top_parts as $idx => $part): ?>
                                        <li><?php echo htmlspecialchars($part['cost_name']); ?> <span
                                                style="font-weight: bold; color: #2563eb;"><?php echo $part['total_qty']; ?>개</span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($top_parts) === 0): ?>
                                        <li>데이터 없음</li>
                                    <?php endif; ?>
                                </ol>
                            </div>
                        </div>
                        <div class="stat-card as-card">
                            <h4>AS 매출</h4>
                            <?php echo formatRevenue($stats['as']['total_as_cost'] ?? 0); ?>
                        </div>
                    </div>
                </div>

                <!-- 판매 통계 -->
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #10b981; margin-bottom: 15px; font-size: 14px;">🔋 판매 통계</h3>
                    <div class="stats-grid">
                        <div class="stat-card sales-card">
                            <h4>판매 완료</h4>
                            <div class="number"><?php echo number_format($stats['sales']['sales_completed'] ?? 0); ?></div>
                            <div class="label">건</div>
                        </div>
                        <div class="stat-card sales-card">
                            <h4>TOP3 판매 자재</h4>
                            <div class="top-list">
                                <ol>
                                    <?php foreach ($top_sale_parts as $idx => $part): ?>
                                        <li><?php echo htmlspecialchars($part['cost_name']); ?> <span
                                                style="font-weight: bold; color: #059669;"><?php echo $part['total_qty']; ?>개</span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($top_sale_parts) === 0): ?>
                                        <li>데이터 없음</li>
                                    <?php endif; ?>
                                </ol>
                            </div>
                        </div>
                        <div class="stat-card sales-card">
                            <h4>판매 매출</h4>
                            <?php echo formatRevenue($stats['sales']['total_sales_cost'] ?? 0); ?>
                        </div>
                    </div>
                </div>

                <!-- 월별 통계 테이블 -->
                <div class="table-section">
                    <h3>월별 AS 현황</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>월</th>
                                <th class="text-right">완료</th>
                                <th class="text-right">매출</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_as as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['month']); ?></td>
                                    <td class="text-right"><?php echo number_format($row['completed']); ?></td>
                                    <td class="text-right"><?php echo number_format(intval($row['total_cost'])); ?> 원</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-section">
                    <h3>월별 판매 현황</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>월</th>
                                <th class="text-right">완료</th>
                                <th class="text-right">매출</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_sales as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['month']); ?></td>
                                    <td class="text-right"><?php echo number_format($row['completed']); ?></td>
                                    <td class="text-right"><?php echo number_format(intval($row['total_cost'])); ?> 원</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($current_tab === 'monthly_report'): ?>
                <!-- 월간 리포트 탭 -->
                <h3 style="color: #667eea; margin-bottom: 20px; font-size: 16px;">📅 월간 리포트</h3>
                <p style="color: #666; margin-bottom: 20px; font-size: 14px;">위의 연도/월을 선택하고 "📥 월간 종합 리포트" 버튼을 클릭하여 리포트를
                    다운로드하세요.</p>

                <!-- 월간 종합 매출 결과 테이블 -->
                <div class="table-section">
                    <h3>월간 종합 매출 결과</h3>
                    <p style="color: #999; font-size: 12px; margin-bottom: 10px;"><?php echo $report_year; ?>년
                        <?php echo intval($report_month); ?>월
                    </p>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>분류</th>
                                <th class="text-right">수리건수</th>
                                <th class="text-right">수리비</th>
                                <th class="text-right">소모품 판매</th>
                                <th class="text-right">소모품 판매비</th>
                                <th class="text-right">합계(수리비 + 소모품)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>본사</td>
                                <td class="text-right"><?php echo number_format($monthly_report_data['as_count']); ?></td>
                                <td class="text-right"><?php echo number_format($monthly_report_data['as_total_cost']); ?> 원
                                </td>
                                <td class="text-right"><?php echo number_format($monthly_report_data['sell_count']); ?></td>
                                <td class="text-right"><?php echo number_format($monthly_report_data['sell_total_cost']); ?>
                                    원</td>
                                <td class="text-right" style="font-weight: bold;">
                                    <?php echo number_format($monthly_report_data['as_total_cost'] + $monthly_report_data['sell_total_cost']); ?>
                                    원
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($current_tab === 'as_analysis'): ?>
                <!-- AS 분석 탭 -->
                <h3 style="color: #667eea; margin-bottom: 20px; font-size: 16px;">🔧 AS 상세 분석</h3>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h4>전체 AS</h4>
                        <div class="number"><?php echo number_format($stats['as']['as_completed'] ?? 0); ?></div>
                        <div class="label">건</div>
                    </div>
                    <div class="stat-card">
                        <h4>총 AS 매출</h4>
                        <?php echo formatRevenue($stats['as']['total_as_cost'] ?? 0); ?>
                    </div>
                </div>

                <!-- AS 매출 그래프 -->
                <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- 연도별 AS 매출 그래프 -->
                    <div
                        style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h4 style="color: #333; margin-bottom: 15px; font-size: 14px;">📈 연도별 총 AS 매출 (2012년~)</h4>
                        <canvas id="asYearlyChart" style="max-height: 300px;"></canvas>
                    </div>

                    <!-- 올해 월별 AS 매출 그래프 -->
                    <div
                        style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h4 style="color: #333; margin-bottom: 15px; font-size: 14px;">📊 <?php echo date('Y'); ?>년 월별 AS 매출
                        </h4>
                        <canvas id="asMonthlyChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>

                <div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- TOP10 수리 모델 -->
                    <div>
                        <h4 style="color: #333; margin-bottom: 15px; font-size: 14px;">🔧 TOP10 수리 모델</h4>
                        <table style="width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <thead>
                                <tr style="background: #f59e0b; color: white;">
                                    <th style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">No</th>
                                    <th style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">모델명</th>
                                    <th style="padding: 12px; text-align: center;">수리건수</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_repair_models)): ?>
                                    <?php $rank = 1; ?>
                                    <?php foreach ($top_repair_models as $model): ?>
                                        <tr style="border-bottom: 1px solid #ddd;">
                                            <td style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">
                                                <?php echo $rank; ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">
                                                <?php echo htmlspecialchars($model['model_name'] ?? '-'); ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center;">
                                                <?php echo number_format($model['repair_count']); ?>건
                                            </td>
                                        </tr>
                                        <?php $rank++; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="padding: 20px; text-align: center; color: #999;">조회된 수리 모델이 없습니다.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- TOP10 교체 자재 -->
                    <div>
                        <h4 style="color: #333; margin-bottom: 15px; font-size: 14px;">🔩 TOP10 교체 자재</h4>
                        <table style="width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <thead>
                                <tr style="background: #10b981; color: white;">
                                    <th style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">No</th>
                                    <th style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">자재명</th>
                                    <th style="padding: 12px; text-align: center;">사용량</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_replacement_parts)): ?>
                                    <?php $rank = 1; ?>
                                    <?php foreach ($top_replacement_parts as $part): ?>
                                        <tr style="border-bottom: 1px solid #ddd;">
                                            <td style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">
                                                <?php echo $rank; ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">
                                                <?php echo htmlspecialchars($part['part_name'] ?? '-'); ?>
                                            </td>
                                            <td style="padding: 12px; text-align: center;">
                                                <?php echo number_format($part['total_quantity']); ?>개
                                            </td>
                                        </tr>
                                        <?php $rank++; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="padding: 20px; text-align: center; color: #999;">조회된 교체 자재가 없습니다.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($current_tab === 'sales_analysis'): ?>
                <!-- 판매 분석 탭 -->
                <h3 style="color: #667eea; margin-bottom: 20px; font-size: 16px;">🔋 판매 분석</h3>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h4>전체 판매</h4>
                        <div class="number"><?php echo number_format($stats['sales']['sales_completed'] ?? 0); ?></div>
                        <div class="label">건</div>
                    </div>
                    <div class="stat-card">
                        <h4>총 판매 매출</h4>
                        <?php echo formatRevenue($stats['sales']['total_sales_cost'] ?? 0); ?>
                    </div>
                </div>

                <!-- 판매액 그래프 -->
                <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- 연도별 판매액 그래프 -->
                    <div
                        style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h4 style="color: #333; margin-bottom: 15px; font-size: 14px;">📈 연도별 소모품 매출 (2012년~)</h4>
                        <canvas id="yearlyChart" style="max-height: 300px;"></canvas>
                    </div>

                    <!-- 올해 월별 판매액 그래프 -->
                    <div
                        style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h4 style="color: #333; margin-bottom: 15px; font-size: 14px;">📊 <?php echo date('Y'); ?>년 소모품 매출
                        </h4>
                        <canvas id="monthlyChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>

                <!-- TOP10 판매 자재 테이블 -->
                <div style="margin-top: 30px;">
                    <h4 style="color: #333; margin-bottom: 15px; font-size: 14px;">📦 TOP10 판매 자재</h4>
                    <table
                        style="width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <thead>
                            <tr style="background: #667eea; color: white;">
                                <th style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">No</th>
                                <th style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">자재명</th>
                                <th style="padding: 12px; text-align: center;">판매수량</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_sale_parts)): ?>
                                <?php $rank = 1; ?>
                                <?php foreach ($top_sale_parts as $part): ?>
                                    <tr style="border-bottom: 1px solid #ddd;">
                                        <td style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">
                                            <?php echo $rank; ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center; border-right: 1px solid #ddd;">
                                            <?php echo htmlspecialchars($part['cost_name']); ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php echo number_format($part['total_qty']); ?>개
                                        </td>
                                    </tr>
                                    <?php $rank++; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="padding: 20px; text-align: center; color: #999;">조회된 판매 자재가 없습니다.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        // 판매분석 탭 그래프 렌더링
        <?php if ($current_tab === 'sales_analysis'): ?>

            // 연도별 판매액 데이터
            var yearlyLabels = [<?php echo implode(',', array_map(function ($item) {
                return "'" . $item['year'] . "'";
            }, $yearly_sales)); ?>];
            var yearlyCosts = [<?php echo implode(',', array_map(function ($item) {
                return intval($item['total_cost']);
            }, $yearly_sales)); ?>];

            // 연도별 그래프
            if (document.getElementById('yearlyChart')) {
                var yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
                var yearlyChart = new Chart(yearlyCtx, {
                    type: 'line',
                    data: {
                        labels: yearlyLabels,
                        datasets: [{
                            label: '연 소모품 매출',
                            data: yearlyCosts,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '(만원)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return Math.round(value / 10000).toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 올해 월별 판매액 데이터
            var monthlyLabels = [<?php
            for ($m = 1; $m <= 12; $m++) {
                echo "'" . $m . "'";
                if ($m < 12)
                    echo ",";
            }
            ?>];

            // 월별 데이터 직접 생성 (모든 월을 0으로 초기화)
            var monthlyCosts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

            // PHP에서 생성한 월별 데이터
            <?php
            $monthly_map = array();
            foreach ($current_year_monthly_sales as $item) {
                $month = intval($item['month']);
                $cost = intval($item['total_cost']);
                echo "monthlyCosts[" . ($month - 1) . "] = " . $cost . ";\n";
            }
            ?>

            // 올해 월별 그래프
            if (document.getElementById('monthlyChart')) {
                var monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
                var monthlyChart = new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: monthlyLabels,
                        datasets: [{
                            label: '월 소모품 매출',
                            data: monthlyCosts,
                            borderColor: '#06b6d4',
                            backgroundColor: 'rgba(6, 182, 212, 0.1)',
                            fill: true,
                            tension: 0,
                            pointBackgroundColor: '#06b6d4',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '(만원)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return Math.round(value / 10000).toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

        <?php elseif ($current_tab === 'as_analysis'): ?>

            // 연도별 AS 비용 데이터
            var asYearlyLabels = [<?php echo implode(',', array_map(function ($item) {
                return "'" . $item['year'] . "'";
            }, $yearly_as)); ?>];
            var asYearlyCosts = [<?php echo implode(',', array_map(function ($item) {
                return intval($item['total_cost']);
            }, $yearly_as)); ?>];

            // 연도별 AS 그래프
            if (document.getElementById('asYearlyChart')) {
                var asYearlyCtx = document.getElementById('asYearlyChart').getContext('2d');
                var asYearlyChart = new Chart(asYearlyCtx, {
                    type: 'line',
                    data: {
                        labels: asYearlyLabels,
                        datasets: [{
                            label: '연 AS 매출',
                            data: asYearlyCosts,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            fill: true,
                            tension: 0,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '(만원)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return Math.round(value / 10000).toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 올해 월별 AS 매출 데이터
            var asMonthlyLabels = [<?php
            for ($m = 1; $m <= 12; $m++) {
                echo "'" . $m . "'";
                if ($m < 12)
                    echo ",";
            }
            ?>];

            // 월별 데이터 직접 생성 (모든 월을 0으로 초기화)
            var asMonthyCosts = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

            // PHP에서 생성한 월별 데이터
            <?php
            foreach ($current_year_monthly_as as $item) {
                $month = intval($item['month']);
                $cost = intval($item['total_cost']);
                echo "asMonthyCosts[" . ($month - 1) . "] = " . $cost . ";\n";
            }
            ?>

            // 올해 월별 AS 그래프
            if (document.getElementById('asMonthlyChart')) {
                var asMonthlyCtx = document.getElementById('asMonthlyChart').getContext('2d');
                var asMonthlyChart = new Chart(asMonthlyCtx, {
                    type: 'line',
                    data: {
                        labels: asMonthlyLabels,
                        datasets: [{
                            label: '월 AS 매출',
                            data: asMonthyCosts,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '(만원)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return Math.round(value / 10000).toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

        <?php endif; ?>
    </script>

</body>

</html>
<?php mysql_close($connect); ?>