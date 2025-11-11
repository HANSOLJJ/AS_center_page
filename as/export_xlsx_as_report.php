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
    s13_as_out_no,
    DATE_FORMAT(s13_as_in_date, '%Y-%m-%d %H:%i') as as_in_date,
    DATE_FORMAT(s13_as_out_date, '%Y-%m-%d %H:%i') as as_out_date,
    ex_company,
    ex_tel,
    ex_total_cost
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
    'AS ID',
    '접수번호',
    '접수일시',
    '완료일시',
    '업체명',
    '전화번호',
    '수리비'
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
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// 데이터 행 작성
$row = 2;
$row_count = 0;
while ($data = mysql_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $row, $data['s13_asid'] ?? '');
    $sheet->setCellValue('B' . $row, $data['s13_as_out_no'] ?? '');
    $sheet->setCellValue('C' . $row, $data['as_in_date'] ?? '');
    $sheet->setCellValue('D' . $row, $data['as_out_date'] ?? '');
    $sheet->setCellValue('E' . $row, $data['ex_company'] ?? '');
    $sheet->setCellValue('F' . $row, $data['ex_tel'] ?? '');
    $sheet->setCellValue('G' . $row, $data['ex_total_cost'] ?? '');

    // 데이터 셀 스타일
    $dataStyle = [
        'alignment' => ['horizontal' => 'left', 'vertical' => 'center', 'wrapText' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);

    $row++;
    $row_count++;
}

// 열 너비 자동 조정
$sheet->getColumnDimension('A')->setWidth(12);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(15);

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
