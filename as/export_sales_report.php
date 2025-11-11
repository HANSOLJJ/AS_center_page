<?php
/**
 * 판매 리포트 XLSX 내보내기
 *
 * as_statistics.php에서 호출되는 판매 완료 데이터 리포트 생성 파일
 * PhpSpreadsheet 라이브러리를 사용하여 XLSX 형식으로 내보냄
 * 날짜 범위 필터링 지원 (GET 파라미터: start_date, end_date, range)
 * 자재별 자동 줄 분리 (하나의 판매에 여러 자재가 있을 경우)
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
$range = isset($_GET['range']) ? $_GET['range'] : '';

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

// 판매 완료 데이터 조회 (JOIN으로 한 번에 모든 데이터 가져오기)
$where_clause = '';
if (!empty($start_date) && !empty($end_date)) {
    $where_clause = " AND DATE(s.s20_sell_out_date) BETWEEN '" . mysql_real_escape_string($start_date) . "'
                    AND '" . mysql_real_escape_string($end_date) . "'";
}

// 모든 데이터를 한 번에 JOIN으로 조회 (N+1 쿼리 문제 해결)
$query = "SELECT
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
WHERE s.s20_sell_level = '2'
$where_clause
ORDER BY s.s20_sellid, c.s21_accid";

$result = mysql_query($query);

if (!$result) {
    die("오류: " . mysql_error());
}

// 메모리에 모든 데이터 로드 및 구조화
$data_array = array();
while ($row = mysql_fetch_assoc($result)) {
    $sellid = $row['s20_sellid'];
    if (!isset($data_array[$sellid])) {
        $data_array[$sellid] = array(
            'master' => $row,
            'items' => array()
        );
    }
    if (!empty($row['s21_accid'])) {
        $data_array[$sellid]['items'][] = $row;
    }
}

// 스프레드시트 생성
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('판매 리포트');

// 헤더 작성
$headers = array(
    'No',
    '판매일',
    '접수번호',
    '업체명',
    '형태',
    '자재명',
    '수량',
    '가격',
    '총액',
    '주소',
    '연락처',
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
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

// 데이터 행 작성 (미리 로드된 배열 사용)
$row = 2;
$row_count = 0;
$no = 0;

foreach ($data_array as $sellid => $data) {
    $no++;
    $sale = $data['master'];
    $items = $data['items'];
    $cart_count = count($items);

    // 결제방법 로직
    $payment_method = '';
    if ($sale['s20_bankcheck_w'] === 'center') {
        $payment_method = '센터 현금납부';
    } elseif ($sale['s20_bankcheck_w'] === 'base') {
        $payment_method = '계좌이체';
    } else {
        $payment_method = '3월 8일 이후 확인가능';
    }

    if ($cart_count === 0) {
        // 자재가 없으면 마스터 정보만 출력
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, substr($sale['sell_out_date'], 0, 10));
        $sheet->setCellValue('C' . $row, $sale['s20_sell_out_no'] ?? '');
        $sheet->setCellValue('D' . $row, $sale['ex_company'] ?? '');
        $sheet->setCellValue('E' . $row, $sale['ex_sec1'] ?? '');
        $sheet->setCellValue('F' . $row, '');
        $sheet->setCellValue('G' . $row, '');
        $sheet->setCellValue('H' . $row, '');
        $sheet->setCellValue('I' . $row, isset($sale['s20_total_cost']) ? (int) $sale['s20_total_cost'] : '');
        $sheet->setCellValue('J' . $row, $sale['ex_address'] ?? '');
        $sheet->setCellValue('K' . $row, $sale['ex_tel'] ?? '');
        $sheet->setCellValue('L' . $row, $sale['s20_tax_code'] ? '발행' : '미발행');
        $sheet->setCellValue('M' . $row, $payment_method);

        // 데이터 셀 스타일
        $dataStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($dataStyle);

        $row++;
        $row_count++;
    } else {
        // 자재별로 줄 작성
        $start_row = $row;
        $is_first = true;
        foreach ($items as $cart) {
            // 자재별 가격 계산 (수량 * 단가)
            $item_price = (isset($cart['s21_quantity']) && isset($cart['cost1']))
                ? ($cart['s21_quantity'] * $cart['cost1'])
                : 0;

            $sheet->setCellValue('A' . $row, $is_first ? $no : '');
            $sheet->setCellValue('B' . $row, $is_first ? substr($sale['sell_out_date'], 0, 10) : '');
            $sheet->setCellValue('C' . $row, $is_first ? ($sale['s20_sell_out_no'] ?? '') : '');
            $sheet->setCellValue('D' . $row, $is_first ? ($sale['ex_company'] ?? '') : '');
            $sheet->setCellValue('E' . $row, $is_first ? ($sale['ex_sec1'] ?? '') : '');
            $sheet->setCellValue('F' . $row, $cart['cost_name'] ?? '');
            $sheet->setCellValue('G' . $row, (isset($cart['s21_quantity']) ? $cart['s21_quantity'] . '개' : ''));
            $sheet->setCellValue('H' . $row, (int) $item_price);
            $sheet->setCellValue('I' . $row, $is_first ? (isset($sale['s20_total_cost']) ? (int) $sale['s20_total_cost'] : '') : '');
            $sheet->setCellValue('J' . $row, $is_first ? ($sale['ex_address'] ?? '') : '');
            $sheet->setCellValue('K' . $row, $is_first ? ($sale['ex_tel'] ?? '') : '');
            $sheet->setCellValue('L' . $row, $is_first ? ($sale['s20_tax_code'] ? '발행' : '미발행') : '');
            $sheet->setCellValue('M' . $row, $is_first ? $payment_method : '');

            $is_first = false;
            $row++;
            $row_count++;
        }

        // 범위 기반 스타일 적용 (루프 후 한 번에 처리) - 최적화
        $end_row = $row - 1;
        $centerStyle = [
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $leftStyle = [
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        // 전체 범위에 center 정렬 적용
        $sheet->getStyle('A' . $start_row . ':M' . $end_row)->applyFromArray($centerStyle);

        // F 컬럼만 left 정렬로 덮어씌우기
        $sheet->getStyle('F' . $start_row . ':F' . $end_row)->applyFromArray($leftStyle);

        // 셀 merge (A, B, C, D, E, I, J, K, L, M 컬럼)
        if ($cart_count > 1) {
            $sheet->mergeCells('A' . $start_row . ':A' . $end_row);
            $sheet->mergeCells('B' . $start_row . ':B' . $end_row);
            $sheet->mergeCells('C' . $start_row . ':C' . $end_row);
            $sheet->mergeCells('D' . $start_row . ':D' . $end_row);
            $sheet->mergeCells('E' . $start_row . ':E' . $end_row);
            $sheet->mergeCells('I' . $start_row . ':I' . $end_row);
            $sheet->mergeCells('J' . $start_row . ':J' . $end_row);
            $sheet->mergeCells('K' . $start_row . ':K' . $end_row);
            $sheet->mergeCells('L' . $start_row . ':L' . $end_row);
            $sheet->mergeCells('M' . $start_row . ':M' . $end_row);
        }
    }
}

// 열 너비 자동 조정
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(18);
$sheet->getColumnDimension('D')->setWidth(30);
$sheet->getColumnDimension('E')->setWidth(12);
$sheet->getColumnDimension('F')->setWidth(44);
$sheet->getColumnDimension('G')->setWidth(10);
$sheet->getColumnDimension('H')->setWidth(12);
$sheet->getColumnDimension('I')->setWidth(12);
$sheet->getColumnDimension('J')->setWidth(20);
$sheet->getColumnDimension('K')->setWidth(15);
$sheet->getColumnDimension('L')->setWidth(15);
$sheet->getColumnDimension('M')->setWidth(15);

// H, I 컬럼에 숫자 포맷 적용 (천 단위 쉼표)
$sheet->getStyle('H2:H' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle('I2:I' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

// 행 높이 설정
$sheet->getRowDimension('1')->setRowHeight(25);

// 파일명 생성
$filename = 'Sales_Report_' . date('YmdHis') . '.xlsx';

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