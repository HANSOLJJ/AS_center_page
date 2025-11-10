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

// 기간 설정 (기본값: 오늘)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 통계 데이터 조회 함수
function getStatistics($connect, $start_date, $end_date)
{
    // AS 통계
    $as_query = "SELECT
        COUNT(*) as total_as,
        SUM(CASE WHEN s13_as_level NOT IN ('2','3','4','5') THEN 1 ELSE 0 END) as as_request,
        SUM(CASE WHEN s13_as_level IN ('2','3','4') THEN 1 ELSE 0 END) as as_working,
        SUM(CASE WHEN s13_as_level = '5' THEN 1 ELSE 0 END) as as_completed,
        SUM(COALESCE(ex_total_cost, 0)) as total_as_cost
        FROM step13_as
        WHERE DATE(s13_as_in_date) BETWEEN '$start_date' AND '$end_date'";

    $as_result = mysql_query($as_query);
    $as_stats = mysql_fetch_assoc($as_result) ?? array();

    // 자재 판매 통계
    $sales_query = "SELECT
        COUNT(*) as total_sales,
        SUM(CASE WHEN s20_sell_level = '1' THEN 1 ELSE 0 END) as sales_request,
        SUM(CASE WHEN s20_sell_level = '2' THEN 1 ELSE 0 END) as sales_completed,
        SUM(COALESCE(s20_total_cost, 0)) as total_sales_cost
        FROM step20_sell
        WHERE DATE(s20_sell_in_date) BETWEEN '$start_date' AND '$end_date'";

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

// TOP3 수리 제품 (AS)
function getTopRepairProducts($connect, $start_date, $end_date)
{
    $query = "SELECT
        s8_product_name,
        COUNT(*) as count
        FROM step13_as
        WHERE s13_as_level = '5' AND DATE(s13_as_out_date) BETWEEN '$start_date' AND '$end_date'
        GROUP BY s8_product_name
        ORDER BY count DESC
        LIMIT 3";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// TOP3 수리 자재 (AS)
function getTopRepairParts($connect, $start_date, $end_date)
{
    $query = "SELECT
        s14_as_item_title,
        COUNT(*) as count
        FROM step14_as_item
        WHERE DATE(s14_as_item_in_date) BETWEEN '$start_date' AND '$end_date'
        GROUP BY s14_as_item_title
        ORDER BY count DESC
        LIMIT 3";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// TOP3 판매 자재 (판매)
function getTopSaleParts($connect, $start_date, $end_date)
{
    $query = "SELECT
        s5_parts_name,
        COUNT(*) as count
        FROM step20_sell
        WHERE s20_sell_level = '2' AND DATE(s20_sell_out_date) BETWEEN '$start_date' AND '$end_date'
        GROUP BY s5_parts_name
        ORDER BY count DESC
        LIMIT 3";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
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
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .date-filter input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .date-filter button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }

        .date-filter button:hover {
            background: #5568d3;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            font-size: 13px;
        }

        .top-list ol {
            margin: 0;
            padding-left: 20px;
        }

        .top-list li {
            padding: 5px 0;
            color: #666;
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
        <a href="as_statistics.php" class="nav-item <?php echo $current_page === 'as_statistics' ? 'active' : ''; ?>">통계/분석</a>
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
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <span>~</span>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <button type="submit">조회</button>
            </form>

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
                                        <li><?php echo htmlspecialchars($product['s8_product_name']); ?> (<?php echo $product['count']; ?>)</li>
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
                                        <li><?php echo htmlspecialchars($part['s14_as_item_title']); ?> (<?php echo $part['count']; ?>)</li>
                                    <?php endforeach; ?>
                                    <?php if (count($top_parts) === 0): ?>
                                        <li>데이터 없음</li>
                                    <?php endif; ?>
                                </ol>
                            </div>
                        </div>
                        <div class="stat-card as-card">
                            <h4>AS 매출</h4>
                            <div class="number"><?php echo number_format(intval($stats['as']['total_as_cost'] ?? 0) / 10000); ?></div>
                            <div class="label">만원</div>
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
                                        <li><?php echo htmlspecialchars($part['s5_parts_name']); ?> (<?php echo $part['count']; ?>)</li>
                                    <?php endforeach; ?>
                                    <?php if (count($top_sale_parts) === 0): ?>
                                        <li>데이터 없음</li>
                                    <?php endif; ?>
                                </ol>
                            </div>
                        </div>
                        <div class="stat-card sales-card">
                            <h4>판매 매출</h4>
                            <div class="number"><?php echo number_format(intval($stats['sales']['total_sales_cost'] ?? 0) / 10000); ?></div>
                            <div class="label">만원</div>
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
