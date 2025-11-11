<?php
// MySQL 호환성 레이어 로드
require_once 'mysql_compat.php';

// 데이터베이스 연결
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

// s13_asid >= 34481인 레코드들에 s13_bankcheck_w = 'center' 설정
$update_query = "UPDATE step13_as SET s13_bankcheck_w = 'center' WHERE s13_asid >= 34481";
$result = @mysql_query($update_query);

if ($result) {
    $affected = mysql_affected_rows();
    echo "✅ 업데이트 완료: {$affected}개 레코드 수정됨";
} else {
    echo "❌ 업데이트 실패: " . mysql_error();
}

mysql_close($connect);
?>
