<?php
/**
 * AS System - Login Processing
 * 로그인 폼 처리 및 인증
 */

header('Content-Type: text/html; charset=utf-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$admin_id = isset($_POST['admin_id']) ? trim($_POST['admin_id']) : '';
$admin_password = isset($_POST['admin_password']) ? trim($_POST['admin_password']) : '';

// 입력값 검증
if (empty($admin_id) || empty($admin_password)) {
    $_SESSION['login_error'] = '아이디와 비밀번호를 입력해주세요.';
    header('Location: login.php');
    exit;
}

// MySQL 호환성 레이어 로드
require_once 'mysql_compat.php';

// 데이터베이스 연결
$connect = @mysql_connect('mysql', 'mic4u_user', 'change_me');
if (!$connect) {
    $_SESSION['login_error'] = '데이터베이스 연결 오류가 발생했습니다.';
    header('Location: login.php');
    exit;
}

@mysql_select_db('mic4u', $connect);

// 관리자 정보 조회 - 2010_admin_member 테이블
$query = "SELECT `id`, `passwd`, `userlevel` FROM `2010_admin_member` WHERE `id` = '$admin_id' LIMIT 1";
$result = @mysql_query($query);

if (!$result) {
    $_SESSION['login_error'] = '데이터베이스 쿼리 오류가 발생했습니다.';
    @mysql_close($connect);
    header('Location: login.php');
    exit;
}

$rows = @mysql_num_rows($result);

// 조회된 사용자가 없는 경우
if (!$rows) {
    $_SESSION['login_error'] = '존재하지 않는 관리자 ID입니다.';
    @mysql_close($connect);
    header('Location: login.php');
    exit;
}

// 조회된 관리자 정보 처리
$admin = @mysql_fetch_assoc($result);
$db_passwd = $admin['passwd'];
$db_level = $admin['userlevel'] ?? '0';
$db_name = $admin['id'];

// 비밀번호 비교
// DB에 저장된 형식에 따라 처리
$password_match = false;

// MySQL PASSWORD() 함수를 사용한 경우
$query_pwd = @mysql_query("SELECT password('$admin_password')");
$encrypted_pwd = @mysql_result($query_pwd, 0, 0);

// 암호화된 비밀번호 비교 (MySQL PASSWORD 함수는 *로 시작하는 41자 해시)
if (strlen($encrypted_pwd) > 16 && strlen($db_passwd) > 16) {
    // 신 형식 (*로 시작하는 SHA1)
    $password_match = ($db_passwd === $encrypted_pwd);
} else if (strlen($db_passwd) <= 16) {
    // 구 형식 (16자 이하)
    $password_match = ($db_passwd === substr($encrypted_pwd, 0, 16));
} else {
    // 평문 비교 (개발용)
    $password_match = ($db_passwd === $admin_password);
}

if (!$password_match) {
    $_SESSION['login_error'] = '비밀번호가 일치하지 않습니다.';
    @mysql_close($connect);
    header('Location: login.php');
    exit;
}

// 로그인 성공 - 세션 설정
$_SESSION['member_id'] = $admin['id'];
$_SESSION['member_sid'] = 'sid_' . time() . '_' . rand(10000, 99999);
$_SESSION['member_level'] = $db_level;
$_SESSION['user_name'] = $db_name;
$_SESSION['login_time'] = time();

@mysql_close($connect);

// dashboard로 리다이렉트
header('Location: dashboard.php');
exit;
?>
