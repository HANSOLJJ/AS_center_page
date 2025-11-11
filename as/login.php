<?php
/**
 * AS System - Login Page
 * 관리자 인증을 위한 로그인 페이지
 */

header('Content-Type: text/html; charset=utf-8');
session_start();

// 이미 로그인한 경우 dashboard로 리다이렉트
if (isset($_SESSION['member_id']) && !empty($_SESSION['member_id'])) {
    header('Location: dashboard.php');
    exit;
}

// 로그인 실패 메시지
$error_message = '';
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// 성공 메시지
$success_message = '';
if (isset($_SESSION['login_success'])) {
    $success_message = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AS System - 로그인</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, '나눔고딕', 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 28px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .login-header p {
            font-size: 14px;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }

        .demo-info {
            background: #f0f4ff;
            border: 1px solid #d0d8ff;
            padding: 12px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 12px;
            color: #555;
        }

        .demo-info strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>AS System</h1>
            <p>Service Management</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login_process.php">
            <div class="form-group">
                <label for="admin_id">관리자 ID</label>
                <input type="text" id="admin_id" name="admin_id" required autofocus>
            </div>

            <div class="form-group">
                <label for="admin_password">비밀번호</label>
                <input type="password" id="admin_password" name="admin_password" required>
            </div>

            <button type="submit" class="btn-login">로그인</button>
        </form>

        <div class="demo-info">
            <strong>기본 관리자 계정:</strong>
            ID: raintrace<br>
            비밀번호: (원본 DB 참조)
        </div>

        <div class="login-footer">
            <p>&copy; 2024 AS System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
