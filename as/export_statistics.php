<?php
header('Content-Type: text/html; charset=utf-8');
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

// ===== 데이터 조회 함수들 =====

// 판매 데이터 조회 (기간 범위 내)
function getSalesData($connect, $start_date, $end_date)
{
    $where_clause = (!empty($start_date) && !empty($end_date))
        ? "WHERE s20_sell_level = '2' AND DATE(s20_sell_out_date) BETWEEN '$start_date' AND '$end_date'"
        : "WHERE s20_sell_level = '2'";

    $query = "SELECT
        s20_sellid,
        s20_sell_out_no,
        DATE_FORMAT(s20_sell_in_date, '%y-%m-%d') as sell_date,
        s20_sell_out_no as receipt_no,
        ex_company,
        ex_sec1 as form,
        ex_address,
        ex_tel,
        ex_sms_no,
        s20_tax_code,
        s20_bankcheck_w,
        s20_total_cost
        FROM step20_sell
        $where_clause
        ORDER BY s20_sellid DESC";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 판매 자재 조회
function getSalesCartData($connect, $sellid)
{
    $query = "SELECT
        cost_name,
        s21_quantity,
        cost1,
        cost2,
        s21_sp_cost
        FROM step21_sell_cart
        WHERE s21_sellid = '$sellid'";

    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// 판매 총액 계산
function calculateSalesTotal($connect, $sellid)
{
    $query = "SELECT
        SUM(CASE
            WHEN s21_sp_cost = '' THEN s21_quantity * cost1
            ELSE s21_quantity * cost2
        END) as total
        FROM step21_sell_cart
        WHERE s21_sellid = '$sellid'";

    $result = mysql_query($query);
    $row = mysql_fetch_assoc($result);
    return intval($row['total'] ?? 0);
}

// 데이터 조회
$sales_data = getSalesData($connect, $start_date, $end_date);

// ===== XLSX 파일 생성 (OpenDocument Spreadsheet XML) =====

$filename = 'AS_판매내역_' . date('Y-m-d_H-i-s') . '.xlsx';

// ZIP 확장자로 생성할 임시 폴더
$temp_dir = '/tmp/xlsx_' . uniqid();
@mkdir($temp_dir, 0777, true);

// ===== [Content_Types].xml =====
$content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>';
@mkdir($temp_dir . '/_rels', 0777, true);
@mkdir($temp_dir . '/xl', 0777, true);
@mkdir($temp_dir . '/xl/worksheets', 0777, true);
@mkdir($temp_dir . '/docProps', 0777, true);
file_put_contents($temp_dir . '/[Content_Types].xml', $content_types);

// ===== .rels =====
$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
</Relationships>';
file_put_contents($temp_dir . '/_rels/.rels', $rels);

// ===== workbook.xml.rels =====
$workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';
@mkdir($temp_dir . '/xl/_rels', 0777, true);
file_put_contents($temp_dir . '/xl/_rels/workbook.xml.rels', $workbook_rels);

// ===== styles.xml =====
$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2">
<font><sz val="11"/><color theme="1"/><name val="Calibri"/></font>
<font><bold val="1"/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
</fonts>
<fills count="3">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF4472C4"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color indexed="64"/></left><right style="thin"><color indexed="64"/></right><top style="thin"><color indexed="64"/></top><bottom style="thin"><color indexed="64"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
</cellStyleXfs>
<cellXfs count="3">
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1"/>
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
</cellXfs>
</styleSheet>';
file_put_contents($temp_dir . '/xl/styles.xml', $styles);

// ===== core.xml =====
$core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/officeDocument/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>AS 판매내역</dc:title>
<dc:creator>AS System</dc:creator>
<cp:lastModifiedBy>AS System</cp:lastModifiedBy>
<dcterms:created xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:created>
<dcterms:modified xsi:type="dcterms:W3CDTF">' . date('Y-m-d\TH:i:s\Z') . '</dcterms:modified>
</cp:coreProperties>';
file_put_contents($temp_dir . '/docProps/core.xml', $core);

// ===== workbook.xml =====
$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="판매내역" sheetId="1" r:id="rId2"/>
</sheets>
</workbook>';
file_put_contents($temp_dir . '/xl/workbook.xml', $workbook);

// ===== sheet1.xml (메인 데이터) =====
$sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
$sheet .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
$sheet .= '<sheetData>' . "\n";

// 헤더 행
$sheet .= '<row r="1">' . "\n";
$sheet .= '  <c r="A1" s="1"><v>No</v></c>' . "\n";
$sheet .= '  <c r="B1" s="1"><v>판매일</v></c>' . "\n";
$sheet .= '  <c r="C1" s="1"><v>접수번호</v></c>' . "\n";
$sheet .= '  <c r="D1" s="1"><v>업체명</v></c>' . "\n";
$sheet .= '  <c r="E1" s="1"><v>형태</v></c>' . "\n";
$sheet .= '  <c r="F1" s="1"><v>부품명 | 개수 | 가격</v></c>' . "\n";
$sheet .= '  <c r="G1" s="1"><v>총 액</v></c>' . "\n";
$sheet .= '  <c r="H1" s="1"><v>주소</v></c>' . "\n";
$sheet .= '  <c r="I1" s="1"><v>연락처</v></c>' . "\n";
$sheet .= '  <c r="J1" s="1"><v>세금계산서발행</v></c>' . "\n";
$sheet .= '  <c r="K1" s="1"><v>결제방법</v></c>' . "\n";
$sheet .= '</row>' . "\n";

// 데이터 행
$row_num = 2;
$no = 1;
foreach ($sales_data as $sale) {
    $s1 = $no; // No
    $s2 = $sale['sell_date']; // 판매일
    $s3 = htmlspecialchars($sale['receipt_no'], ENT_XML1, 'UTF-8'); // 접수번호
    $s4 = htmlspecialchars($sale['ex_company'], ENT_XML1, 'UTF-8'); // 업체명
    $s5 = htmlspecialchars($sale['form'], ENT_XML1, 'UTF-8'); // 형태

    // 부품명 | 개수 | 가격
    $cart_data = getSalesCartData($connect, $sale['s20_sellid']);
    $parts_info = '';
    foreach ($cart_data as $item) {
        $cost = intval($item['s21_sp_cost']) == 0 ? intval($item['cost1']) : intval($item['cost2']);
        $total_item_cost = intval($item['s21_quantity']) * $cost;
        if (!empty($parts_info)) $parts_info .= ' | ';
        $parts_info .= htmlspecialchars($item['cost_name'], ENT_XML1, 'UTF-8') . ' | ' . intval($item['s21_quantity']) . '개 | ' . number_format($total_item_cost);
    }
    $parts_info = htmlspecialchars($parts_info, ENT_XML1, 'UTF-8');

    // 총액
    $s7 = calculateSalesTotal($connect, $sale['s20_sellid']);

    $s8 = htmlspecialchars($sale['ex_address'], ENT_XML1, 'UTF-8'); // 주소
    $s9 = htmlspecialchars($sale['ex_tel'] . '(' . $sale['ex_sms_no'] . ')', ENT_XML1, 'UTF-8'); // 연락처

    // 세금계산서
    $s10 = $sale['s20_tax_code'] == '' ? '미발행' : '발행';

    // 결제방법
    $s11 = '3월 8일 이후 확인가능';
    if ($sale['s20_bankcheck_w'] == 'center') {
        $s11 = '센터 현금납부';
    } elseif ($sale['s20_bankcheck_w'] == 'base') {
        $s11 = '계좌이체';
    }

    // XML 셀 작성 (이스케이프)
    $sheet .= '<row r="' . $row_num . '">' . "\n";
    $sheet .= '  <c r="A' . $row_num . '"><v>' . $s1 . '</v></c>' . "\n";
    $sheet .= '  <c r="B' . $row_num . '"><v>' . $s2 . '</v></c>' . "\n";
    $sheet .= '  <c r="C' . $row_num . '"><v>' . $s3 . '</v></c>' . "\n";
    $sheet .= '  <c r="D' . $row_num . '"><v>' . $s4 . '</v></c>' . "\n";
    $sheet .= '  <c r="E' . $row_num . '"><v>' . $s5 . '</v></c>' . "\n";
    $sheet .= '  <c r="F' . $row_num . '"><v>' . $parts_info . '</v></c>' . "\n";
    $sheet .= '  <c r="G' . $row_num . '"><v>' . $s7 . '</v></c>' . "\n";
    $sheet .= '  <c r="H' . $row_num . '"><v>' . $s8 . '</v></c>' . "\n";
    $sheet .= '  <c r="I' . $row_num . '"><v>' . $s9 . '</v></c>' . "\n";
    $sheet .= '  <c r="J' . $row_num . '"><v>' . $s10 . '</v></c>' . "\n";
    $sheet .= '  <c r="K' . $row_num . '"><v>' . $s11 . '</v></c>' . "\n";
    $sheet .= '</row>' . "\n";

    $row_num++;
    $no++;
}

$sheet .= '</sheetData>' . "\n";
$sheet .= '<mergeCells count="0"/>' . "\n";
$sheet .= '<pageMargins left="0.7" top="0.75" right="0.7" bottom="0.75" header="0.3" footer="0.3"/>' . "\n";
$sheet .= '</worksheet>';

file_put_contents($temp_dir . '/xl/worksheets/sheet1.xml', $sheet);

// ===== ZIP 생성 =====
function createZip($source_dir, $destination) {
    $zip = new ZipArchive();
    if ($zip->open($destination, ZipArchive::CREATE) !== true) {
        return false;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($source_dir) + 1);
            $zip->addFile($file_path, $relative_path);
        }
    }

    $zip->close();
    return true;
}

// XLSX 파일 생성 (ZIP 형식)
$xlsx_file = $temp_dir . '/../' . $filename;
createZip($temp_dir, $xlsx_file);

// 다운로드 헤더
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($xlsx_file));
header('Cache-Control: max-age=0');

// 파일 전송
readfile($xlsx_file);

// 임시 파일 삭제
@unlink($xlsx_file);
array_walk_recursive(glob($temp_dir . '/*'), function($file) {
    is_file($file) && @unlink($file);
});
@rmdir($temp_dir . '/xl/worksheets');
@rmdir($temp_dir . '/xl/_rels');
@rmdir($temp_dir . '/xl');
@rmdir($temp_dir . '/_rels');
@rmdir($temp_dir . '/docProps');
@rmdir($temp_dir);

mysql_close($connect);
?>
