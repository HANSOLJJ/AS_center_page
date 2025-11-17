<?php
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../db_config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // 삭제 실행
    $delete_query = "DELETE FROM step11_member WHERE s11_meid = " . $id;
    $result = mysql_query($delete_query);

    if ($result && mysql_affected_rows() > 0) {
        // 삭제 성공
        header('Location: members.php?deleted=1');
    } else {
        // 삭제 실패
        header('Location: members.php?error=delete_failed');
    }
} else {
    header('Location: members.php?error=invalid_id');
}

exit;
?>
