<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../db_config.php';

$sellid = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($sellid <= 0) {
    die('유효하지 않은 영수증 번호입니다.');
}

// 주문 정보 조회 - 원본 set2.php와 동일한 필드들
$order_result = @mysql_query("
    SELECT s20_sellid, s20_sell_out_no, s20_sell_center, s20_sell_in_date, s20_total_cost,
           s20_bank_check, s20_tax_code, s20_sell_out_date, s20_sell_level, s20_sell_name1,
           s20_meid, ex_tel, ex_sms_no, ex_sec1, ex_sec2, ex_company, ex_man, ex_address,
           ex_address_no, ex_company_no, s20_bankcheck_w
    FROM step20_sell
    WHERE s20_sellid = $sellid
    LIMIT 1
");

if (!$order_result || mysql_num_rows($order_result) == 0) {
    die('해당 영수증을 찾을 수 없습니다.');
}

$order = mysql_fetch_assoc($order_result);

// 날짜 포맷팅 (원본과 동일하게)
// s20_sell_in_date 처리 (접수일자)
$sell_in_date = '';
if (!empty($order['s20_sell_in_date'])) {
    if (is_numeric($order['s20_sell_in_date']) && intval($order['s20_sell_in_date']) > 86400) {
        // 유효한 유닉스 타임스탐프 (최소 1970-02-01 이후)
        $sell_in_date = date("Y년 m월 d일", intval($order['s20_sell_in_date']));
    } else {
        // 문자열 datetime 형식
        $timestamp = strtotime($order['s20_sell_in_date']);
        if ($timestamp !== false && $timestamp > 0) {
            $sell_in_date = date("Y년 m월 d일", $timestamp);
        } else {
            // strtotime 실패 시 원본 값 그대로 표시
            $sell_in_date = htmlspecialchars($order['s20_sell_in_date']);
        }
    }
}

// s20_sell_out_date 처리 (A/S 처리완료일)
$out_date = '';
if (!empty($order['s20_sell_out_date'])) {
    if (is_numeric($order['s20_sell_out_date']) && intval($order['s20_sell_out_date']) > 86400) {
        // 유효한 유닉스 타임스탐프 (최소 1970-02-01 이후)
        $out_date = date("Y년 m월 d일", intval($order['s20_sell_out_date']));
    } else {
        // 문자열 datetime 형식
        $timestamp = strtotime($order['s20_sell_out_date']);
        if ($timestamp !== false && $timestamp > 0) {
            $out_date = date("Y년 m월 d일", $timestamp);
        } else {
            // strtotime 실패 시 원본 값 그대로 표시
            $out_date = htmlspecialchars($order['s20_sell_out_date']);
        }
    }
}

// s20_bank_check 처리 (입금일자)
$bank_check = '';
if (!empty($order['s20_bank_check'])) {
    if (is_numeric($order['s20_bank_check']) && intval($order['s20_bank_check']) > 86400) {
        // 유효한 유닉스 타임스탐프 (최소 1970-02-01 이후)
        $bank_check = date("Y년 m월 d일", intval($order['s20_bank_check']));
    } else {
        // 문자열 datetime 형식
        $timestamp = strtotime($order['s20_bank_check']);
        if ($timestamp !== false && $timestamp > 0) {
            $bank_check = date("Y년 m월 d일", $timestamp);
        } else {
            // strtotime 실패 시 원본 값 그대로 표시
            $bank_check = htmlspecialchars($order['s20_bank_check']);
        }
    }
}

// 세금계산서 발행 여부
$tax_on = (isset($order['s20_tax_code']) && $order['s20_tax_code'] == "on") ? "발행" : "미발행";

// 대금지급 방법
$payment_method = '';
if ($order['s20_bankcheck_w'] == 'center') {
    $payment_method = "센터 현금납부";
} elseif ($order['s20_bankcheck_w'] == 'base') {
    $payment_method = "계좌이체";
} else {
    $payment_method = "3월 8일 이후 확인가능";
}

// 센터명 조회
$center_query = @mysql_query("SELECT s2_center FROM step2_center WHERE s2_center_id = '{$order['s20_sell_center']}'");
$center_name = $center_query ? mysql_result($center_query, 0, 0) : '미정';

// 주문 항목 조회
$items_result = @mysql_query("
    SELECT s21_uid, s21_quantity, cost1, cost2, s21_sp_cost
    FROM step21_sell_cart
    WHERE s21_sellid = $sellid
    ORDER BY s21_uid ASC
");

$items = array();
$item_no = 0;
$total_cost = 0;

if ($items_result) {
    while ($item = mysql_fetch_assoc($items_result)) {
        $item_no++;
        // 원본 로직: s21_sp_cost가 없으면 cost1, 있으면 cost2 사용
        $unit_price = empty($item['s21_sp_cost']) ? floatval($item['cost1']) : floatval($item['cost2']);
        $item_total = $unit_price * intval($item['s21_quantity']);
        $total_cost += $item_total;

        $items[] = array(
            'no' => $item_no,
            's21_uid' => $item['s21_uid'],
            'quantity' => $item['s21_quantity'],
            'unit_price' => $unit_price,
            'item_total' => $item_total,
            's21_sp_cost' => $item['s21_sp_cost']
        );
    }
}

// 자재명 조회를 위해 부품 정보 가져오기
$part_info = array();
if (!empty($items)) {
    $uid_list = array_map(function ($item) {
        return $item['s21_uid'];
    }, $items);
    $uid_str = implode(',', array_map('intval', $uid_list));
    $parts_result = @mysql_query("
        SELECT s1_uid, s1_name
        FROM step1_parts
        WHERE s1_uid IN ($uid_str)
    ");
    if ($parts_result) {
        while ($part = mysql_fetch_assoc($parts_result)) {
            $part_info[$part['s1_uid']] = $part['s1_name'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <title>소모품 판매 내역서</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            padding: 20px;
            background: #f5f5f5;
        }

        .receipt-container {
            background: white;
            width: 900px;
            margin: 0 auto;
            padding: 30px;
            line-height: 1.5;
        }

        .logo {
            margin-bottom: 20px;
            text-align: left;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 20px;
        }

        .header-table td {
            border: 1px solid #000;
            padding: 8px;
            height: 30px;
            vertical-align: middle;
            text-align: center;
        }

        .header-table .label {
            background: #f0f0f0;
            font-weight: bold;
            width: 15%;
        }

        .header-table .value {
            text-align: center;
        }

        .info-section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding: 5px;
            border-left: 3px solid #000;
            background: #f9f9f9;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin: 10px 0;
        }

        .items-table th {
            border: 1px solid #000;
            padding: 8px;
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
            height: 30px;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            height: 50px;
            vertical-align: middle;
        }

        .items-table .no-col {
            width: 10%;
            text-align: center;
        }

        .items-table .name-col {
            width: 60%;
            text-align: left;
        }

        .items-table .qty-col {
            width: 10%;
            text-align: right;
            padding-right: 15px;
        }

        .items-table .price-col {
            width: 20%;
            text-align: right;
            padding-right: 15px;
        }

        .total-row {
            background: #f0f0f0;
            font-weight: bold;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin: 10px 0;
        }

        .payment-table td {
            border: 1px solid #000;
            padding: 8px;
            height: 30px;
            vertical-align: middle;
        }

        .payment-table .label {
            background: #f0f0f0;
            font-weight: bold;
            width: 25%;
            text-align: center;
        }

        .center-info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin: 10px 0;
        }

        .center-info-table td {
            border: 1px solid #000;
            padding: 8px;
            height: 30px;
            vertical-align: middle;
            text-align: center;
        }

        .center-info-table .header {
            background: #f0f0f0;
            font-weight: bold;
        }

        .remarks {
            margin-top: 15px;
            font-size: 11px;
            line-height: 1.6;
        }

        .button-group {
            text-align: right;
            margin-top: 20px;
        }

        button {
            padding: 8px 15px;
            margin-left: 10px;
            border: 1px solid #666;
            background: #f0f0f0;
            cursor: pointer;
            font-size: 12px;
        }

        button:hover {
            background: #e0e0e0;
        }

        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }

            .receipt-container {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .button-group {
                display: none !important;
            }

            /* 인쇄할 때 모든 색상을 검은색으로 변경 */
            * {
                color: black !important;
                border-color: black !important;
            }

            p,
            div,
            span,
            table,
            tr,
            td {
                color: black !important;
            }

            img,
            .logo img {
                filter: grayscale(100%) !important;
                -webkit-filter: grayscale(100%) !important;
            }
        }

        .header-info {
            display: flex;
            margin-bottom: 20px;
            gap: 20px;
        }

        .header-info-box {
            flex: 1;
        }
    </style>
    <script type="text/javascript">
        function printNow() {
            window.print();
        }
    </script>
</head>

<body>
    <div class="receipt-container">
        <div class="logo">
            <img src="../logo1.jpg" width="100" alt="Logo">
        </div>

        <table border="0" width="100%">
            <tr>
                <td width="50%" valign="top">
                    <!-- 좌측: 공급자 정보 -->
                    <table border="1" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                        <tr>
                            <td colspan="3" height="50"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <p style="font-size: 14px; font-weight: bold;">소모품 신청 처리 내역서</p>
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" rowspan="4"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                공<br>급<br>자
                            </td>
                            <td width="45%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                상호</td>
                            <td width="45%" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                (주)디지탈컴</td>
                        </tr>
                        <tr>
                            <td width="45%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                사업자등록번호</td>
                            <td width="45%" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                116-81-75974</td>
                        </tr>
                        <tr>
                            <td width="45%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                처리지점</td>
                            <td width="45%" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <?php echo htmlspecialchars($center_name); ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="45%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                A/S 처리기사</td>
                            <td width="45%" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <?php echo htmlspecialchars($order['s20_sell_name1']); ?>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="50%" valign="top">
                    <!-- 우측: 접수신청자 정보 -->
                    <table border="1" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                        <tr>
                            <td colspan="2" width="50%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                접수번호</td>
                            <td width="50%" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <?php echo htmlspecialchars($order['s20_sell_out_no']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" width="50%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                일자</td>
                            <td width="50%" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <?php echo htmlspecialchars($sell_in_date); ?>
                            </td>
                        </tr>
                        <tr>

                            <td width="15%" rowspan="4" style="border: 1px solid #000;
                                    text-align: center;
                                    vertical-align: middle;
                                    font-weight: bold;
                                    white-space: nowrap;">
                                접수<br>신청<br>
                            </td>

                            <td width="25%" height="35" style="border: 1px solid #000;
                                    text-align: center;
                                    vertical-align: middle;
                                    font-weight: bold;
                                    background: #fafafa;">
                                상호
                            </td>
                            <td width="60%" style="border: 1px solid #000;
                                        text-align: left;
                                        padding-left: 12px;
                                        vertical-align: middle;">
                                <?php echo htmlspecialchars($order['ex_company']); ?>&nbsp;귀하
                            </td>
                        </tr>
                        <tr>
                            <td height="35" style="border: 1px solid #000;
                                        text-align: center;
                                        vertical-align: middle;
                                        font-weight: bold;
                                        background: #fafafa;">
                                연락처
                            </td>
                            <td style="border: 1px solid #000;
                                        text-align: left;
                                        padding-left: 12px;
                                        vertical-align: middle;">
                                <?php echo htmlspecialchars($order['ex_tel']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td height="35" style="border: 1px solid #000;
                                            text-align: center;
                                            vertical-align: middle;
                                             font-weight: bold;
                                            background: #fafafa;">
                                세금계산서발행
                            </td>
                            <td style="border: 1px solid #000;
                                    text-align: left;
                                    padding-left: 12px;
                                    vertical-align: middle;">
                                <?php echo htmlspecialchars($tax_on); ?>
                            </td>
                        </tr>
                        <tr>
                            <td height="35" style="border: 1px solid #000;
                                        text-align: center;
                                        vertical-align: middle;
                                        font-weight: bold;
                                        background: #fafafa;">
                                사업자등록번호
                            </td>
                            <td style="border: 1px solid #000;
                                        text-align: left;
                                        padding-left: 12px;
                                        vertical-align: middle;">
                                <?php echo htmlspecialchars($order['ex_company_no']); ?>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div style="padding: 10px 0;">
                        <p style="font-size: 13px; font-weight: bold;">판매 내역 및 비용</p>
                    </div>

                    <table border="1" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                        <tr>
                            <td width="10%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                No.</td>
                            <td width="60%"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                소모품</td>
                            <td width="10%"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                수량</td>
                            <td width="20%"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                금액</td>
                        </tr>
                        <?php
                        if (!empty($items)) {
                            foreach ($items as $item) {
                                $part_name = isset($part_info[$item['s21_uid']]) ?
                                    $part_info[$item['s21_uid']] : '품목 ID: ' . $item['s21_uid'];
                                $unit_price_formatted = number_format($item['unit_price']);
                                $item_total_formatted = number_format($item['item_total']);
                                ?>
                                <tr>
                                    <td height="50"
                                        style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                        <?php echo htmlspecialchars($item['no']); ?>
                                    </td>
                                    <td style="border: 1px solid #000; text-align: left; vertical-align: middle;">
                                        &nbsp;<?php echo htmlspecialchars($part_name); ?></td>
                                    <td
                                        style="border: 1px solid #000; text-align: right; vertical-align: middle; padding-right: 10px;">
                                        <span
                                            style="color: red; font-weight: bold;"><?php echo htmlspecialchars($item['quantity']); ?>&nbsp;개</span>
                                    </td>
                                    <td
                                        style="border: 1px solid #000; text-align: right; vertical-align: middle; padding-right: 10px;">
                                        <span
                                            style="color: red; font-weight: bold;"><?php echo htmlspecialchars($unit_price_formatted); ?>&nbsp;×&nbsp;<?php echo htmlspecialchars($item['quantity']); ?>&nbsp;=&nbsp;<?php echo htmlspecialchars($item_total_formatted); ?></span>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="4" height="50"
                                    style="border: 1px solid #000; text-align: center; vertical-align: middle; color: #999;">
                                    항목이 없습니다.</td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                총액</td>
                            <td colspan="3"
                                style="border: 1px solid #000; text-align: right; vertical-align: middle; padding-right: 10px;">
                                <b><?php echo number_format($total_cost); ?>&nbsp;원&nbsp;</b>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <table border="1" width="100%" cellspacing="0" cellpadding="0"
                        style="border-collapse:collapse; margin-top: 20px;">
                        <tr>
                            <td width="25%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                입금계좌번호</td>
                            <td colspan="3" width="75%"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                신한 140-005-221339 (주)디지탈컴 정용호</td>
                        </tr>
                        <tr>
                            <td width="25%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                대금지급</td>
                            <td width="25%"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                <?php echo htmlspecialchars($payment_method); ?>
                            </td>
                            <td width="25%"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                입금일자</td>
                            <td width="25%" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <?php echo htmlspecialchars($bank_check); ?>
                            </td>
                        </tr>
                        <tr>
                            <td height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                A/S 처리완료일</td>
                            <td style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <?php echo htmlspecialchars($out_date); ?>
                            </td>
                            <td
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                &nbsp;</td>
                            <td style="border: 1px solid #000; text-align: center; vertical-align: middle;">&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <table border="1" width="100%" cellspacing="0" cellpadding="0"
                        style="border-collapse:collapse; margin-top: 20px;">
                        <tr>
                            <td width="100%" colspan="3" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                디지탈컴 A/S 센터</td>
                        </tr>
                        <tr>
                            <td width="15%" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                지점</td>
                            <td width="60%"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                주소</td>
                            <td width="25%"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                전화번호</td>
                        </tr>
                        <tr>
                            <td height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                영등포</td>
                            <td
                                style="border: 1px solid #000; text-align: left; padding-left: 5px; vertical-align: middle;">
                                [우 150-723]서울특별시 영등포구 영등포로 109, 3층 가열 8호 (당산동2가,영등포 유통상가)</td>
                            <td style="border: 1px solid #000; text-align: center; vertical-align: middle;">02-2671-9193
                            </td>
                        </tr>
                        <tr>
                            <td height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                을지로</td>
                            <td
                                style="border: 1px solid #000; text-align: left; padding-left: 5px; vertical-align: middle;">
                                [우 100-340]서울특별시 중구 을지로 157, 라열 377호 (산림동, 대림상가)</td>
                            <td style="border: 1px solid #000; text-align: center; vertical-align: middle;">02-2275-9193
                            </td>
                        </tr>
                        <tr>
                            <td height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                본사</td>
                            <td
                                style="border: 1px solid #000; text-align: left; padding-left: 5px; vertical-align: middle;">
                                [우 421-742]경기도 부천시 오정구 석천로 397, 303동 801호-804호 (삼정동, 부천테크노파크 쌍용3차)</td>
                            <td style="border: 1px solid #000; text-align: center; vertical-align: middle;">032-624-1980
                            </td>
                        </tr>
                        <tr>
                            <td width="75%" colspan="2" height="30"
                                style="border: 1px solid #000; text-align: center; vertical-align: middle; font-weight: bold;">
                                기술상담문의</td>
                            <td width="25%" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                1577-9193</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 10px;">※ A/S 택배 접수시 불량증상을 적어서 보내주시면 더욱 신속하게 처리됩니다.</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 5px;">※테두리가 진하게 표시된 네모박스안에 반드시 명기해 주세요.</td>
            </tr>
        </table>

        <div class="button-group">
            <button onclick="printNow()">인쇄</button>
            <button onclick="window.close()">닫기</button>
        </div>
    </div>
</body>

</html>
<?php mysql_close($connect); ?>