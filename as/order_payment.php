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

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (empty($id) || empty($action)) {
    die('잘못된 요청입니다.');
}

// POST 방식으로도 처리 가능하도록
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : $id;
    $action = isset($_POST['action']) ? $_POST['action'] : $action;
}

if ($action === 'complete') {
    // 판매 완료: s20_as_level을 '2'로 업데이트, s20_bank_check와 s20_as_out_date에 현재 시간 기록, s20_bankcheck_w에 "center" 기록
    //s20_as_time,s20_as_in_no,s20_as_in_no2도 s20_as_out_date에 업데이트
    $now = date('Y-m-d H:i:s');


    $sell_out_data = $now;
    // 접수번호 자동 생성
    // s20_as_in_date를 YYMMDD 형식으로 변환 (예: 2012-04-13 -> 120413)
    $date_part = date('ymd', strtotime($sell_out_data));
    $as_time = $date_part; // s20_as_time에 저장할 값 (예: 120413)

    // 같은 날짜의 주문 개수 조회 (순번 결정)
    $count_query = "SELECT COUNT(*) as cnt FROM step20_sell WHERE DATE(s20_as_out_date) = DATE('$sell_out_data')";
    $count_result = @mysql_query($count_query);
    $count_row = mysql_fetch_assoc($count_result);
    $seq_no = ($count_row['cnt'] + 1); // 1부터 시작
    $seq_no_str = str_pad($seq_no, 3, '0', STR_PAD_LEFT); // 3자리 제로패딩 (001, 002, ...)

    // s20_as_in_no2: YYMMDD + 순번 (예: 120413001)
    $as_in_no2 = $date_part . $seq_no_str;

    // s20_as_in_no: NO + YYMMDD + - + 순번 (예: NO120413-001)
    $as_in_no = 'NO' . $date_part . '-' . $seq_no_str;

    $as_in_no_esc = mysql_real_escape_string($as_in_no);
    $as_in_no2_esc = mysql_real_escape_string($as_in_no2);
    $as_time_esc = mysql_real_escape_string($as_time);



    $update_query = "UPDATE step20_sell SET s20_as_level = '2', s20_as_time='$as_time_esc', s20_as_in_no='$as_in_no_esc', s20_as_in_no2='$as_in_no2_esc', s20_bank_check = '$now', s20_as_out_date = '$now', s20_bankcheck_w = 'center' WHERE s20_sellid = $id";
    $result = mysql_query($update_query);

    // step21_sell_cart의 s21_signdate를 s20_as_out_date와 동기화 (입금 확인 시간 동기화)
    if ($result) {
        $sync_query = "UPDATE step21_sell_cart SET s21_signdate = '$now' WHERE s21_sellid = $id";
        @mysql_query($sync_query);
    }

    if ($result) {
        // 성공하면 orders.php의 판매완료 탭으로 리다이렉트
        header('Location: orders.php?tab=completed');
        exit;
    } else {
        echo "업데이트 실패: " . mysql_error($connect);
    }
} elseif ($action === 'confirm') {
    // 입금 확인: s20_bank_check를 현재 시간으로 업데이트
    $now = date('Y-m-d H:i:s');
    $update_query = "UPDATE step20_sell SET s20_bank_check = '$now' WHERE s20_sellid = $id";
    $result = mysql_query($update_query);

    if ($result) {
        // 성공하면 orders.php의 구매신청 탭으로 리다이렉트
        header('Location: orders.php?tab=request');
        exit;
    } else {
        echo "업데이트 실패: " . mysql_error($connect);
    }
} elseif ($action === 'cancel') {
    // 판매 완료 취소: 완료 시 업데이트된 모든 값들을 초기화
    // s20_as_level을 '1'로 변경하여 판매요청 탭으로 돌아감
    $update_query = "UPDATE step20_sell SET
        s20_as_level = '1',
        s20_as_time = '',
        s20_as_in_no = '',
        s20_as_in_no2 = '',
        s20_bank_check = NULL,
        s20_as_out_date = NULL,
        s20_bankcheck_w = ''
        WHERE s20_sellid = $id";
    $result = mysql_query($update_query);

    // step21_sell_cart의 s21_signdate를 NULL로 리셋 (입금 취소 시 동기화)
    if ($result) {
        $sync_query = "UPDATE step21_sell_cart SET s21_signdate = NULL WHERE s21_sellid = $id";
        @mysql_query($sync_query);
    }

    if ($result) {
        // 성공하면 orders.php의 판매요청 탭으로 리다이렉트
        header('Location: orders.php?tab=request');
        exit;
    } else {
        echo "업데이트 실패: " . mysql_error($connect);
    }
} else {
    die('알 수 없는 동작입니다.');
}

mysql_close($connect);
?>