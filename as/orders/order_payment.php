<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

require_once '../db_config.php';

// POST 방식 우선, GET 방식도 지원
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
} else {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $action = isset($_GET['action']) ? $_GET['action'] : '';
}

if (empty($id) || empty($action)) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

if ($action === 'complete') {
    // 판매 완료: s20_sell_level을 '2'로 업데이트, s20_bank_check와 s20_sell_out_date에 현재 시간 기록, s20_bankcheck_w에 "center" 기록
    //s20_sell_time,s20_sell_out_no,s20_sell_out_no2도 s20_sell_out_date에 업데이트
    $now = date('Y-m-d H:i:s');


    $sell_out_data = $now;
    // 접수번호 자동 생성
    // s20_sell_out_date를 YYMMDD 형식으로 변환 (예: 2012-04-13 -> 120413)
    $date_part = date('ymd', strtotime($sell_out_data));
    $as_time = $date_part; // s20_sell_time에 저장할 값 (예: 120413)

    // 같은 날짜의 최대 순번 조회 (순번 중복 방지)
    $max_query = "SELECT MAX(CAST(SUBSTRING(s20_sell_out_no2, 7) AS UNSIGNED)) as max_seq
                  FROM step20_sell
                  WHERE s20_sell_out_no2 LIKE '{$date_part}%' AND s20_sell_out_no2 IS NOT NULL AND s20_sell_out_no2 != ''";
    $max_result = @mysql_query($max_query);
    $max_row = mysql_fetch_assoc($max_result);
    $seq_no = ($max_row['max_seq'] ?? 0) + 1; // 최대값 + 1
    $seq_no_str = str_pad($seq_no, 3, '0', STR_PAD_LEFT); // 3자리 제로패딩 (001, 002, ...)

    // s20_sell_out_no2: YYMMDD + 순번 (예: 120413001)
    $as_out_no2 = $date_part . $seq_no_str;

    // s20_sell_out_no: NO + YYMMDD + - + 순번 (예: NO120413-001)
    $as_out_no = 'NO' . $date_part . '-' . $seq_no_str;

    $as_out_no_esc = mysql_real_escape_string($as_out_no);
    $as_out_no2_esc = mysql_real_escape_string($as_out_no2);
    $as_time_esc = mysql_real_escape_string($as_time);

    // 총액 재계산 (완료 시점의 정확한 금액 반영)
    $total_query = "SELECT COALESCE(SUM(cost1 * s21_quantity), 0) as total FROM step21_sell_cart WHERE s21_sellid = $id";
    $total_result = mysql_query($total_query);
    $total_cost = 0;
    if ($total_result) {
        $total_row = mysql_fetch_assoc($total_result);
        $total_cost = intval($total_row['total'] ?? 0);
    }

    $update_query = "UPDATE step20_sell SET s20_sell_level = '2', s20_sell_time='$as_time_esc', s20_sell_out_no='$as_out_no_esc', s20_sell_out_no2='$as_out_no2_esc', s20_bank_check = '$now', s20_sell_out_date = '$now', s20_bankcheck_w = 'center', s20_total_cost = $total_cost WHERE s20_sellid = $id";
    $result = mysql_query($update_query);

    if ($result) {
        echo json_encode(['success' => true, 'message' => '판매가 완료되었습니다.']);
    } else {
        echo json_encode(['success' => false, 'message' => '업데이트 실패: ' . mysql_error($connect)]);
    }
} elseif ($action === 'confirm') {
    // 입금 확인: s20_bank_check를 현재 시간으로 업데이트
    $now = date('Y-m-d H:i:s');
    $update_query = "UPDATE step20_sell SET s20_bank_check = '$now' WHERE s20_sellid = $id";
    $result = mysql_query($update_query);

    if ($result) {
        echo json_encode(['success' => true, 'message' => '입금이 확인되었습니다.']);
    } else {
        echo json_encode(['success' => false, 'message' => '업데이트 실패: ' . mysql_error($connect)]);
    }
} elseif ($action === 'cancel') {
    // 판매 완료 취소: 완료 시 업데이트된 모든 값들을 초기화
    // s20_sell_level을 '1'로 변경하여 판매요청 탭으로 돌아감
    $update_query = "UPDATE step20_sell SET
        s20_sell_level = '1',
        s20_sell_time = '',
        s20_sell_out_no = '',
        s20_sell_out_no2 = '',
        s20_bank_check = NULL,
        s20_sell_out_date = NULL,
        s20_bankcheck_w = ''
        WHERE s20_sellid = $id";
    $result = mysql_query($update_query);

    if ($result) {
        echo json_encode(['success' => true, 'message' => '판매 완료가 취소되었습니다.']);
    } else {
        echo json_encode(['success' => false, 'message' => '업데이트 실패: ' . mysql_error($connect)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => '알 수 없는 동작입니다.']);
}

mysql_close($connect);
?>