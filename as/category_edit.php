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
$error_message = '';
$success_message = '';

// ID 확인
$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if (empty($id)) {
    header('Location: parts.php?tab=tab2');
    exit;
}

// 카테고리 정보 조회
$query = "SELECT s5_caid, s5_category FROM step5_category WHERE s5_caid = '" . mysql_real_escape_string($id) . "'";
$result = mysql_query($query);
$category = mysql_fetch_assoc($result);

if (!$category) {
    header('Location: parts.php?tab=tab2');
    exit;
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s5_category = isset($_POST['s5_category']) ? trim($_POST['s5_category']) : '';

    // 유효성 검사
    if (empty($s5_category)) {
        $error_message = '카테고리명은 필수입니다.';
    } else {
        // UPDATE 쿼리
        $update_query = "
            UPDATE step5_category SET
            s5_category = '" . mysql_real_escape_string($s5_category) . "'
            WHERE s5_caid = '" . mysql_real_escape_string($id) . "'
        ";

        if (mysql_query($update_query)) {
            header('Location: parts.php?tab=tab2');
            exit;
        } else {
            $error_message = '카테고리 수정에 실패했습니다: ' . mysql_error();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>카테고리 수정 - AS 시스템</title>
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
            max-width: 800px;
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
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn-submit {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
        }

        .message {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: #efe;
            border: 1px solid #9f9;
            color: #3c3;
        }

        .error {
            background: #fee;
            border: 1px solid #f99;
            color: #c33;
        }

        .section-title {
            background: #f0f4ff;
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 15px;
            color: #667eea;
            font-weight: 600;
        }

        .info-box {
            background: #f9f9f9;
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #666;
            border-left: 4px solid #667eea;
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
        <a href="parts.php" class="nav-item active">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
    </div>

    <div class="container">
        <div class="content">
            <h2>✏️ 카테고리 수정</h2>

            <div class="info-box">
                <strong>카테고리 번호:</strong> <?php echo htmlspecialchars($category['s5_caid']); ?>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="section-title">📋 기본 정보</div>

                <div class="form-group">
                    <label for="s5_category">카테고리명 *</label>
                    <input type="text" id="s5_category" name="s5_category" placeholder="카테고리명을 입력하세요"
                        value="<?php echo htmlspecialchars($category['s5_category']); ?>" required>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-submit">수정</button>
                    <a href="parts.php?tab=tab2" class="btn-cancel">취소</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
<?php mysql_close($connect); ?>