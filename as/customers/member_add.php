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
$current_page = 'members';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 폼 데이터 수집 (분류는 단일 선택)
    $sec = isset($_POST['sec']) ? trim($_POST['sec']) : '';
    $com_name = isset($_POST['com_name']) ? trim($_POST['com_name']) : '';
    $phone1 = isset($_POST['phone1']) ? trim($_POST['phone1']) : '';
    $phone2 = isset($_POST['phone2']) ? trim($_POST['phone2']) : '';
    $phone3 = isset($_POST['phone3']) ? trim($_POST['phone3']) : '';

    // 필수 항목 검증
    if (empty($sec)) {
        $error = '분류를 선택해주세요.';
    } elseif (empty($com_name)) {
        $error = '업체명을 입력해주세요.';
    } elseif (empty($phone1) || empty($phone2) || empty($phone3)) {
        $error = '전화번호를 모두 입력해주세요.';
    } elseif (!preg_match('/^\d{1,4}$/', $phone1) || !preg_match('/^\d{1,4}$/', $phone2) || !preg_match('/^\d{1,4}$/', $phone3)) {
        $error = '전화번호 형식이 잘못되었습니다.';
    }

    if (empty($error)) {
        // 데이터베이스에 삽입
        $phone1_esc = mysql_real_escape_string($phone1);
        $phone2_esc = mysql_real_escape_string($phone2);
        $phone3_esc = mysql_real_escape_string($phone3);
        $sec_esc = mysql_real_escape_string($sec);
        $com_name_esc = mysql_real_escape_string($com_name);

        // 기본값 설정 (필수 필드)
        $com_num1 = '000';
        $com_num2 = '00';
        $com_num3 = '00000';
        $com_zip1 = '000';
        $com_zip2 = '00';
        $phone4 = '0';
        $phone5 = '0';
        $phone6 = '0';
        $com_man = '';
        $oaddr = '';
        $com_sec1 = '';

        $query = "INSERT INTO step11_member (
            s11_sec, s11_com_name, s11_com_man,
            s11_phone1, s11_phone2, s11_phone3, s11_phone4, s11_phone5, s11_phone6,
            s11_com_num1, s11_com_num2, s11_com_num3,
            s11_com_zip1, s11_com_zip2,
            s11_oaddr, s11_com_sec1, s11_com_sec2
        ) VALUES (
            '$sec_esc', '$com_name_esc', '$com_man',
            '$phone1_esc', '$phone2_esc', '$phone3_esc', '$phone4', '$phone5', '$phone6',
            '$com_num1', '$com_num2', '$com_num3',
            '$com_zip1', '$com_zip2',
            '$oaddr', '$com_sec1', ''
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
    <title>고객 등록 - 디지탈컴 AS 시스템</title>
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
            max-width: 800px;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
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
        input[type="email"],
        select,
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
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-item input[type="radio"] {
            width: auto;
            cursor: pointer;
        }

        .radio-item label {
            margin-bottom: 0;
            cursor: pointer;
            user-select: none;
        }

        .phone-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .phone-group input {
            flex: 1;
        }

        .phone-separator {
            color: #999;
            font-weight: bold;
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

        .form-hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
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
        function redirectToMembers() {
            setTimeout(function () {
                window.location.href = 'members.php';
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
        <a href="members.php" class="nav-item <?php echo $current_page === 'members' ? 'active' : ''; ?>">고객 관리</a>
        <a href="../products/products.php" class="nav-item">제품 관리</a>
        <a href="../stat/statistics.php" class="nav-item">통계/분석</a>

    </div>

    <div class="container">
        <div class="page-title">
            <h2>고객 등록</h2>
            <p>새로운 고객 정보를 등록합니다.</p>
        </div>

        <div class="form-container">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ 고객이 정상적으로 등록되었습니다. 곧 목록 페이지로 이동합니다...
                </div>
                <div class="redirect-message">
                    <p>고객이 등록되었습니다.</p>
                    <p><a href="members.php">고객 목록으로 돌아가기</a></p>
                </div>
                <script>redirectToMembers();</script>
            <?php else: ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        ✗ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>분류 <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="sec_general" name="sec" value="일반" checked>
                                <label for="sec_general">일반</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="sec_dealer" name="sec" value="대리점">
                                <label for="sec_dealer">대리점</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="sec_distributor" name="sec" value="딜러">
                                <label for="sec_distributor">딜러</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="com_name">업체명 <span class="required">*</span></label>
                            <input type="text" id="com_name" name="com_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>전화번호 <span class="required">*</span></label>
                        <div class="phone-group">
                            <input type="text" id="phone1" name="phone1" value="010" placeholder="010" maxlength="4"
                                required>
                            <span class="phone-separator">-</span>
                            <input type="text" id="phone2" name="phone2" value="1234" placeholder="1234" maxlength="4"
                                required>
                            <span class="phone-separator">-</span>
                            <input type="text" id="phone3" name="phone3" value="5678" placeholder="5678" maxlength="4"
                                required>
                        </div>
                        <div class="form-hint">형식: 010-1234-5678 (숫자만 입력)</div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-submit">등록하기</button>
                        <a href="members.php"><button type="button" class="btn-cancel">취소</button></a>
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