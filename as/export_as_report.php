<?php
/**
 * AS 리포트 XLSX 내보내기
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
    $where_clause = " WHERE a.s13_as_level = '5'
                    AND DATE(a.s13_as_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
                    AND '" . mysql_real_escape_string($end_date) . "'";
} else {
    $where_clause = " WHERE a.s13_as_level = '5'";
}

$query = "SELECT
    a.s13_asid,
    DATE_FORMAT(a.s13_as_out_date, '%Y-%m-%d') as as_out_date,
    a.ex_company,
    a.ex_sec1,
    a.ex_address,
    a.s13_bankcheck_w,
    a.s20_tax_code,
    a.ex_total_cost
FROM step13_as a
$where_clause
ORDER BY a.s13_asid DESC";

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
    '판매일',
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
$no = 1;

while ($as = mysql_fetch_assoc($result)) {
    $asid = $as['s13_asid'];
    $as_out_date = $as['as_out_date'] ?? '';
    $ex_company = $as['ex_company'] ?? '';
    $ex_sec1 = $as['ex_sec1'] ?? '';
    $ex_address = $as['ex_address'] ?? '';
    $ex_total_cost = $as['ex_total_cost'] ?? '';
    
    // 세금계산서 여부
    $tax_code = isset($as['s20_tax_code']) && $as['s20_tax_code'] ? '발행' : '미발행';
    
    // 결제방법
    $payment_method = '확인중';
    if (isset($as['s13_bankcheck_w'])) {
        if ($as['s13_bankcheck_w'] === 'center') {
            $payment_method = '센터 현금';
        } elseif ($as['s13_bankcheck_w'] === 'base') {
            $payment_method = '계좌이체';
        }
    }
    
    // AS 자재 조회
    $item_query = "SELECT
        s14_aiid,
        cost_name,
        s14_cart
        FROM step14_as_item
        WHERE s14_asid = '" . mysql_real_escape_string($asid) . "'
        ORDER BY s14_aiid ASC";
    
    $item_result = mysql_query($item_query);
    
    if (!$item_result || mysql_num_rows($item_result) === 0) {
        // 자재가 없으면 마스터 정보만 출력
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, $as_out_date);
        $sheet->setCellValue('C' . $row, $ex_company);
        $sheet->setCellValue('D' . $row, $ex_sec1);
        $sheet->setCellValue('E' . $row, '');
        $sheet->setCellValue('F' . $row, '');
        $sheet->setCellValue('G' . $row, $ex_total_cost);
        $sheet->setCellValue('H' . $row, $ex_address);
        $sheet->setCellValue('I' . $row, $tax_code);
        $sheet->setCellValue('J' . $row, $payment_method);
        
        // 데이터 셀 스타일
        $dataStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);
        
        $row++;
        $no++;
    } else {
        // 자재별로 줄 작성
        $is_first = true;
        $item_row_start = $row;
        
        while ($item = mysql_fetch_assoc($item_result)) {
            if ($is_first) {
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, $as_out_date);
                $sheet->setCellValue('C' . $row, $ex_company);
                $sheet->setCellValue('D' . $row, $ex_sec1);
            }
            
            $sheet->setCellValue('E' . $row, $item['cost_name'] ?? '');
            $sheet->setCellValue('F' . $row, $item['s14_cart'] ?? '');
            
            if ($is_first) {
                $sheet->setCellValue('G' . $row, $ex_total_cost);
                $sheet->setCellValue('H' . $row, $ex_address);
                $sheet->setCellValue('I' . $row, $tax_code);
                $sheet->setCellValue('J' . $row, $payment_method);
            }
            
            $row++;
            $is_first = false;
        }
        
        // Merge cells for non-item columns
        if ($row - 1 > $item_row_start) {
            // Merge A column (No)
            $sheet->mergeCells('A' . $item_row_start . ':A' . ($row - 1));
            // Merge B column (판매일)
            $sheet->mergeCells('B' . $item_row_start . ':B' . ($row - 1));
            // Merge C column (업체명)
            $sheet->mergeCells('C' . $item_row_start . ':C' . ($row - 1));
            // Merge D column (형태)
            $sheet->mergeCells('D' . $item_row_start . ':D' . ($row - 1));
            // Merge G column (총액)
            $sheet->mergeCells('G' . $item_row_start . ':G' . ($row - 1));
            // Merge H column (주소)
            $sheet->mergeCells('H' . $item_row_start . ':H' . ($row - 1));
            // Merge I column (세금계산서)
            $sheet->mergeCells('I' . $item_row_start . ':I' . ($row - 1));
            // Merge J column (결제방법)
            $sheet->mergeCells('J' . $item_row_start . ':J' . ($row - 1));
        }
        
        // Apply styles to all rows for this AS
        for ($i = $item_row_start; $i < $row; $i++) {
            $dataStyle = [
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A' . $i . ':J' . $i)->applyFromArray($dataStyle);
        }
        
        $no++;
    }
}

// 열 너비 자동 조정
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(12);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(10);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(25);
$sheet->getColumnDimension('I')->setWidth(12);
$sheet->getColumnDimension('J')->setWidth(15);

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
