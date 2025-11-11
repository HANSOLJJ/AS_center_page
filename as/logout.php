<?php
/**
 * AS System - Logout
 * 로그아웃 처리
 */

header('Content-Type: text/html; charset=utf-8');
session_start();

// 세션 초기화
session_destroy();

// 로그인 페이지로 리다이렉트
header('Location: login.php');
exit;
?>
