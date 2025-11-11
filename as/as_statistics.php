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
$current_tab = in_array($tab, ['overview', 'as_analysis', 'sales_analysis']) ? $tab : 'overview';

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
    // AS 통계
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

    // 자재 판매 통계
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

// 월별 AS 통계 (완료 기준)
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

// TOP3 판매 자재 (step20_sell에서 판매 완료 기준, step21_sell_cart에서 수량 합산)
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

// 매출 포맷팅 함수 (모두 원으로 표시)
function formatRevenue($cost)
{
    $cost = intval($cost);
    return '<div class="revenue-amount">' . number_format($cost) . '</div><div class="revenue-unit">원</div>';
}

// 통계 데이터 조회
$stats = getStatistics($connect, $start_date, $end_date);
$monthly_as = getMonthlyASStats($connect);
$monthly_sales = getMonthlySalesStats($connect);
$top_products = getTopRepairProducts($connect, $start_date, $end_date);
$top_parts = getTopRepairParts($connect, $start_date, $end_date);
$top_sale_parts = getTopSaleParts($connect, $start_date, $end_date);
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
                <button class="tab-btn <?php echo $current_tab === 'as_analysis' ? 'active' : ''; ?>"
                    onclick="location.href='as_statistics.php?tab=as_analysis'">AS 분석</button>
                <button class="tab-btn <?php echo $current_tab === 'sales_analysis' ? 'active' : ''; ?>"
                    onclick="location.href='as_statistics.php?tab=sales_analysis'">판매 분석</button>
            </div>

            <!-- 기간 필터 -->
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
                    <input type="hidden" id="range-input-stat" name="range" value="">
                    <button type="submit">검색</button>
                    <button type="button" onclick="downloadReport('export_xlsx_as_report.php')"
                        style="margin-left: 10px; padding: 8px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block;">📥 AS 리포트</button>
                    <button type="button" onclick="downloadReport('export_xlsx_sales_report.php')"
                        style="margin-left: 5px; padding: 8px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block;">📥 판매 리포트</button>
                </div>
            </form>

            <script>
                // 리포트 다운로드 함수 (브라우저 다운로드 다이얼로그 표시)
                function downloadReport(filename) {
                    const startDate = document.querySelector('input[name="start_date"]').value;
                    const endDate = document.querySelector('input[name="end_date"]').value;
                    const range = document.getElementById('range-input-stat').value || 'month';

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
                document.addEventListener('DOMContentLoaded', function() {
                    const dateForm = document.querySelector('.date-filter');
                    if (dateForm) {
                        dateForm.addEventListener('submit', function(e) {
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

            <?php elseif ($current_tab === 'as_analysis'): ?>
                <!-- AS 분석 탭 -->
                <h3 style="color: #667eea; margin-bottom: 20px; font-size: 16px;">🔧 AS 상세 분석</h3>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h4>전체 AS</h4>
                        <div class="number"><?php echo number_format($stats['as']['total_as'] ?? 0); ?></div>
                    </div>
                    <div class="stat-card">
                        <h4>완료율</h4>
                        <div class="number">
                            <?php
                            $total = intval($stats['as']['total_as'] ?? 0);
                            $completed = intval($stats['as']['as_completed'] ?? 0);
                            echo $total > 0 ? round(($completed / $total) * 100) : 0;
                            ?>%
                        </div>
                    </div>
                    <div class="stat-card">
                        <h4>평균 비용</h4>
                        <div class="number">
                            <?php
                            $total = intval($stats['as']['total_as'] ?? 0);
                            $cost = intval($stats['as']['total_as_cost'] ?? 0);
                            echo $total > 0 ? number_format(intval($cost / $total)) : 0;
                            ?>
                        </div>
                        <div class="label">원</div>
                    </div>
                </div>

                <div class="table-section">
                    <h3>고객별 AS 현황 (상위 10)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>고객명</th>
                                <th class="text-right">전체 AS</th>
                                <th class="text-right">완료</th>
                                <th class="text-right">총 비용</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customer_as)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #999; padding: 30px;">데이터가 없습니다.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customer_as as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['ex_company'] ?? '-'); ?></td>
                                        <td class="text-right"><?php echo number_format($row['total']); ?></td>
                                        <td class="text-right"><?php echo number_format($row['completed']); ?></td>
                                        <td class="text-right"><?php echo number_format(intval($row['total_cost'])); ?> 원</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($current_tab === 'sales_analysis'): ?>
                <!-- 판매 분석 탭 -->
                <h3 style="color: #667eea; margin-bottom: 20px; font-size: 16px;">🔋 판매 분석</h3>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h4>전체 판매</h4>
                        <div class="number"><?php echo number_format($stats['sales']['total_sales'] ?? 0); ?></div>
                    </div>
                    <div class="stat-card">
                        <h4>완료율</h4>
                        <div class="number">
                            <?php
                            $total = intval($stats['sales']['total_sales'] ?? 0);
                            $completed = intval($stats['sales']['sales_completed'] ?? 0);
                            echo $total > 0 ? round(($completed / $total) * 100) : 0;
                            ?>%
                        </div>
                    </div>
                    <div class="stat-card">
                        <h4>평균 판매액</h4>
                        <div class="number">
                            <?php
                            $total = intval($stats['sales']['total_sales'] ?? 0);
                            $cost = intval($stats['sales']['total_sales_cost'] ?? 0);
                            echo $total > 0 ? number_format(intval($cost / $total)) : 0;
                            ?>
                        </div>
                        <div class="label">원</div>
                    </div>
                </div>

                <div class="table-section">
                    <h3>월별 판매 추이</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>월</th>
                                <th class="text-right">전체</th>
                                <th class="text-right">완료</th>
                                <th class="text-right">총 매출</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_sales as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['month']); ?></td>
                                    <td class="text-right"><?php echo number_format($row['total']); ?></td>
                                    <td class="text-right"><?php echo number_format($row['completed']); ?></td>
                                    <td class="text-right"><?php echo number_format(intval($row['total_cost'])); ?> 원</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>
<?php mysql_close($connect); ?>