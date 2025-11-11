<?php
/**
 * AS 리포트 XLSX 내보내기 (판매 리포트 형식 적용)
 *
 * as_statistics.php에서 호출되는 AS 완료 데이터 리포트 생성 파일
 * PhpSpreadsheet 라이브러리를 사용하여 XLSX 형식으로 내보냄
 * 날짜 범위 필터링 지원 (GET 파라미터: start_date, end_date, range)
 * 자재별 자동 줄 분리 (하나의 AS에 여러 자재가 있을 경우)
 */

session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: login.php');
    exit;
}

// Composer 자동로드
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;

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

// AS 완료 데이터 조회
$where_clause = '';
if (!empty($start_date) && !empty($end_date)) {
    $where_clause = " WHERE s13_as_level = '5'
                    AND DATE(s13_as_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
                    AND '" . mysql_real_escape_string($end_date) . "'";
} else {
    $where_clause = " WHERE s13_as_level = '5'";
}

$query = "SELECT
    s13_asid,
    DATE_FORMAT(s13_as_out_date, '%Y-%m-%d') as as_out_date,
    ex_company,
    ex_category,
    ex_adress,
    s13_total_cost,
    s13_tax_code,
    s13_bankcheck_w
FROM step13_as
$where_clause
ORDER BY s13_asid DESC";

$result = mysql_query($query);

if (!$result) {
    die("오류: " . mysql_error());
}

// 스프레드시트 생성
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('AS 리포트');

// 헤더 작성
$headers = array(
    'No',
    '완료일',
    '업체명',
    '형태',
    '자재명',
    '수량',
    '총액',
    '주소',
    '세금계산서',
    '결제방법'
);

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// 헤더 스타일 설정
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '366092']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

// 데이터 행 작성
$row = 2;
$row_count = 0;
$no_counter = 0;

while ($as = mysql_fetch_assoc($result)) {
    $asid = $as['s13_asid'];
    $no_counter++;

    // AS 자재 조회 (step22_as_cart 사용)
    $cart_query = "SELECT
        s22_accid,
        cost_name,
        s22_quantity,
        cost1
        FROM step22_as_cart
        WHERE s22_asid = '" . mysql_real_escape_string($asid) . "'
        ORDER BY s22_accid ASC";

    $cart_result = mysql_query($cart_query);

    // 결제 방법 변환
    $payment_method = '3월 8일 이후 확인가능';
    if ($as['s13_bankcheck_w'] === 'center') {
        $payment_method = '센터 현금납부';
    } elseif ($as['s13_bankcheck_w'] === 'base') {
        $payment_method = '계좌이체';
    }

    // 세금계산서 발행 여부
    $tax_invoice = $as['s13_tax_code'] ? '발행' : '미발행';

    if (!$cart_result || mysql_num_rows($cart_result) === 0) {
        // 자재가 없으면 마스터 정보만 출력
        $sheet->setCellValue('A' . $row, $no_counter);
        $sheet->setCellValue('B' . $row, $as['as_out_date'] ?? '');
        $sheet->setCellValue('C' . $row, $as['ex_company'] ?? '');
        $sheet->setCellValue('D' . $row, $as['ex_category'] ?? '');
        $sheet->setCellValue('E' . $row, '');
        $sheet->setCellValue('F' . $row, '');
        $sheet->setCellValue('G' . $row, $as['s13_total_cost'] ?? '');
        $sheet->setCellValue('H' . $row, $as['ex_adress'] ?? '');
        $sheet->setCellValue('I' . $row, $tax_invoice);
        $sheet->setCellValue('J' . $row, $payment_method);

        // 데이터 셀 스타일
        $dataStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);

        $row++;
        $row_count++;
    } else {
        // 자재별로 줄 작성
        $is_first = true;
        $cart_rows = array();
        while ($cart = mysql_fetch_assoc($cart_result)) {
            $cart_rows[] = $cart;
        }

        $num_carts = count($cart_rows);

        foreach ($cart_rows as $idx => $cart) {
            $sheet->setCellValue('A' . $row, $is_first ? $no_counter : '');
            $sheet->setCellValue('B' . $row, $is_first ? ($as['as_out_date'] ?? '') : '');
            $sheet->setCellValue('C' . $row, $is_first ? ($as['ex_company'] ?? '') : '');
            $sheet->setCellValue('D' . $row, $is_first ? ($as['ex_category'] ?? '') : '');
            $sheet->setCellValue('E' . $row, $cart['cost_name'] ?? '');
            $sheet->setCellValue('F' . $row, $cart['s22_quantity'] ?? '');
            $sheet->setCellValue('G' . $row, $is_first ? ($as['s13_total_cost'] ?? '') : '');
            $sheet->setCellValue('H' . $row, $is_first ? ($as['ex_adress'] ?? '') : '');
            $sheet->setCellValue('I' . $row, $is_first ? $tax_invoice : '');
            $sheet->setCellValue('J' . $row, $is_first ? $payment_method : '');

            // 데이터 셀 스타일
            $dataStyle = [
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);

            $is_first = false;
            $row++;
            $row_count++;
        }
    }
}

// 열 너비 자동 조정
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(10);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(25);
$sheet->getColumnDimension('I')->setWidth(12);
$sheet->getColumnDimension('J')->setWidth(18);

// 행 높이 설정
$sheet->getRowDimension('1')->setRowHeight(25);

// 파일명 생성
$filename = 'AS_Report_' . date('Ymd_His') . '.xlsx';

// 헤더 설정
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 파일 출력
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
