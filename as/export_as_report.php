<?php
header('Content-Type: text/csv; charset=utf-8-sig');
header('Content-Disposition: attachment; filename="AS_Report_' . date('Ymd_His') . '.csv"');

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

// AS 완료 데이터 조회
$where_clause = '';
if (!empty($start_date) && !empty($end_date)) {
    $where_clause = " WHERE s13_as_level = '2'
                    AND DATE(s13_as_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
                    AND '" . mysql_real_escape_string($end_date) . "'";
} else {
    $where_clause = " WHERE s13_as_level = '2'";
}

$query = "SELECT
    s13_asid,
    s13_as_out_no,
    DATE_FORMAT(s13_as_in_date, '%Y-%m-%d %H:%i') as as_in_date,
    DATE_FORMAT(s13_as_out_date, '%Y-%m-%d %H:%i') as as_out_date,
    ex_company,
    ex_tel,
    s13_as_level,
    ex_total_cost
FROM step13_as
$where_clause
ORDER BY s13_asid DESC";

$result = mysql_query($query);

if (!$result) {
    echo "오류: " . mysql_error();
    exit;
}

// 헤더 작성
$headers = array(
    'AS ID',
    '접수번호',
    '접수일시',
    '완료일시',
    '업체명',
    '전화번호',
    '상태',
    '수리비'
);

echo outputCSV($headers);

// 데이터 행 작성
$row_count = 0;
while ($row = mysql_fetch_assoc($result)) {
    // 상태 변환
    $status = '';
    switch ($row['s13_as_level']) {
        case '5':
            $status = '완료';
            break;
        case '4':
            $status = '수리중';
            break;
        case '3':
            $status = '접수완료';
            break;
        case '2':
            $status = '견적대기';
            break;
        default:
            $status = '신청';
            break;
    }

    $fields = array(
        $row['s13_asid'] ?? '',
        $row['s13_as_out_no'] ?? '',
        $row['as_in_date'] ?? '',
        $row['as_out_date'] ?? '',
        $row['ex_company'] ?? '',
        $row['ex_tel'] ?? '',
        $status,
        $row['ex_total_cost'] ?? ''
    );

    echo outputCSV($fields);
    $row_count++;
}

// 데이터가 없으면 메시지 추가
if ($row_count === 0) {
    echo outputCSV(array('데이터가 없습니다.'));
}
?>
