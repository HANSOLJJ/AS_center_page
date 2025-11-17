<?php
/**
 * Database Configuration -->로컬 도커용
 * 데이터베이스 연결 설정 및 자동 연결
 */

// MySQL 호환성 레이어 로드
require_once __DIR__ . '/mysql_compat.php';

// 데이터베이스 연결 정보
// 로컬 개발: 'mysql', 'mic4u_user', 'change_me', 'mic4u'
// 서버 배포: 'localhost', 'dcom2000', 'Basserd2@@', 'dcom2000'
define('DB_HOST', 'mysql');
define('DB_USER', 'mic4u_user');
define('DB_PASS', 'change_me');
define('DB_NAME', 'mic4u');

// 자동 연결
$connect = @mysql_connect(DB_HOST, DB_USER, DB_PASS);
if (!$connect) {
    die('Database connection failed: ' . mysql_error());
}

// 데이터베이스 선택
if (!@mysql_select_db(DB_NAME, $connect)) {
    die('Database selection failed: ' . mysql_error());
}

// 전역 변수로 연결 저장 (기존 코드 호환성)
$GLOBALS['db_connect'] = $connect;
?>