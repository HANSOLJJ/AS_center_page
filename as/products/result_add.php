<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../mysql_compat.php';
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

$user_name = $_SESSION['member_id'];
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = isset($_POST['result']) ? trim($_POST['result']) : '';

    if (empty($result)) {
        $error = 'AS 결과 타입을 입력해주세요.';
    } else {
        $result_esc = mysql_real_escape_string($result);
        $query = "INSERT INTO step19_as_result (s19_result) VALUES ('$result_esc')";
        $result = mysql_query($query);
        if ($result) {
            $success = true;
        } else {
            $error = '데이터베이스 오류: ' . mysql_error();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AS 결과 타입 등록 - AS 시스템</title>
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
            max-width: 600px;
            margin: 0 auto;
        }

        .page-title {
            margin-bottom: 30px;
        }

        .page-title h2 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #f99;
            color: #c33;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #9f9;
            color: #3c3;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: flex-end;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
        }

        .redirect-message {
            text-align: center;
            padding: 20px;
        }

        .redirect-message a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .redirect-message a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function redirect() {
            setTimeout(function () { window.location.href = 'products.php?tab=result'; }, 2000);
        }
    </script>
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
        <a href="../dashboard.php" class="nav-item">대시보드</a>
        <a href="../as_task/as_requests.php" class="nav-item">AS 작업</a>
        <a href="../orders/orders.php" class="nav-item">자재 판매</a>
        <a href="../parts/parts.php" class="nav-item">자재 관리</a>
        <a href="../customers/members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item active">제품 관리</a>
        <a href="../stat/statistics.php" class="nav-item">통계/분석</a>

    </div>

    <div class="container">
        <div class="page-title">
            <h2>AS 결과 타입 등록</h2>
        </div>

        <div class="form-container">
            <?php if ($success): ?>
                <div class="alert alert-success">✓ AS 결과 타입이 등록되었습니다.</div>
                <div class="redirect-message">
                    <p><a href="products.php?tab=result">AS 결과 타입 목록으로</a></p>
                </div>
                <script>redirect();</script>
            <?php else: ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">✗ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="result">AS 결과 타입</label>
                        <input type="text" id="result" name="result" required>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn-submit">등록</button>
                        <a href="products.php?tab=result"><button type="button" class="btn-cancel">취소</button></a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
<?php mysql_close($connect); ?>