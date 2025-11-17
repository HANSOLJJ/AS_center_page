<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../db_config.php';

$error = '';
$success = false;

$user_name = $_SESSION['member_id'];
$current_page = 'products';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 폼 데이터 수집 (모델명, 시리얼 및 버전만)
    $model_name = isset($_POST['model_name']) ? trim($_POST['model_name']) : '';
    $model_sn = isset($_POST['model_sn']) ? trim($_POST['model_sn']) : '';

    // 필수 항목 검증
    if (empty($model_name)) {
        $error = '모델명을 입력해주세요.';
    } elseif (empty($model_sn)) {
        $error = '시리얼 및 버전을 입력해주세요.';
    }

    if (empty($error)) {
        // 데이터베이스에 삽입
        $model_name_esc = mysql_real_escape_string($model_name);
        $model_sn_esc = mysql_real_escape_string($model_sn);

        $query = "INSERT INTO step15_as_model (
            s15_model_name, s15_model_sn
        ) VALUES (
            '$model_name_esc', '$model_sn_esc'
        )";

        $result = mysql_query($query);
        if ($result) {
            $success = true;
        } else {
            $error = '데이터베이스 오류가 발생했습니다: ' . mysql_error();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>제품 등록 - AS 시스템</title>
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

        .page-title p {
            color: #666;
            font-size: 14px;
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

        .required {
            color: #c33;
            margin-left: 3px;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
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

        .redirect-message p {
            margin-bottom: 10px;
            color: #666;
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
        function redirectToProducts() {
            setTimeout(function () {
                window.location.href = 'products.php';
            }, 2000);
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
            <h2>제품 등록</h2>
            <p>새로운 제품을 등록합니다.</p>
        </div>

        <div class="form-container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ 제품이 정상적으로 등록되었습니다. 곧 목록 페이지로 이동합니다...
                </div>
                <div class="redirect-message">
                    <p>제품이 등록되었습니다.</p>
                    <p><a href="products.php">제품 목록으로 돌아가기</a></p>
                </div>
                <script>redirectToProducts();</script>
            <?php else: ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        ✗ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="model_name">모델명 <span class="required">*</span></label>
                        <input type="text" id="model_name" name="model_name" placeholder="예: DWM701T" required>
                    </div>

                    <div class="form-group">
                        <label for="model_sn">시리얼 및 버전 <span class="required">*</span></label>
                        <input type="text" id="model_sn" name="model_sn" placeholder="예: DWM-701" required>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-submit">등록하기</button>
                        <a href="products.php"><button type="button" class="btn-cancel">취소</button></a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
<?php
mysql_close($connect);
?>