<?php
header('Content-Type: text/csv; charset=utf-8-sig');
header('Content-Disposition: attachment; filename="Sales_Report_' . date('Ymd_His') . '.csv"');

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

// 날짜 범위 파라미터
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$range = isset($_GET['range']) ? $_GET['range'] : 'month';

// 기간 설정
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');
$year_start = date('Y-01-01');

if ($range === 'today') {
    $start_date = $today;
    $end_date = $today;
} elseif ($range === 'week') {
    $start_date = $week_start;
    $end_date = $today;
} elseif ($range === 'month') {
    $start_date = $month_start;
    $end_date = $today;
} elseif ($range === 'year') {
    $start_date = $year_start;
    $end_date = $today;
} elseif ($range === '' || $range === 'all') {
    $start_date = '';
    $end_date = '';
}

// CSV 출력 함수
function outputCSV($fields) {
    $output = '';
    foreach ($fields as $field) {
        // 필드에 쉼표, 따옴표, 줄바꿈이 있으면 따옴표로 감싸기
        $field = str_replace('"', '""', $field);
        if (preg_match('/[,"\r\n]/', $field)) {
            $output .= '"' . $field . '",';
        } else {
            $output .= $field . ',';
        }
    }
    return rtrim($output, ',') . "\r\n";
}

// 판매 완료 데이터 조회
$where_clause = '';
if (!empty($start_date) && !empty($end_date)) {
    $where_clause = " WHERE s20_sell_level = '2'
                    AND DATE(s20_sell_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
                    AND '" . mysql_real_escape_string($end_date) . "'";
} else {
    $where_clause = " WHERE s20_sell_level = '2'";
}

// Step 1: 판매 마스터 조회
$query = "SELECT
    s20_sellid,
    s20_sell_out_no,
    s20_sell_out_no2,
    DATE_FORMAT(s20_sell_in_date, '%Y-%m-%d %H:%i') as sell_in_date,
    DATE_FORMAT(s20_sell_out_date, '%Y-%m-%d %H:%i') as sell_out_date,
    ex_company,
    ex_tel,
    s20_total_cost,
    s20_sell_level,
    s20_tax_code,
    s20_bankcheck_w
FROM step20_sell
$where_clause
ORDER BY s20_sellid DESC";

$result = mysql_query($query);

if (!$result) {
    echo "오류: " . mysql_error();
    exit;
}

// 헤더 작성
$headers = array(
    '판매ID',
    '판매번호',
    '판매번호2',
    '접수일시',
    '완료일시',
    '업체명',
    '전화번호',
    '자재명',
    '수량',
    '단가',
    '합계',
    '세금계산서',
    '입금확인',
    '상태'
);

echo outputCSV($headers);

// 데이터 행 작성
$row_count = 0;
while ($row = mysql_fetch_assoc($result)) {
    $sellid = $row['s20_sellid'];

    // 판매 자재 조회
    $cart_query = "SELECT
        s21_accid,
        cost_name,
        s21_quantity,
        cost1,
        cost2,
        s21_sp_cost
        FROM step21_sell_cart
        WHERE s21_sellid = '" . mysql_real_escape_string($sellid) . "'
        ORDER BY s21_accid ASC";

    $cart_result = mysql_query($cart_query);

    if (!$cart_result || mysql_num_rows($cart_result) === 0) {
        // 자재가 없으면 마스터 정보만 출력
        $fields = array(
            $row['s20_sellid'] ?? '',
            $row['s20_sell_out_no'] ?? '',
            $row['s20_sell_out_no2'] ?? '',
            $row['sell_in_date'] ?? '',
            $row['sell_out_date'] ?? '',
            $row['ex_company'] ?? '',
            $row['ex_tel'] ?? '',
            '', // 자재명
            '', // 수량
            '', // 단가
            $row['s20_total_cost'] ?? '',
            $row['s20_tax_code'] ? '발행' : '미발행',
            $row['s20_bankcheck_w'] ?? '',
            $row['s20_sell_level'] == '2' ? '완료' : '대기'
        );
        echo outputCSV($fields);
        $row_count++;
    } else {
        // 자재별로 줄 작성
        $is_first = true;
        while ($cart = mysql_fetch_assoc($cart_result)) {
            $fields = array(
                $is_first ? ($row['s20_sellid'] ?? '') : '',
                $is_first ? ($row['s20_sell_out_no'] ?? '') : '',
                $is_first ? ($row['s20_sell_out_no2'] ?? '') : '',
                $is_first ? ($row['sell_in_date'] ?? '') : '',
                $is_first ? ($row['sell_out_date'] ?? '') : '',
                $is_first ? ($row['ex_company'] ?? '') : '',
                $is_first ? ($row['ex_tel'] ?? '') : '',
                $cart['cost_name'] ?? '',
                $cart['s21_quantity'] ?? '',
                $cart['cost1'] ?? '',
                $is_first ? ($row['s20_total_cost'] ?? '') : '',
                $is_first ? ($row['s20_tax_code'] ? '발행' : '미발행') : '',
                $is_first ? ($row['s20_bankcheck_w'] ?? '') : '',
                $is_first ? ($row['s20_sell_level'] == '2' ? '완료' : '대기') : ''
            );
            echo outputCSV($fields);
            $is_first = false;
            $row_count++;
        }
    }
}

// 데이터가 없으면 메시지 추가
if ($row_count === 0) {
    echo outputCSV(array('데이터가 없습니다.'));
}
?>
