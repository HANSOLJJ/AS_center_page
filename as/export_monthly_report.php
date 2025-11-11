<?php
/**
 * 월간 종합 리포트 XLSX 내보내기
 *
 * as_statistics.php의 월간 리포트 탭에서 호출되는 월별 종합 리포트 생성 파일
 * PhpSpreadsheet 라이브러리를 사용하여 XLSX 형식으로 내보냄
 * 2개 시트: AS 리포트, 판매 리포트
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

// 파라미터
$report_year = isset($_GET['report_year']) ? intval($_GET['report_year']) : date('Y');
$report_month = isset($_GET['report_month']) ? $_GET['report_month'] : date('m');

// 년월 검증
if ($report_month < 1 || $report_month > 12) {
    $report_month = date('m');
}

$start_date = $report_year . '-' . $report_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

// 스프레드시트 생성
$spreadsheet = new Spreadsheet();

// ========================================
// Sheet 1: AS 리포트
// ========================================
$sheet_as = $spreadsheet->getActiveSheet();
$sheet_as->setTitle('AS 리포트');

// AS 데이터 조회 (JOIN으로 한 번에)
$as_where = "WHERE a.s13_as_level = '5' AND DATE(a.s13_as_out_date) BETWEEN '$start_date' AND '$end_date'";

$as_query = "SELECT
    a.s13_asid,
    a.s13_as_out_no,
    DATE_FORMAT(a.s13_as_out_date, '%Y-%m-%d %H:%i') as as_out_date,
    DATE_FORMAT(a.s13_as_in_date, '%Y-%m-%d') as as_in_date,
    a.s13_as_in_how,
    a.ex_company,
    a.ex_sec1,
    COALESCE(a.ex_address, '') as ex_address,
    COALESCE(a.ex_tel, '') as ex_tel,
    a.s13_total_cost,
    a.s13_bank_check,
    a.s13_bankcheck_w,
    a.s13_as_level,
    i.s14_aiid,
    i.cost_name as item_cost_name,
    i.as_end_result,
    c.s18_accid,
    c.cost_name as part_cost_name,
    c.s18_quantity,
    c.cost1
FROM step13_as a
LEFT JOIN step14_as_item i ON a.s13_asid = i.s14_asid
LEFT JOIN step18_as_cure_cart c ON i.s14_aiid = c.s18_aiid
$as_where
ORDER BY a.s13_asid, i.s14_aiid, c.s18_accid";

$as_result = mysql_query($as_query);

if (!$as_result) {
    die("오류: " . mysql_error());
}

// 메모리에 데이터 로드
$as_data_array = array();
while ($row = mysql_fetch_assoc($as_result)) {
    $asid = $row['s13_asid'];
    if (!isset($as_data_array[$asid])) {
        $as_data_array[$asid] = array(
            'master' => $row,
            'parts' => array()
        );
    }
    if (!empty($row['s18_accid'])) {
        $as_data_array[$asid]['parts'][] = $row;
    }
}

// AS 헤더
$as_headers = array('No', '완료일', '수탁방법', '접수번호', '업체명', '형태', '입고일', '제품명', '처리내역', '부품명', '수량', '가격', '총액', '주소', '연락처', '세금계산서', '결제방법');
$col = 'A';
foreach ($as_headers as $header) {
    $sheet_as->setCellValue($col . '1', $header);
    $col++;
}

// AS 헤더 스타일
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet_as->getStyle('A1:Q1')->applyFromArray($headerStyle);

// AS 데이터 행 작성
$row = 2;
$no = 0;

foreach ($as_data_array as $asid => $data) {
    $no++;
    $as = $data['master'];
    $parts = $data['parts'];
    $parts_count = count($parts);

    // 결제방법 로직
    $payment_method = '';
    if ($as['s13_bankcheck_w'] === 'center') {
        $payment_method = '센터 현금납부';
    } elseif ($as['s13_bankcheck_w'] === 'base') {
        $payment_method = '계좌이체';
    } else {
        $payment_method = '3월 8일 이후 확인가능';
    }

    // 세금계산서
    $tax_display = $as['s13_bank_check'] ? '발행' : '미발행';

    // 제품명/처리내역
    $product_name = $as['item_cost_name'] ?? '';
    $end_result = $as['as_end_result'] ?? '';

    if ($parts_count === 0) {
        // 부품이 없으면 마스터 정보만 출력
        $sheet_as->setCellValue('A' . $row, $no);
        $sheet_as->setCellValue('B' . $row, $as['as_out_date']);
        $sheet_as->setCellValue('C' . $row, $as['s13_as_in_how'] ?? '');
        $sheet_as->setCellValue('D' . $row, $as['s13_as_out_no'] ?? '');
        $sheet_as->setCellValue('E' . $row, $as['ex_company'] ?? '');
        $sheet_as->setCellValue('F' . $row, $as['ex_sec1'] ?? '');
        $sheet_as->setCellValue('G' . $row, $as['as_in_date']);
        $sheet_as->setCellValue('H' . $row, $product_name);
        $sheet_as->setCellValue('I' . $row, $end_result);
        $sheet_as->setCellValue('J' . $row, '');
        $sheet_as->setCellValue('K' . $row, '');
        $sheet_as->setCellValue('L' . $row, '');
        $sheet_as->setCellValue('M' . $row, isset($as['s13_total_cost']) ? (int) $as['s13_total_cost'] : '');
        $sheet_as->setCellValue('N' . $row, $as['ex_address'] ?? '');
        $sheet_as->setCellValue('O' . $row, $as['ex_tel']);
        $sheet_as->setCellValue('P' . $row, $tax_display);
        $sheet_as->setCellValue('Q' . $row, $payment_method);

        // 범위 기반 스타일 적용
        $centerStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet_as->getStyle('A' . $row . ':Q' . $row)->applyFromArray($centerStyle);

        $row++;
    } else {
        // 부품별로 줄 작성
        $start_row = $row;
        $is_first = true;
        foreach ($parts as $part) {
            $part_price = (isset($part['s18_quantity']) && isset($part['cost1']))
                ? ($part['s18_quantity'] * $part['cost1'])
                : 0;

            $sheet_as->setCellValue('A' . $row, $is_first ? $no : '');
            $sheet_as->setCellValue('B' . $row, $is_first ? $as['as_out_date'] : '');
            $sheet_as->setCellValue('C' . $row, $is_first ? ($as['s13_as_in_how'] ?? '') : '');
            $sheet_as->setCellValue('D' . $row, $is_first ? ($as['s13_as_out_no'] ?? '') : '');
            $sheet_as->setCellValue('E' . $row, $is_first ? ($as['ex_company'] ?? '') : '');
            $sheet_as->setCellValue('F' . $row, $is_first ? ($as['ex_sec1'] ?? '') : '');
            $sheet_as->setCellValue('G' . $row, $is_first ? $as['as_in_date'] : '');
            $sheet_as->setCellValue('H' . $row, $is_first ? $product_name : '');
            $sheet_as->setCellValue('I' . $row, $is_first ? $end_result : '');
            $sheet_as->setCellValue('J' . $row, $part['part_cost_name'] ?? '');
            $sheet_as->setCellValue('K' . $row, (isset($part['s18_quantity']) ? $part['s18_quantity'] . '개' : ''));
            $sheet_as->setCellValue('L' . $row, (int) $part_price);
            $sheet_as->setCellValue('M' . $row, $is_first ? (isset($as['s13_total_cost']) ? (int) $as['s13_total_cost'] : '') : '');
            $sheet_as->setCellValue('N' . $row, $is_first ? ($as['ex_address'] ?? '') : '');
            $sheet_as->setCellValue('O' . $row, $is_first ? $as['ex_tel'] : '');
            $sheet_as->setCellValue('P' . $row, $is_first ? $tax_display : '');
            $sheet_as->setCellValue('Q' . $row, $is_first ? $payment_method : '');

            $is_first = false;
            $row++;
        }

        // 범위 기반 스타일 적용
        $end_row = $row - 1;
        $centerStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $leftStyle = [
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $sheet_as->getStyle('A' . $start_row . ':Q' . $end_row)->applyFromArray($centerStyle);
        $sheet_as->getStyle('J' . $start_row . ':J' . $end_row)->applyFromArray($leftStyle);

        // 셀 merge
        if ($parts_count > 1) {
            $sheet_as->mergeCells('A' . $start_row . ':A' . $end_row);
            $sheet_as->mergeCells('B' . $start_row . ':B' . $end_row);
            $sheet_as->mergeCells('C' . $start_row . ':C' . $end_row);
            $sheet_as->mergeCells('D' . $start_row . ':D' . $end_row);
            $sheet_as->mergeCells('E' . $start_row . ':E' . $end_row);
            $sheet_as->mergeCells('F' . $start_row . ':F' . $end_row);
            $sheet_as->mergeCells('G' . $start_row . ':G' . $end_row);
            $sheet_as->mergeCells('H' . $start_row . ':H' . $end_row);
            $sheet_as->mergeCells('I' . $start_row . ':I' . $end_row);
            $sheet_as->mergeCells('M' . $start_row . ':M' . $end_row);
            $sheet_as->mergeCells('N' . $start_row . ':N' . $end_row);
            $sheet_as->mergeCells('O' . $start_row . ':O' . $end_row);
            $sheet_as->mergeCells('P' . $start_row . ':P' . $end_row);
            $sheet_as->mergeCells('Q' . $start_row . ':Q' . $end_row);
        }
    }
}

// AS 열 너비
$sheet_as->getColumnDimension('A')->setWidth(8);
$sheet_as->getColumnDimension('B')->setWidth(12);
$sheet_as->getColumnDimension('C')->setWidth(12);
$sheet_as->getColumnDimension('D')->setWidth(15);
$sheet_as->getColumnDimension('E')->setWidth(30);
$sheet_as->getColumnDimension('F')->setWidth(12);
$sheet_as->getColumnDimension('G')->setWidth(12);
$sheet_as->getColumnDimension('H')->setWidth(20);
$sheet_as->getColumnDimension('I')->setWidth(15);
$sheet_as->getColumnDimension('J')->setWidth(44);
$sheet_as->getColumnDimension('K')->setWidth(10);
$sheet_as->getColumnDimension('L')->setWidth(12);
$sheet_as->getColumnDimension('M')->setWidth(12);
$sheet_as->getColumnDimension('N')->setWidth(20);
$sheet_as->getColumnDimension('O')->setWidth(15);
$sheet_as->getColumnDimension('P')->setWidth(12);
$sheet_as->getColumnDimension('Q')->setWidth(15);

// AS 숫자 포맷
$sheet_as->getStyle('L2:L' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
$sheet_as->getStyle('M2:M' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

// ========================================
// Sheet 2: 판매 리포트
// ========================================
$sheet_sales = $spreadsheet->createSheet();
$sheet_sales->setTitle('판매 리포트');

// 판매 데이터 조회 (JOIN으로 한 번에)
$sales_where = "WHERE s.s20_sell_level = '2' AND DATE(s.s20_sell_out_date) BETWEEN '$start_date' AND '$end_date'";

$sales_query = "SELECT
    s.s20_sellid,
    s.s20_sell_out_no,
    DATE_FORMAT(s.s20_sell_out_date, '%Y-%m-%d %H:%i') as sell_out_date,
    s.ex_company,
    s.ex_sec1,
    COALESCE(s.ex_address, '') as ex_address,
    COALESCE(s.ex_tel, '') as ex_tel,
    s.s20_total_cost,
    s.s20_tax_code,
    s.s20_bankcheck_w,
    c.s21_accid,
    c.cost_name,
    c.s21_quantity,
    c.cost1
FROM step20_sell s
LEFT JOIN step21_sell_cart c ON s.s20_sellid = c.s21_sellid
$sales_where
ORDER BY s.s20_sellid, c.s21_accid";

$sales_result = mysql_query($sales_query);

if (!$sales_result) {
    die("오류: " . mysql_error());
}

// 메모리에 데이터 로드
$sales_data_array = array();
while ($row = mysql_fetch_assoc($sales_result)) {
    $sellid = $row['s20_sellid'];
    if (!isset($sales_data_array[$sellid])) {
        $sales_data_array[$sellid] = array(
            'master' => $row,
            'items' => array()
        );
    }
    if (!empty($row['s21_accid'])) {
        $sales_data_array[$sellid]['items'][] = $row;
    }
}

// 판매 헤더
$sales_headers = array('No', '판매일', '접수번호', '업체명', '형태', '자재명', '수량', '가격', '총액', '주소', '연락처', '세금계산서', '결제방법');
$col = 'A';
foreach ($sales_headers as $header) {
    $sheet_sales->setCellValue($col . '1', $header);
    $col++;
}

// 판매 헤더 스타일
$sheet_sales->getStyle('A1:M1')->applyFromArray($headerStyle);

// 판매 데이터 행 작성
$row = 2;
$no = 0;

foreach ($sales_data_array as $sellid => $data) {
    $no++;
    $sale = $data['master'];
    $items = $data['items'];
    $cart_count = count($items);

    // 결제방법
    $payment_method = '';
    if ($sale['s20_bankcheck_w'] === 'center') {
        $payment_method = '센터 현금납부';
    } elseif ($sale['s20_bankcheck_w'] === 'base') {
        $payment_method = '계좌이체';
    } else {
        $payment_method = '3월 8일 이후 확인가능';
    }

    // 세금계산서
    $tax_display = $sale['s20_tax_code'] ? '발행' : '미발행';

    if ($cart_count === 0) {
        $sheet_sales->setCellValue('A' . $row, $no);
        $sheet_sales->setCellValue('B' . $row, substr($sale['sell_out_date'], 0, 10));
        $sheet_sales->setCellValue('C' . $row, $sale['s20_sell_out_no'] ?? '');
        $sheet_sales->setCellValue('D' . $row, $sale['ex_company'] ?? '');
        $sheet_sales->setCellValue('E' . $row, $sale['ex_sec1'] ?? '');
        $sheet_sales->setCellValue('F' . $row, '');
        $sheet_sales->setCellValue('G' . $row, '');
        $sheet_sales->setCellValue('H' . $row, '');
        $sheet_sales->setCellValue('I' . $row, isset($sale['s20_total_cost']) ? (int) $sale['s20_total_cost'] : '');
        $sheet_sales->setCellValue('J' . $row, $sale['ex_address'] ?? '');
        $sheet_sales->setCellValue('K' . $row, $sale['ex_tel'] ?? '');
        $sheet_sales->setCellValue('L' . $row, $tax_display);
        $sheet_sales->setCellValue('M' . $row, $payment_method);

        $centerStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet_sales->getStyle('A' . $row . ':M' . $row)->applyFromArray($centerStyle);

        $row++;
    } else {
        $start_row = $row;
        $is_first = true;
        foreach ($items as $cart) {
            $item_price = (isset($cart['s21_quantity']) && isset($cart['cost1']))
                ? ($cart['s21_quantity'] * $cart['cost1'])
                : 0;

            $sheet_sales->setCellValue('A' . $row, $is_first ? $no : '');
            $sheet_sales->setCellValue('B' . $row, $is_first ? substr($sale['sell_out_date'], 0, 10) : '');
            $sheet_sales->setCellValue('C' . $row, $is_first ? ($sale['s20_sell_out_no'] ?? '') : '');
            $sheet_sales->setCellValue('D' . $row, $is_first ? ($sale['ex_company'] ?? '') : '');
            $sheet_sales->setCellValue('E' . $row, $is_first ? ($sale['ex_sec1'] ?? '') : '');
            $sheet_sales->setCellValue('F' . $row, $cart['cost_name'] ?? '');
            $sheet_sales->setCellValue('G' . $row, (isset($cart['s21_quantity']) ? $cart['s21_quantity'] . '개' : ''));
            $sheet_sales->setCellValue('H' . $row, (int) $item_price);
            $sheet_sales->setCellValue('I' . $row, $is_first ? (isset($sale['s20_total_cost']) ? (int) $sale['s20_total_cost'] : '') : '');
            $sheet_sales->setCellValue('J' . $row, $is_first ? ($sale['ex_address'] ?? '') : '');
            $sheet_sales->setCellValue('K' . $row, $is_first ? ($sale['ex_tel'] ?? '') : '');
            $sheet_sales->setCellValue('L' . $row, $is_first ? $tax_display : '');
            $sheet_sales->setCellValue('M' . $row, $is_first ? $payment_method : '');

            $is_first = false;
            $row++;
        }

        $end_row = $row - 1;
        $centerStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $leftStyle = [
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $sheet_sales->getStyle('A' . $start_row . ':M' . $end_row)->applyFromArray($centerStyle);
        $sheet_sales->getStyle('F' . $start_row . ':F' . $end_row)->applyFromArray($leftStyle);

        if ($cart_count > 1) {
            $sheet_sales->mergeCells('A' . $start_row . ':A' . $end_row);
            $sheet_sales->mergeCells('B' . $start_row . ':B' . $end_row);
            $sheet_sales->mergeCells('C' . $start_row . ':C' . $end_row);
            $sheet_sales->mergeCells('D' . $start_row . ':D' . $end_row);
            $sheet_sales->mergeCells('E' . $start_row . ':E' . $end_row);
            $sheet_sales->mergeCells('I' . $start_row . ':I' . $end_row);
            $sheet_sales->mergeCells('J' . $start_row . ':J' . $end_row);
            $sheet_sales->mergeCells('K' . $start_row . ':K' . $end_row);
            $sheet_sales->mergeCells('L' . $start_row . ':L' . $end_row);
            $sheet_sales->mergeCells('M' . $start_row . ':M' . $end_row);
        }
    }
}

// 판매 열 너비
$sheet_sales->getColumnDimension('A')->setWidth(8);
$sheet_sales->getColumnDimension('B')->setWidth(12);
$sheet_sales->getColumnDimension('C')->setWidth(18);
$sheet_sales->getColumnDimension('D')->setWidth(30);
$sheet_sales->getColumnDimension('E')->setWidth(12);
$sheet_sales->getColumnDimension('F')->setWidth(44);
$sheet_sales->getColumnDimension('G')->setWidth(10);
$sheet_sales->getColumnDimension('H')->setWidth(12);
$sheet_sales->getColumnDimension('I')->setWidth(12);
$sheet_sales->getColumnDimension('J')->setWidth(20);
$sheet_sales->getColumnDimension('K')->setWidth(15);
$sheet_sales->getColumnDimension('L')->setWidth(15);
$sheet_sales->getColumnDimension('M')->setWidth(15);

// 판매 숫자 포맷
$sheet_sales->getStyle('H2:H' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
$sheet_sales->getStyle('I2:I' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

// 파일명 생성
$filename = 'Monthly_Report_' . $report_year . '_' . $report_month . '_' . date('His') . '.xlsx';

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
