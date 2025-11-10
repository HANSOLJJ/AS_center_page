<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>데이터베이스 연결 테스트</h1>";

// MySQL 호환성 레이어 로드
if (file_exists('mysql_compat.php')) {
    echo "✓ mysql_compat.php 파일 존재<br>";
    require_once 'mysql_compat.php';
} else {
    echo "✗ mysql_compat.php 파일 없음<br>";
}

// MySQL 연결 시도
echo "<h2>1. MySQL 연결 시도...</h2>";
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');

if ($connect) {
    echo "✓ MySQL 연결 성공!<br>";
    echo "호스트: mysql<br>";
    echo "사용자: mic4u_user<br>";
} else {
    echo "✗ MySQL 연결 실패!<br>";
    echo "에러: " . mysql_error() . "<br>";
    echo "<br>디버깅 정보:<br>";
    echo "- 호스트가 'mysql'로 올바른가?<br>";
    echo "- MySQL 서버가 실행 중인가?<br>";
    echo "- 사용자명/비밀번호가 올바른가?<br>";
    die();
}

// 데이터베이스 선택
echo "<h2>2. 데이터베이스 선택 시도...</h2>";
if (mysql_select_db('mic4u', $connect)) {
    echo "✓ mic4u 데이터베이스 선택 성공!<br>";
} else {
    echo "✗ mic4u 데이터베이스 선택 실패!<br>";
    echo "에러: " . mysql_error() . "<br>";
    die();
}

// step13_as 테이블 존재 확인
echo "<h2>3. step13_as 테이블 확인...</h2>";
$result = mysql_query("SELECT COUNT(*) as cnt FROM step13_as LIMIT 1");
if ($result) {
    $row = mysql_fetch_assoc($result);
    echo "✓ step13_as 테이블 존재<br>";
    echo "현재 데이터 개수: " . $row['cnt'] . "<br>";
} else {
    echo "✗ step13_as 테이블 접근 실패<br>";
    echo "에러: " . mysql_error() . "<br>";
}

// step11_member 테이블 확인
echo "<h2>4. step11_member 테이블 확인...</h2>";
$result = mysql_query("SELECT COUNT(*) as cnt FROM step11_member LIMIT 1");
if ($result) {
    $row = mysql_fetch_assoc($result);
    echo "✓ step11_member 테이블 존재<br>";
    echo "현재 데이터 개수: " . $row['cnt'] . "<br>";
} else {
    echo "✗ step11_member 테이블 접근 실패<br>";
    echo "에러: " . mysql_error() . "<br>";
}

// JOIN 테스트
echo "<h2>5. JOIN 쿼리 테스트...</h2>";
$test_query = "SELECT a.s13_asid, m.s11_phone1, m.s11_phone2, m.s11_phone3 
               FROM step13_as a
               LEFT JOIN step11_member m ON a.s13_meid = m.s11_meid
               LIMIT 1";
$result = mysql_query($test_query);
if ($result) {
    echo "✓ LEFT JOIN 쿼리 성공<br>";
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        echo "샘플 데이터:<br>";
        echo "- s13_asid: " . $row['s13_asid'] . "<br>";
        echo "- s11_phone1: " . $row['s11_phone1'] . "<br>";
        echo "- s11_phone2: " . $row['s11_phone2'] . "<br>";
        echo "- s11_phone3: " . $row['s11_phone3'] . "<br>";
    } else {
        echo "데이터가 없습니다.<br>";
    }
} else {
    echo "✗ JOIN 쿼리 실패<br>";
    echo "에러: " . mysql_error() . "<br>";
    echo "쿼리: " . htmlspecialchars($test_query) . "<br>";
}

mysql_close($connect);

echo "<h2>테스트 완료</h2>";
echo "모든 테스트가 성공하면 as_requests.php와 as_statistics.php도 정상 작동해야 합니다.<br>";
echo "<a href='as_requests.php'>as_requests.php로 이동</a>";
?>
