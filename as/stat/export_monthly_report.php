<?php
/**
 * 월간 종합 리포트 XLSX 내보내기 (본사만)
 *
 * statistics.php의 월간 리포트 탭에서 호출되는 월별 종합 리포트 생성 파일
 * PhpSpreadsheet 라이브러리를 사용하여 XLSX 형식으로 내보냄
 * 본사의 AS 수리비 + 판매 소모품을 합친 리포트
 */

session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: ../login.php');
    exit;
}

// Composer 자동로드
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// MySQL 호환성 레이어 로드
require_once '../mysql_compat.php';

// 데이터베이스 연결
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

// 파라미터
$report_year = isset($_GET['report_year']) ? intval($_GET['report_year']) : date('Y');
$report_month = isset($_GET['report_month']) ? $_GET['report_month'] : date('m');

// 년월 검증
if ($report_month < 1 || $report_month > 12) {
    $report_month = date('m');
}

$start_date = $report_year . '-' . $report_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// ========================================
// AS 데이터 조회 (본사만)
// ========================================
$as_query = "SELECT
    COUNT(*) as as_count,
    COALESCE(SUM(s13_total_cost), 0) as as_total_cost
FROM step13_as
WHERE s13_as_level = '5'
AND s13_as_center = 'center1283763850'
AND DATE(s13_as_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
    AND '" . mysql_real_escape_string($end_date) . "'";

$as_result = mysql_query($as_query);
$as_data = mysql_fetch_assoc($as_result);

$as_count = intval($as_data['as_count']);
$as_total_cost = intval($as_data['as_total_cost']);

// ========================================
// 판매 데이터 조회 (본사만)
// ========================================
$sell_query = "SELECT
    COUNT(*) as sell_count,
    COALESCE(SUM(s20_total_cost), 0) as sell_total_cost
FROM step20_sell
WHERE s20_sell_level = '2'
AND s20_sell_center = 'center1283763850'
AND DATE(s20_sell_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
    AND '" . mysql_real_escape_string($end_date) . "'";

$sell_result = mysql_query($sell_query);
$sell_data = mysql_fetch_assoc($sell_result);

$sell_count = intval($sell_data['sell_count']);
$sell_total_cost = intval($sell_data['sell_total_cost']);

// ========================================
// 스프레드시트 생성
// ========================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('월간 종합 리포트');

// 제목 행
$sheet->setCellValue('A1', $report_year . '년 ' . $report_month . '월 본사 통합 리포트');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 공백 행
$sheet->setCellValue('A2', '');

// 헤더 작성
$headers = array('No', '분류', '수리건수', '수리비', '소모품 판매', '소모품 판매비', '합계(수리비 + 소모품)');
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '3', $header);
    $col++;
}

// 헤더 스타일 설정
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '366092']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A3:G3')->applyFromArray($headerStyle);

// 데이터 행 - 본사
$total_sum = $as_total_cost + $sell_total_cost;

$sheet->setCellValue('A4', 1);
$sheet->setCellValue('B4', '본사');
$sheet->setCellValue('C4', $as_count);
$sheet->setCellValue('D4', $as_total_cost);
$sheet->setCellValue('E4', $sell_count);
$sheet->setCellValue('F4', $sell_total_cost);
$sheet->setCellValue('G4', $total_sum);

// 데이터 셀 스타일
$dataStyle = [
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'numberFormat' => ['formatCode' => '#,##0']
];
$sheet->getStyle('A4:G4')->applyFromArray($dataStyle);

// 숫자 포맷 명시 (D, F, G 열)
$sheet->getStyle('D4')->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle('F4')->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle('G4')->getNumberFormat()->setFormatCode('#,##0');

// 열 너비 설정
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(20);

// 행 높이
$sheet->getRowDimension('1')->setRowHeight(25);
$sheet->getRowDimension('3')->setRowHeight(20);

// 파일명 생성
$filename = '월간리포트_' . $report_year . '년_' . str_pad($report_month, 2, '0', STR_PAD_LEFT) . '월' . '.xlsx';

// 헤더 설정
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 파일 출력
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

mysql_close($connect);
exit;
?>