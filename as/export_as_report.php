<?php
/**
 * AS 리포트 XLSX 내보내기
 *
 * as_statistics.php에서 호출되는 AS 완료 데이터 리포트 생성 파일
 * PhpSpreadsheet 라이브러리를 사용하여 XLSX 형식으로 내보냄
 * 날짜 범위 필터링 지원 (GET 파라미터: start_date, end_date, range)
 * 부품별 자동 줄 분리 (하나의 AS에 여러 부품이 있을 경우)
 * 셀 merge를 통한 명확한 구조 제시
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
    DATE_FORMAT(s13_as_in_date, '%Y-%m-%d') as as_in_date,
    s13_as_in_how,
    s13_as_out_no,
    ex_company,
    ex_sec1,
    DATE_FORMAT(s13_as_out_date, '%Y-%m-%d') as as_out_date,
    s13_total_cost,
    s13_bankcheck_w,
    COALESCE(ex_tel, '') as ex_tel,
    s13_tax_code
FROM step13_as
$where_clause
ORDER BY s13_as_out_date DESC";

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
    '입고일',
    '수탁방법',
    '접수번호',
    '업체명',
    '형태',
    '완료일',
    '제품명',
    '처리내역',
    '사용 부품',
    '수량',
    '가격',
    '총 수리비',
    '결제방법',
    '연락처',
    '세금계산서'
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
$sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

// 데이터 행 작성
$row = 2;
$no = 0;

while ($as = mysql_fetch_assoc($result)) {
    $no++;
    $asid = $as['s13_asid'];

    // 결제방법 로직
    $payment_method = '';
    if ($as['s13_bankcheck_w'] === 'center') {
        $payment_method = '센터 현금납부';
    } elseif ($as['s13_bankcheck_w'] === 'base') {
        $payment_method = '계좌이체';
    } else {
        $payment_method = '3월 8일 이후 확인가능';
    }

    // 세금계산서 표기
    $tax_display = $as['s13_tax_code'] ? '발행' : '미발행';

    // AS 제품명 및 처리내역 조회
    $item_query = "SELECT
        s14_aiid,
        cost_name,
        as_end_result
        FROM step14_as_item
        WHERE s14_asid = '" . mysql_real_escape_string($asid) . "'
        ORDER BY s14_aiid ASC
        LIMIT 1";
    
    $item_result = mysql_query($item_query);
    $product_name = '';
    $end_result = '';
    if ($item_result && mysql_num_rows($item_result) > 0) {
        $item = mysql_fetch_assoc($item_result);
        $product_name = $item['cost_name'] ?? '';
        $end_result = $item['as_end_result'] ?? '';
    }

    // AS 사용 부품 조회
    $parts_query = "SELECT
        s18_aiid,
        cost_name,
        s18_quantity,
        cost1
        FROM step18_as_cure_cart
        WHERE s18_aiid IN (SELECT s14_aiid FROM step14_as_item WHERE s14_asid = '" . mysql_real_escape_string($asid) . "')
        ORDER BY s18_aiid ASC";
    
    $parts_result = mysql_query($parts_query);
    $parts_count = $parts_result ? mysql_num_rows($parts_result) : 0;

    if ($parts_count === 0) {
        // 부품이 없으면 마스터 정보만 출력
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, $as['as_in_date']);
        $sheet->setCellValue('C' . $row, $as['s13_as_in_how'] ?? '');
        $sheet->setCellValue('D' . $row, $as['s13_as_out_no'] ?? '');
        $sheet->setCellValue('E' . $row, $as['ex_company'] ?? '');
        $sheet->setCellValue('F' . $row, $as['ex_sec1'] ?? '');
        $sheet->setCellValue('G' . $row, $as['as_out_date']);
        $sheet->setCellValue('H' . $row, $product_name);
        $sheet->setCellValue('I' . $row, $end_result);
        $sheet->setCellValue('J' . $row, '');
        $sheet->setCellValue('K' . $row, '');
        $sheet->setCellValue('L' . $row, '');
        $sheet->setCellValue('M' . $row, isset($as['s13_total_cost']) ? (int)$as['s13_total_cost'] : '');
        $sheet->setCellValue('N' . $row, $payment_method);
        $sheet->setCellValue('O' . $row, $as['ex_tel']);
        $sheet->setCellValue('P' . $row, $tax_display);

        // 데이터 셀 스타일
        $dataStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A' . $row . ':P' . $row)->applyFromArray($dataStyle);

        $row++;
    } else {
        // 부품별로 줄 작성
        $start_row = $row;
        $is_first = true;
        $total_repair_cost = 0; // 총 수리비 합산용
        while ($part = mysql_fetch_assoc($parts_result)) {
            // 부품별 가격 계산 (수량 * 단가)
            $part_price = (isset($part['s18_quantity']) && isset($part['cost1']))
                ? ($part['s18_quantity'] * $part['cost1'])
                : 0;
            $total_repair_cost += $part_price; // 총액에 누적

            $sheet->setCellValue('A' . $row, $is_first ? $no : '');
            $sheet->setCellValue('B' . $row, $is_first ? $as['as_in_date'] : '');
            $sheet->setCellValue('C' . $row, $is_first ? ($as['s13_as_in_how'] ?? '') : '');
            $sheet->setCellValue('D' . $row, $is_first ? ($as['s13_as_out_no'] ?? '') : '');
            $sheet->setCellValue('E' . $row, $is_first ? ($as['ex_company'] ?? '') : '');
            $sheet->setCellValue('F' . $row, $is_first ? ($as['ex_sec1'] ?? '') : '');
            $sheet->setCellValue('G' . $row, $is_first ? $as['as_out_date'] : '');
            $sheet->setCellValue('H' . $row, $is_first ? $product_name : '');
            $sheet->setCellValue('I' . $row, $is_first ? $end_result : '');
            $sheet->setCellValue('J' . $row, $part['cost_name'] ?? '');
            $sheet->setCellValue('K' . $row, (isset($part['s18_quantity']) ? $part['s18_quantity'] . '개' : ''));
            $sheet->setCellValue('L' . $row, (int)$part_price);
            $sheet->setCellValue('M' . $row, ''); // 루프 후 첫 줄에만 총액 입력
            $sheet->setCellValue('N' . $row, $is_first ? $payment_method : '');
            $sheet->setCellValue('O' . $row, $is_first ? $as['ex_tel'] : '');
            $sheet->setCellValue('P' . $row, $is_first ? $tax_display : '');

            // 데이터 셀 스타일 적용
            $sheet->getStyle('A' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('B' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('C' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('D' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('E' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('F' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('G' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('H' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('I' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('J' . $row)->applyFromArray(['alignment' => ['horizontal' => 'left', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('K' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('L' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('M' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('N' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('O' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
            $sheet->getStyle('P' . $row)->applyFromArray(['alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

            $is_first = false;
            $row++;
        }

        // 루프 후 첫 줄의 M 셀에 합산된 총 수리비 입력
        $sheet->setCellValue('M' . $start_row, (int)$total_repair_cost);

        // 셀 merge (A, B, C, D, E, F, G, H, I, M, N, O, P 컬럼)
        if ($parts_count > 1) {
            $end_row = $row - 1;
            $sheet->mergeCells('A' . $start_row . ':A' . $end_row);
            $sheet->mergeCells('B' . $start_row . ':B' . $end_row);
            $sheet->mergeCells('C' . $start_row . ':C' . $end_row);
            $sheet->mergeCells('D' . $start_row . ':D' . $end_row);
            $sheet->mergeCells('E' . $start_row . ':E' . $end_row);
            $sheet->mergeCells('F' . $start_row . ':F' . $end_row);
            $sheet->mergeCells('G' . $start_row . ':G' . $end_row);
            $sheet->mergeCells('H' . $start_row . ':H' . $end_row);
            $sheet->mergeCells('I' . $start_row . ':I' . $end_row);
            $sheet->mergeCells('M' . $start_row . ':M' . $end_row);
            $sheet->mergeCells('N' . $start_row . ':N' . $end_row);
            $sheet->mergeCells('O' . $start_row . ':O' . $end_row);
            $sheet->mergeCells('P' . $start_row . ':P' . $end_row);
        }
    }
}

// 열 너비 자동 조정
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(12);
$sheet->getColumnDimension('G')->setWidth(12);
$sheet->getColumnDimension('H')->setWidth(25);
$sheet->getColumnDimension('I')->setWidth(20);
$sheet->getColumnDimension('J')->setWidth(25);
$sheet->getColumnDimension('K')->setWidth(10);
$sheet->getColumnDimension('L')->setWidth(12);
$sheet->getColumnDimension('M')->setWidth(12);
$sheet->getColumnDimension('N')->setWidth(15);
$sheet->getColumnDimension('O')->setWidth(15);
$sheet->getColumnDimension('P')->setWidth(10);

// L 컬럼(가격)에 숫자 포맷 적용 (천 단위 쉼표)
$sheet->getStyle('L2:L' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

// M 컬럼(총 수리비)에 숫자 포맷 적용 (천 단위 쉼표)
$sheet->getStyle('M2:M' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

// 행 높이 설정
$sheet->getRowDimension('1')->setRowHeight(25);

// 파일명 생성
$filename = 'AS_Report_' . date('YmdHis') . '.xlsx';

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
