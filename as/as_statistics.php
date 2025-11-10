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
if (!$connect) {
    die('MySQL 연결 실패: ' . mysql_error());
}
if (!mysql_select_db('mic4u', $connect)) {
    die('데이터베이스 선택 실패: ' . mysql_error());
}

$user_name = $_SESSION['member_id'];
$current_page = 'statistics';

// 탭 선택
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overall';
$current_tab = in_array($tab, ['overall', 'as', 'parts']) ? $tab : 'overall';

// 기간 검색
$search_start_date = isset($_GET['search_start_date']) ? $_GET['search_start_date'] : '';
$search_end_date = isset($_GET['search_end_date']) ? $_GET['search_end_date'] : '';

// 통계 데이터 초기화
$stats_data = array();

// 기간 필터 WHERE 조건
$date_condition = '';
if (!empty($search_start_date) && !empty($search_end_date)) {
    $start_date = mysql_real_escape_string($search_start_date);
    $end_date = mysql_real_escape_string($search_end_date);
    $date_condition = "AND DATE(a.s13_as_out_date) BETWEEN '$start_date' AND '$end_date'";
} elseif (!empty($search_start_date)) {
    $start_date = mysql_real_escape_string($search_start_date);
    $date_condition = "AND DATE(a.s13_as_out_date) >= '$start_date'";
} elseif (!empty($search_end_date)) {
    $end_date = mysql_real_escape_string($search_end_date);
    $date_condition = "AND DATE(a.s13_as_out_date) <= '$end_date'";
}

// 탭별 통계 데이터 조회
switch ($current_tab) {
    case 'overall':
        // 종합 통계: AS 완료 + 자재 판매 고객별 통계
        $as_query = "SELECT
                        a.s13_meid,
                        a.ex_company,
                        COUNT(DISTINCT a.s13_asid) as as_count,
                        COALESCE(SUM(c.s18_quantity), 0) as parts_count
                    FROM step13_as a
                    LEFT JOIN step14_as_item b ON a.s13_asid = b.s14_asid
                    LEFT JOIN step18_as_cure_cart c ON b.s14_aiid = c.s18_aiid
                    WHERE a.s13_as_level = '5' $date_condition
                    GROUP BY a.s13_meid, a.ex_company
                    ORDER BY as_count DESC";

        $result = mysql_query($as_query);
        if (!$result) {
            die('AS 쿼리 실패: ' . mysql_error() . '<br>Query: ' . htmlspecialchars($as_query));
        }
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $stats_data[] = array(
                    'company' => $row['ex_company'],
                    'count' => (int)$row['as_count'],
                    'type' => 'AS'
                );
            }
        }

        $parts_query = "SELECT
                            a.s20_meid,
                            a.ex_company,
                            COUNT(DISTINCT a.s20_sellid) as sell_count
                        FROM step20_sell a
                        WHERE a.s20_sell_level = '2'
                        GROUP BY a.s20_meid, a.ex_company
                        ORDER BY sell_count DESC";

        $result = mysql_query($parts_query);
        if (!$result) {
            die('자재 판매 쿼리 실패: ' . mysql_error() . '<br>Query: ' . htmlspecialchars($parts_query));
        }
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                // 기존 회사가 있는지 확인
                $found = false;
                foreach ($stats_data as &$item) {
                    if ($item['company'] === $row['ex_company']) {
                        $item['parts_count'] = (int)$row['sell_count'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $stats_data[] = array(
                        'company' => $row['ex_company'],
                        'count' => 0,
                        'parts_count' => (int)$row['sell_count'],
                        'type' => 'PARTS'
                    );
                }
            }
        }

        // 전체 sort
        usort($stats_data, function($a, $b) {
            $a_total = ($a['count'] ?? 0) + ($a['parts_count'] ?? 0);
            $b_total = ($b['count'] ?? 0) + ($b['parts_count'] ?? 0);
            return $b_total - $a_total;
        });
        break;

    case 'as':
        // AS 통계
        $as_query = "SELECT
                        a.s13_meid,
                        a.ex_company,
                        COUNT(DISTINCT a.s13_asid) as as_count
                    FROM step13_as a
                    WHERE a.s13_as_level = '5' $date_condition
                    GROUP BY a.s13_meid, a.ex_company
                    ORDER BY as_count DESC";

        $result = mysql_query($as_query);
        if (!$result) {
            die('AS 쿼리 실패: ' . mysql_error() . '<br>Query: ' . htmlspecialchars($as_query));
        }
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $stats_data[] = array(
                    'company' => $row['ex_company'],
                    'count' => (int)$row['as_count']
                );
            }
        }
        break;

    case 'parts':
        // 자재 판매 통계
        $parts_query = "SELECT
                            a.s20_meid,
                            a.ex_company,
                            COUNT(DISTINCT a.s20_sellid) as sell_count
                        FROM step20_sell a
                        WHERE a.s20_sell_level = '2'
                        GROUP BY a.s20_meid, a.ex_company
                        ORDER BY sell_count DESC";

        $result = mysql_query($parts_query);
        if (!$result) {
            die('자재 판매 쿼리 실패: ' . mysql_error() . '<br>Query: ' . htmlspecialchars($parts_query));
        }
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $stats_data[] = array(
                    'company' => $row['ex_company'],
                    'count' => (int)$row['sell_count']
                );
            }
        }
        break;
}
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

        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 24px;
        }

        .header-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .user-info {
            font-size: 14px;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border: 1px solid white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        /* Navigation */
        .nav-bar {
            background: white;
            padding: 0;
            display: flex;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .nav-item {
            padding: 15px 25px;
            text-decoration: none;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }

        .nav-item:hover {
            background: #f5f6fa;
            color: #333;
        }

        .nav-item.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        /* Main */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .tab-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }

        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            color: #333;
            background: #f5f6fa;
        }

        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }

        .search-box button {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }

        .search-box button:hover {
            background: #5568d3;
        }

        .btn-reset {
            padding: 8px 16px;
            background: #ddd;
            color: #333;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }

        .btn-reset:hover {
            background: #ccc;
        }

        .stats-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .stats-table thead {
            background: #f5f5f5;
            border-bottom: 2px solid #ddd;
        }

        .stats-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        .stats-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .stats-table tbody tr:hover {
            background: #f9f9f9;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .info-text {
            margin-bottom: 15px;
            padding: 10px;
            background: #f0f3ff;
            border-left: 3px solid #667eea;
            border-radius: 4px;
            font-size: 13px;
            color: #333;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>통계/분석 - AS 시스템</h1>
        <div class="header-right">
            <span class="user-info"><?php echo htmlspecialchars($user_name); ?> 님</span>
            <a href="logout.php" class="logout-btn">로그아웃</a>
        </div>
    </div>

    <!-- Navigation -->
    <div class="nav-bar">
        <a href="dashboard.php" class="nav-item">대시보드</a>
        <a href="as_requests.php" class="nav-item">AS 작업</a>
        <a href="orders.php" class="nav-item">자재 판매</a>
        <a href="parts.php" class="nav-item">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
        <a href="as_statistics.php" class="nav-item <?php echo $current_page === 'statistics' ? 'active' : ''; ?>">통계/분석</a>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Tab Bar -->
        <div class="tab-bar">
            <button class="tab-btn <?php echo $current_tab === 'overall' ? 'active' : ''; ?>"
                onclick="location.href='as_statistics.php?tab=overall'">
                종합 통계
            </button>
            <button class="tab-btn <?php echo $current_tab === 'as' ? 'active' : ''; ?>"
                onclick="location.href='as_statistics.php?tab=as'">
                AS 통계
            </button>
            <button class="tab-btn <?php echo $current_tab === 'parts' ? 'active' : ''; ?>"
                onclick="location.href='as_statistics.php?tab=parts'">
                자재 판매 통계
            </button>
        </div>

        <!-- Search Form -->
        <form method="GET" class="search-box" id="search-form">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
            <input type="date" name="search_start_date" placeholder="시작 날짜"
                value="<?php echo htmlspecialchars($search_start_date); ?>">
            <span style="color: #999;">~</span>
            <input type="date" name="search_end_date" placeholder="종료 날짜"
                value="<?php echo htmlspecialchars($search_end_date); ?>">
            <button type="submit">검색</button>
            <a href="as_statistics.php?tab=<?php echo htmlspecialchars($current_tab); ?>" class="btn-reset">초기화</a>
        </form>

        <!-- Stats Content -->
        <div class="stats-container">
            <div class="info-text">
                총 <?php echo count($stats_data); ?>개의 고객 데이터
            </div>

            <?php if (empty($stats_data)): ?>
                <div class="empty-state">
                    <p>데이터가 없습니다.</p>
                </div>
            <?php else: ?>
                <!-- Chart -->
                <div class="chart-container">
                    <canvas id="stats-chart"></canvas>
                </div>

                <!-- Table -->
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>고객명</th>
                            <?php if ($current_tab === 'overall'): ?>
                                <th style="text-align: right;">AS 건수</th>
                                <th style="text-align: right;">자재판매 건수</th>
                            <?php elseif ($current_tab === 'as'): ?>
                                <th style="text-align: right;">AS 건수</th>
                            <?php else: ?>
                                <th style="text-align: right;">자재판매 건수</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats_data as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['company']); ?></td>
                                <?php if ($current_tab === 'overall'): ?>
                                    <td style="text-align: right;"><?php echo ($item['count'] ?? 0); ?>건</td>
                                    <td style="text-align: right;"><?php echo ($item['parts_count'] ?? 0); ?>건</td>
                                <?php elseif ($current_tab === 'as'): ?>
                                    <td style="text-align: right;"><?php echo $item['count']; ?>건</td>
                                <?php else: ?>
                                    <td style="text-align: right;"><?php echo $item['count']; ?>건</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        const statsData = <?php echo json_encode($stats_data); ?>;
        const currentTab = '<?php echo $current_tab; ?>';

        // Chart.js 설정
        const ctx = document.getElementById('stats-chart')?.getContext('2d');
        let chart = null;

        function updateChart() {
            if (!ctx) return;

            // 라벨과 데이터 준비
            const labels = statsData.map(item => item.company || '미등록');

            let datasets = [];

            if (currentTab === 'overall') {
                // 종합 통계: 2개 데이터셋
                const asData = statsData.map(item => item.count || 0);
                const partsData = statsData.map(item => item.parts_count || 0);

                datasets = [
                    {
                        label: 'AS 건수',
                        data: asData,
                        backgroundColor: '#3498db',
                        borderColor: '#3498db',
                        borderWidth: 1
                    },
                    {
                        label: '자재판매 건수',
                        data: partsData,
                        backgroundColor: '#2ecc71',
                        borderColor: '#2ecc71',
                        borderWidth: 1
                    }
                ];
            } else {
                // AS 통계 또는 자재판매 통계: 1개 데이터셋
                const data = statsData.map(item => item.count);
                const colors = [
                    '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6',
                    '#1abc9c', '#34495e', '#e67e22', '#c0392b', '#16a085'
                ];
                const backgroundColors = data.map((_, i) => colors[i % colors.length]);

                datasets = [{
                    label: currentTab === 'as' ? 'AS 건수' : '자재판매 건수',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors,
                    borderWidth: 1,
                    borderRadius: 5
                }];
            }

            // 기존 차트 파괴
            if (chart) {
                chart.destroy();
            }

            // 새 차트 생성
            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',  // 수평 막대 차트
                    plugins: {
                        legend: {
                            display: currentTab === 'overall',
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: currentTab === 'overall' ? '종합 통계' : (currentTab === 'as' ? 'AS 통계' : '자재판매 통계')
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // 초기 차트 생성
        if (statsData.length > 0) {
            updateChart();
        }
    </script>
</body>

</html>
<?php mysql_close($connect); ?>
