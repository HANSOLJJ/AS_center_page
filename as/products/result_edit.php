<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: ../login.php');
    exit;
}

// MySQL 호환성 레이어 로드
require_once '../mysql_compat.php';

// 데이터베이스 연결
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

$user_name = $_SESSION['member_id'];

$error = '';
$success = false;
$result_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($result_id <= 0) {
    header('Location: products.php?tab=result');
    exit;
}

// AS 결과 타입 정보 조회
$result = mysql_query("SELECT * FROM step19_as_result WHERE s19_asrid = $result_id LIMIT 1");
if (!$result || mysql_num_rows($result) === 0) {
    header('Location: products.php?tab=result');
    exit;
}

$result_data = mysql_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 폼 데이터 수집
    $result_name = isset($_POST['result_name']) ? trim($_POST['result_name']) : '';

    // 필수 항목 검증
    if (empty($result_name)) {
        $error = 'AS 결과 타입을 입력해주세요.';
    }

    if (empty($error)) {
        // 데이터베이스에 업데이트
        $result_name_esc = mysql_real_escape_string($result_name);

        $query = "UPDATE step19_as_result SET
            s19_result = '$result_name_esc'
        WHERE s19_asrid = $result_id";

        $result = mysql_query($query);
        if ($result) {
            $success = true;
            // 업데이트된 정보 다시 로드
            $result = mysql_query("SELECT * FROM step19_as_result WHERE s19_asrid = $result_id LIMIT 1");
            $result_data = mysql_fetch_assoc($result);
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
    <title>AS 결과 타입 수정 - AS 시스템</title>
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

        .result-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .result-info p {
            margin-bottom: 5px;
            font-size: 14px;
        }

        .result-info strong {
            color: #667eea;
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

        .btn-delete {
            background: #ff6b6b;
            color: white;
            margin-right: auto;
        }

        .btn-delete:hover {
            background: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
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
                window.location.href = 'products.php?tab=result';
            }, 2000);
        }

        function deleteConfirm() {
            if (confirm('삭제하시겠습니까?')) {
                window.location.href = 'products.php?action=delete_result&id=<?php echo $result_id; ?>&tab=result';
            }
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
            <h2>AS 결과 타입 수정</h2>
            <p>AS 결과 타입 정보를 수정합니다.</p>
        </div>

        <div class="form-container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ AS 결과 타입이 정상적으로 수정되었습니다. 곧 목록 페이지로 이동합니다...
                </div>
                <div class="redirect-message">
                    <p>AS 결과 타입이 수정되었습니다.</p>
                    <p><a href="products.php?tab=result">AS 결과 타입 목록으로 돌아가기</a></p>
                </div>
                <script>redirectToProducts();</script>
            <?php else: ?>
                <div class="result-info">
                    <p>번호: <strong><?php echo htmlspecialchars($result_data['s19_asrid']); ?></strong></p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        ✗ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="result_name">AS 결과 타입 <span class="required">*</span></label>
                        <input type="text" id="result_name" name="result_name"
                            value="<?php echo htmlspecialchars($result_data['s19_result']); ?>" required>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn-delete" onclick="deleteConfirm()">삭제</button>
                        <button type="submit" class="btn-submit">수정하기</button>
                        <a href="products.php?tab=result"><button type="button" class="btn-cancel">취소</button></a>
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