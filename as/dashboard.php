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

// MySQL 호환성 레이어 로드
require_once 'mysql_compat.php';

// 데이터베이스 연결
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

// 사용자 정보 조회
$result = mysql_query("SELECT * FROM `2010_admin_member` WHERE `id` = '$user_id' LIMIT 1");
if ($result && mysql_num_rows($result) > 0) {
    $user = mysql_fetch_assoc($result);
} else {
    $user = array();
}

// 이번 주의 시작일과 종료일 계산
$today = new DateTime('now', new DateTimeZone('Asia/Seoul'));
$week_start = clone $today;
$week_start->modify('Monday this week');
$week_start->setTime(0, 0, 0);

$week_end = clone $today;
$week_end->modify('Sunday this week');
$week_end->setTime(23, 59, 59);

$week_start_str = $week_start->format('Y-m-d H:i:s');
$week_end_str = $week_end->format('Y-m-d H:i:s');

// 금주 AS 작업 통계
$as_query = "SELECT
    SUM(CASE WHEN s13_as_level = '5' THEN 1 ELSE 0 END) as as_completed,
    COUNT(*) as as_total
    FROM step13_as
    WHERE s13_as_in_date BETWEEN '$week_start_str' AND '$week_end_str'";

$as_result = @mysql_query($as_query);
$as_stats = ($as_result && is_object($as_result)) ? mysql_fetch_assoc($as_result) : array();
$as_completed = intval($as_stats['as_completed'] ?? 0);
$as_total = intval($as_stats['as_total'] ?? 0);
$as_rate = $as_total > 0 ? round(($as_completed / $as_total) * 100) : 0;

// 금주 자재 판매 통계
$sales_query = "SELECT
    SUM(CASE WHEN s20_sell_level = '2' THEN 1 ELSE 0 END) as sales_completed,
    COUNT(*) as sales_total
    FROM step20_sell
    WHERE s20_sell_in_date BETWEEN '$week_start_str' AND '$week_end_str'";

$sales_result = @mysql_query($sales_query);
$sales_stats = ($sales_result && is_object($sales_result)) ? mysql_fetch_assoc($sales_result) : array();
$sales_completed = intval($sales_stats['sales_completed'] ?? 0);
$sales_total = intval($sales_stats['sales_total'] ?? 0);
$sales_rate = $sales_total > 0 ? round(($sales_completed / $sales_total) * 100) : 0;
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

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
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
        <a href="as_requests.php" class="nav-item">AS 작업</a>
        <a href="orders.php" class="nav-item">자재 판매</a>
        <a href="parts.php" class="nav-item">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
        <a href="as_statistics.php" class="nav-item">통계/분석</a>
    </div>

    <div class="container">
        <div class="welcome-box">
            <h2>환영합니다!</h2>
            <p><?php echo htmlspecialchars($user_name); ?>님의 계정으로 로그인하셨습니다. 아래 메뉴를 통해 AS 시스템을 관리하실 수 있습니다.</p>
        </div>

        <h3 style="margin-bottom: 20px;">📊 관리자 메뉴</h3>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>금주 진행 AS 작업수</h4>
                <div class="number" style="font-size: 24px; color: #667eea;">
                    <?php echo $as_completed; ?> / <?php echo $as_total; ?>
                </div>
                <div style="font-size: 12px; color: #999; margin-top: 8px;">
                    완료율: <strong><?php echo $as_rate; ?>%</strong>
                </div>
            </div>
            <div class="stat-card">
                <h4>금주 진행 자재 판매</h4>
                <div class="number" style="font-size: 24px; color: #667eea;">
                    <?php echo $sales_completed; ?> / <?php echo $sales_total; ?>
                </div>
                <div style="font-size: 12px; color: #999; margin-top: 8px;">
                    완료율: <strong><?php echo $sales_rate; ?>%</strong>
                </div>
            </div>
        </div>

        <div class="menu-grid">
            <a href="as_requests.php" class="menu-card featured-card">
                <h3>🔧 AS 작업</h3>
                <p>AS 요청 및 처리 현황을 관리합니다.</p>
            </a>

            <a href="orders.php" class="menu-card featured-card">
                <h3>🔋 자재 판매</h3>
                <p>자재 판매 현황을 조회 및 관리합니다.</p>
            </a>

            <a href="parts.php" class="menu-card">
                <h3>📦 자재 관리</h3>
                <p>부품 정보를 등록 및 수정합니다.</p>
            </a>

            <a href="members.php" class="menu-card">
                <h3>👥 고객 관리</h3>
                <p>고객 정보를 조회 및 관리합니다.</p>
            </a>

            <a href="products.php" class="menu-card">
                <h3>🎤 제품 관리</h3>
                <p>AS 제품 정보를 등록 및 수정합니다.</p>
            </a>

            <a href="as_statistics.php" class="menu-card featured-card">
                <h3>📊 통계/분석</h3>
                <p>AS 및 판매 통계를 분석합니다.</p>
            </a>
        </div>
    </div>
</body>

</html>
<?php
mysql_close($connect);
?>