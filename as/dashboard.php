<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// 로그인 확인 - member_id 및 member_sid 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['member_id'];
$user_level = $_SESSION['member_level'] ?? '';
$user_name = $_SESSION['user_name'] ?? $user_id;
$current_page = 'dashboard';

// 데이터베이스 연결
require_once 'db_config.php';

// 사용자 정보 조회
$result = mysql_query("SELECT * FROM `2010_admin_member` WHERE `id` = '$user_id' LIMIT 1");
if ($result && mysql_num_rows($result) > 0) {
    $user = mysql_fetch_assoc($result);
} else {
    $user = array();
}

// 이번 달의 시작일과 종료일 계산
$today = new DateTime('now', new DateTimeZone('Asia/Seoul'));
$month_start = clone $today;
$month_start->modify('first day of this month');
$month_start->setTime(0, 0, 0);

$month_end = clone $today;
$month_end->modify('last day of this month');
$month_end->setTime(23, 59, 59);

$month_start_str = $month_start->format('Y-m-d H:i:s');
$month_end_str = $month_end->format('Y-m-d H:i:s');

// 이번달 AS 작업 완료수 (s13_as_level = '5')
$as_query = "SELECT COUNT(*) as as_completed
    FROM step13_as
    WHERE s13_as_level = '5' AND s13_as_out_date BETWEEN '$month_start_str' AND '$month_end_str'";

$as_result = @mysql_query($as_query);
$as_stats = ($as_result && is_object($as_result)) ? mysql_fetch_assoc($as_result) : array();
$as_completed = intval($as_stats['as_completed'] ?? 0);

// 이번달 자재 판매 완료수 (s20_sell_level = '2')
$sales_query = "SELECT COUNT(*) as sales_completed
    FROM step20_sell
    WHERE s20_sell_level = '2' AND s20_sell_out_date BETWEEN '$month_start_str' AND '$month_end_str'";

$sales_result = @mysql_query($sales_query);
$sales_stats = ($sales_result && is_object($sales_result)) ? mysql_fetch_assoc($sales_result) : array();
$sales_completed = intval($sales_stats['sales_completed'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>대시보드 - 디지탈컴 AS 시스템</title>
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
            white-space: nowrap;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border: 1px solid white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .welcome-box h2 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .welcome-box p {
            color: #666;
            line-height: 1.6;
        }

        .menu-grid-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* 왼쪽/오른쪽 칼럼 */
            gap: 20px;
            /* 통계 카드와 동일 간격이면 20px 유지 */
            margin-top: 30px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            /* 각 그룹은 2열 */
            /* 4열로 고정 */
            gap: 20px;
            margin-top: 30px;
            box-sizing: border-box;

        }

        .menu-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            border-left: 4px solid #667eea;

        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .menu-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .menu-card p {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-card h4 {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }

        .menu-card.featured-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-left: 4px solid #764ba2;
        }

        .menu-card.featured-card h3 {
            color: white;
        }

        .menu-card.featured-card p {
            color: rgba(255, 255, 255, 0.9);
        }

        .menu-card.featured-card:hover {
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
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
        <a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">대시보드</a>
        <a href="./as_task/as_requests.php" class="nav-item">AS 작업</a>
        <a href="./orders/orders.php" class="nav-item">자재 판매</a>
        <a href="./parts/parts.php" class="nav-item">자재 관리</a>
        <a href="./customers/members.php" class="nav-item">고객 관리</a>
        <a href="./products/products.php" class="nav-item">제품 관리</a>
        <a href="./stat/statistics.php" class="nav-item">통계/분석</a>
    </div>

    <div class="container">
        <div class="welcome-box">
            <h2>환영합니다!!</h2>
            <p><?php echo htmlspecialchars($user_name); ?>님의 계정으로 로그인하셨습니다. 아래 메뉴를 통해 AS 시스템을 관리하실 수 있습니다.</p>
        </div>

        <h3 style="margin-bottom: 20px;">📊 관리자 메뉴</h3>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>이번달 AS 작업 완료수</h4>
                <div class="number">
                    <?php echo $as_completed; ?>
                </div>
            </div>
            <div class="stat-card">
                <h4>이번달 자재 판매 완료수</h4>
                <div class="number">
                    <?php echo $sales_completed; ?>
                </div>
            </div>
        </div>

        <div class="menu-grid-wrapper">
            <!-- 왼쪽 그룹 -->
            <div class="menu-grid left-grid">
                <a href="./as_task/as_requests.php" class="menu-card featured-card">
                    <h3>🔧 AS 작업</h3>
                    <p>AS 요청 및 처리 현황을 관리합니다.</p>
                </a>

                <a href="./orders/orders.php" class="menu-card featured-card">
                    <h3>🔋 자재 판매</h3>
                    <p>자재 판매 현황을 조회 및 관리합니다.</p>
                </a>

                <a href="./stat/statistics.php" class="menu-card featured-card">
                    <h3>📊 통계/분석</h3>
                    <p>AS 및 판매 통계를 분석합니다.</p>
                </a>
            </div>

            <!-- 오른쪽 그룹 -->
            <div class="menu-grid right-grid">
                <a href="./parts/parts.php" class="menu-card">
                    <h3>📦 자재 관리</h3>
                    <p>부품 정보를 등록 및 수정합니다.</p>
                </a>

                <a href="./products/products.php" class="menu-card">
                    <h3>🎤 제품 관리</h3>
                    <p>AS 제품 정보를 등록 및 수정합니다.</p>
                </a>

                <a href="./customers/members.php" class="menu-card">
                    <h3>👥 고객 관리</h3>
                    <p>고객 정보를 조회 및 관리합니다.</p>
                </a>
            </div>
        </div>

    </div>
</body>

</html>
<?php
mysql_close($connect);
?>