<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../db_config.php';

$user_name = $_SESSION['member_id'];
$error_message = '';
$success_message = '';

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s1_name = isset($_POST['s1_name']) ? trim($_POST['s1_name']) : '';
    $s1_caid = isset($_POST['s1_caid']) ? (int) $_POST['s1_caid'] : 0;
    $s1_cost_c_1 = isset($_POST['s1_cost_c_1']) ? (int) $_POST['s1_cost_c_1'] : 0;
    $s1_cost_a_1 = isset($_POST['s1_cost_a_1']) ? (int) $_POST['s1_cost_a_1'] : 0;
    $s1_cost_a_2 = isset($_POST['s1_cost_a_2']) ? (int) $_POST['s1_cost_a_2'] : 0;
    $s1_cost_n_1 = isset($_POST['s1_cost_n_1']) ? (int) $_POST['s1_cost_n_1'] : 0;
    $s1_cost_n_2 = isset($_POST['s1_cost_n_2']) ? (int) $_POST['s1_cost_n_2'] : 0;
    $s1_cost_s_1 = isset($_POST['s1_cost_s_1']) ? (int) $_POST['s1_cost_s_1'] : 0;

    // 유효성 검사
    if (empty($s1_name)) {
        $error_message = '자재명은 필수입니다.';
    } else {
        // INSERT 쿼리
        $query = "
            INSERT INTO step1_parts 
            (s1_name, s1_caid, s1_cost_c_1, s1_cost_a_1, s1_cost_a_2, s1_cost_n_1, s1_cost_n_2, s1_cost_s_1)
            VALUES 
            ('" . mysql_real_escape_string($s1_name) . "', $s1_caid, $s1_cost_c_1, $s1_cost_a_1, $s1_cost_a_2, $s1_cost_n_1, $s1_cost_n_2, $s1_cost_s_1)
        ";

        if (mysql_query($query)) {
            header('Location: parts.php?inserted=1');
            exit;
        } else {
            $error_message = '자재 추가에 실패했습니다: ' . mysql_error();
        }
    }
}

// 카테고리 목록 조회
$category_result = mysql_query("SELECT s5_caid, s5_category FROM step5_category ORDER BY s5_caid ASC");
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 자재 등록 - AS 시스템</title>
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn-submit {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
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
            background: #e0e0e0;
            color: #333;
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
            margin-top: 25px;
            margin-bottom: 15px;
            color: #667eea;
            font-weight: 600;
        }

        .required {
            color: #e74c3c;
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
        <a href="../dashboard.php" class="nav-item">대시보드</a>
        <a href="../as_task/as_requests.php" class="nav-item">AS 작업</a>
        <a href="../orders/orders.php" class="nav-item">자재 판매</a>
        <a href="parts.php" class="nav-item active">자재 관리</a>
        <a href="../customers/members.php" class="nav-item">고객 관리</a>
        <a href="../products/products.php" class="nav-item">제품 관리</a>
        <a href="../stat/statistics.php" class="nav-item">통계/분석</a>
    </div>


    <div class="container">
        <div class="content">
            <h2>새 자재 등록</h2>

            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- 기본 정보 -->
                <div class="section-title">기본 정보</div>

                <div class="form-group">
                    <label for="s1_name">자재명 <span class="required">*</span></label>
                    <input type="text" id="s1_name" name="s1_name" placeholder="자재명을 입력하세요" required>
                </div>

                <div class="form-group">
                    <label for="s1_caid">카테고리</label>
                    <select id="s1_caid" name="s1_caid">
                        <option value="0">선택 안 함</option>
                        <?php
                        while ($cat = mysql_fetch_assoc($category_result)) {
                            echo '<option value="' . $cat['s5_caid'] . '">' . htmlspecialchars($cat['s5_category']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- 공급가 정보 -->
                <div class="section-title">AS center 공급가</div>

                <div class="form-group">
                    <label for="s1_cost_c_1">AS center 공급가</label>
                    <input type="number" id="s1_cost_c_1" name="s1_cost_c_1" value="0" min="0">
                </div>

                <!-- 대리점 공급가 -->
                <div class="section-title">대리점 공급가</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="s1_cost_a_1">개별판매</label>
                        <input type="number" id="s1_cost_a_1" name="s1_cost_a_1" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label for="s1_cost_a_2">수리시</label>
                        <input type="number" id="s1_cost_a_2" name="s1_cost_a_2" value="0" min="0">
                    </div>
                </div>

                <!-- 일반판매 공급가 -->
                <div class="section-title">일반판매 공급가</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="s1_cost_n_1">개별판매</label>
                        <input type="number" id="s1_cost_n_1" name="s1_cost_n_1" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label for="s1_cost_n_2">수리시</label>
                        <input type="number" id="s1_cost_n_2" name="s1_cost_n_2" value="0" min="0">
                    </div>
                </div>

                <!-- 특별공급가 -->
                <div class="section-title">특별공급가</div>
                <div class="form-group">
                    <label for="s1_cost_s_1">특별공급가</label>
                    <input type="number" id="s1_cost_s_1" name="s1_cost_s_1" value="0" min="0">
                </div>

                <!-- 버튼 -->
                <div class="button-group">
                    <button type="submit" class="btn-submit">등록</button>
                    <a href="parts.php" class="btn-cancel">취소</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
<?php mysql_close($connect); ?>