<?php
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;

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

// 판매 완료 데이터 조회
$where_clause = '';
if (!empty($start_date) && !empty($end_date)) {
    $where_clause = " WHERE s20_sell_level = '2'
                    AND DATE(s20_sell_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
                    AND '" . mysql_real_escape_string($end_date) . "'";
} else {
    $where_clause = " WHERE s20_sell_level = '2'";
}

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
    die("오류: " . mysql_error());
}

// 스프레드시트 생성
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('판매 리포트');

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

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// 헤더 스타일 설정
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

// 데이터 행 작성
$row = 2;
$row_count = 0;

while ($sale = mysql_fetch_assoc($result)) {
    $sellid = $sale['s20_sellid'];

    // 판매 자재 조회
    $cart_query = "SELECT
        s21_accid,
        cost_name,
        s21_quantity,
        cost1
        FROM step21_sell_cart
        WHERE s21_sellid = '" . mysql_real_escape_string($sellid) . "'
        ORDER BY s21_accid ASC";

    $cart_result = mysql_query($cart_query);

    if (!$cart_result || mysql_num_rows($cart_result) === 0) {
        // 자재가 없으면 마스터 정보만 출력
        $sheet->setCellValue('A' . $row, $sale['s20_sellid'] ?? '');
        $sheet->setCellValue('B' . $row, $sale['s20_sell_out_no'] ?? '');
        $sheet->setCellValue('C' . $row, $sale['s20_sell_out_no2'] ?? '');
        $sheet->setCellValue('D' . $row, $sale['sell_in_date'] ?? '');
        $sheet->setCellValue('E' . $row, $sale['sell_out_date'] ?? '');
        $sheet->setCellValue('F' . $row, $sale['ex_company'] ?? '');
        $sheet->setCellValue('G' . $row, $sale['ex_tel'] ?? '');
        $sheet->setCellValue('H' . $row, '');
        $sheet->setCellValue('I' . $row, '');
        $sheet->setCellValue('J' . $row, '');
        $sheet->setCellValue('K' . $row, $sale['s20_total_cost'] ?? '');
        $sheet->setCellValue('L' . $row, $sale['s20_tax_code'] ? '발행' : '미발행');
        $sheet->setCellValue('M' . $row, $sale['s20_bankcheck_w'] ?? '');
        $sheet->setCellValue('N' . $row, $sale['s20_sell_level'] == '2' ? '완료' : '대기');

        // 데이터 셀 스타일
        $dataStyle = [
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray($dataStyle);

        $row++;
        $row_count++;
    } else {
        // 자재별로 줄 작성
        $is_first = true;
        while ($cart = mysql_fetch_assoc($cart_result)) {
            $sheet->setCellValue('A' . $row, $is_first ? ($sale['s20_sellid'] ?? '') : '');
            $sheet->setCellValue('B' . $row, $is_first ? ($sale['s20_sell_out_no'] ?? '') : '');
            $sheet->setCellValue('C' . $row, $is_first ? ($sale['s20_sell_out_no2'] ?? '') : '');
            $sheet->setCellValue('D' . $row, $is_first ? ($sale['sell_in_date'] ?? '') : '');
            $sheet->setCellValue('E' . $row, $is_first ? ($sale['sell_out_date'] ?? '') : '');
            $sheet->setCellValue('F' . $row, $is_first ? ($sale['ex_company'] ?? '') : '');
            $sheet->setCellValue('G' . $row, $is_first ? ($sale['ex_tel'] ?? '') : '');
            $sheet->setCellValue('H' . $row, $cart['cost_name'] ?? '');
            $sheet->setCellValue('I' . $row, $cart['s21_quantity'] ?? '');
            $sheet->setCellValue('J' . $row, $cart['cost1'] ?? '');
            $sheet->setCellValue('K' . $row, $is_first ? ($sale['s20_total_cost'] ?? '') : '');
            $sheet->setCellValue('L' . $row, $is_first ? ($sale['s20_tax_code'] ? '발행' : '미발행') : '');
            $sheet->setCellValue('M' . $row, $is_first ? ($sale['s20_bankcheck_w'] ?? '') : '');
            $sheet->setCellValue('N' . $row, $is_first ? ($sale['s20_sell_level'] == '2' ? '완료' : '대기') : '');

            // 데이터 셀 스타일
            $dataStyle = [
                'alignment' => ['horizontal' => 'left', 'vertical' => 'center', 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray($dataStyle);

            $is_first = false;
            $row++;
            $row_count++;
        }
    }
}

// 열 너비 자동 조정
$sheet->getColumnDimension('A')->setWidth(12);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(20);
$sheet->getColumnDimension('I')->setWidth(10);
$sheet->getColumnDimension('J')->setWidth(12);
$sheet->getColumnDimension('K')->setWidth(15);
$sheet->getColumnDimension('L')->setWidth(12);
$sheet->getColumnDimension('M')->setWidth(12);
$sheet->getColumnDimension('N')->setWidth(10);

// 행 높이 설정
$sheet->getRowDimension('1')->setRowHeight(25);

// 파일명 생성
$filename = 'Sales_Report_' . date('Ymd_His') . '.xlsx';

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
