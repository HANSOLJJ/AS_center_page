<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

// MySQL 호환성 레이어 로드
require_once '../mysql_compat.php';

// 데이터베이스 연결
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

// URL 파라미터 처리
$number = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($number <= 0) {
    die('유효하지 않은 AS ID입니다.');
}

##### 선택한 게시물의 입력값을 뽑아낸다.
$query = "SELECT s13_asid, s13_as_center, s13_as_in_date, s13_as_in_how, s13_as_out_no, s13_meid, s13_dex_no, s13_sms1, s13_sms2, s13_bank_check, s13_tax_code, s13_dex_send, s13_dex_send_name, s13_as_out_date, s13_as_name2, ex_tel, ex_sms_no, ex_sec1, ex_sec2, ex_company, ex_man, ex_address, ex_address_no, ex_company_no, s13_bankcheck_w FROM step13_as WHERE s13_asid = $number";
$result = mysql_query($query);
if (!$result) {
    error("QUERY_ERROR");
    exit;
}
$row = mysql_fetch_object($result);

$my_s13_asid = $row->s13_asid;
$my_s13_as_center = $row->s13_as_center;
$my_s13_as_in_date = $row->s13_as_in_date;
$my_s13_as_in_how = $row->s13_as_in_how;

$my_s13_as_out_no = $row->s13_as_out_no;
$my_s13_meid = $row->s13_meid;
$my_s13_dex_no = $row->s13_dex_no;
$my_s13_sms1 = $row->s13_sms1;
$my_s13_sms2 = $row->s13_sms2;
$my_s13_bank_check = $row->s13_bank_check;
$my_s13_tax_code = $row->s13_tax_code;
$my_s13_dex_send = $row->s13_dex_send;

$my_s13_dex_send_name = $row->s13_dex_send_name;
$my_s13_as_out_date = $row->s13_as_out_date;
$my_s13_as_name2 = $row->s13_as_name2;

/// 추가--------------------------
$my_ex_tel = $row->ex_tel;
$my_ex_sms_no = $row->ex_sms_no;
$my_ex_sec1 = $row->ex_sec1;
$my_ex_sec2 = $row->ex_sec2;
$my_ex_company = $row->ex_company;
$my_ex_address = $row->ex_address;
$my_ex_address_no = $row->ex_address_no;
$my_ex_company_no = $row->ex_company_no;
$s13 = $row->s13_bankcheck_w;
if ($s13 == 'center') {
    $s13 = "센터 현금납부";
} elseif ($s13 == 'base') {
    $s13 = "계좌이체";
} elseif ($s13 == '') {
    $s13 = "3월 8일 이후  확인가능";
}
/// 추가--------------------------

// 날짜 포맷팅 함수
function format_date($value)
{
    $result = '';
    if (!empty($value)) {
        if (is_numeric($value) && intval($value) > 86400) {
            // 유효한 유닉스 타임스탐프 (최소 1970-02-01 이후)
            $result = date("Y년 m월 d일", intval($value));
        } else {
            // 문자열 datetime 형식
            $timestamp = strtotime($value);
            if ($timestamp !== false && $timestamp > 0) {
                $result = date("Y년 m월 d일", $timestamp);
            } else {
                // strtotime 실패 시 원본 값 그대로 표시
                $result = htmlspecialchars($value);
            }
        }
    }
    return $result;
}

$my_s13_as_in_date = format_date($my_s13_as_in_date);
$my_s13_bank_check = format_date($my_s13_bank_check);
$my_s13_as_out_date = format_date($my_s13_as_out_date);

if ($my_s13_tax_code != "") {
    $tax_on = "발행";
} else {
    $tax_on = "미발행";
}

if ($my_s13_dex_send_name == "") {
    $dex_send = "-";
} else {
    $dex_send = "$my_s13_dex_send_name($my_s13_dex_send)";
}
//------------------센터명
$center_query = mysql_query("Select s2_center FROM step2_center WHERE s2_center_id ='$my_s13_as_center'");
$center_name = mysql_result($center_query, 0, 0);
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <title>AS 접수 내역서</title>
    <style>
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
            .button-group {
                display: none !important;
            }

            /* 인쇄할 때 모든 색상을 검은색으로 변경 및 폰트 축소 */
            * {
                color: black !important;
                border-color: black !important;
            }

            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11px !important;
            }

            p,
            div,
            span,
            table,
            tr,
            td {
                color: black !important;
            }

            font[color='red'] {
                color: black !important;
            }

            img,
            img.logo-img {
                filter: grayscale(100%) !important;
                -webkit-filter: grayscale(100%) !important;
            }
        }
    </style>
</head>

<body>
    <script type="text/javascript">
        function printNow() {
            window.print();
        }
    </script>

    <p align='left'><img src='logo1.jpg' width='100' class='logo-img'></p>
    <table border="0" width="100%">
        <tr>
            <td width="40%" valign="top">
                <table border="1" width="100%" cellspacing="0" bordercolordark="black" bordercolorlight="black">
                    <tr>
                        <td colspan="3" width="100%" height='50'>
                            <p align='center'>
                                <font size='4'><b>A/S 접수 및 처리 내역서</b></font>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td width="10%" rowspan="4">
                            <p align='center'><b>공<br>급<br>자</b></p>
                        </td>
                        <td width="45%" height='30'>
                            <p align='center'><b>상호</b></p>
                        </td>
                        <td width="45%">
                            <p align='center'>(주)디지탈컴</p>
                        </td>
                    </tr>
                    <tr>
                        <td width="45%" height='30'>
                            <p align='center'><b>사업자등록번호</b></p>
                        </td>
                        <td width="45%">
                            <p align='center'>116-81-75974</p>
                        </td>
                    </tr>
                    <tr>
                        <td width="45%" height='30'>
                            <p align='center'><b>처리지점</b></p>
                        </td>
                        <td width="45%">
                            <p align='center'><? echo "$center_name"; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td width="45%" height='30'>
                            <p align='center'><b>A/S 처리기사</b></p>
                        </td>
                        <td width="45%">
                            <p align='center'><? echo "$my_s13_as_name2"; ?></p>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="60%" valign="top">
                <table border="1" width="100%" cellspacing="0" bordercolordark="black" bordercolorlight="black">
                    <tr>
                        <td colspan="2" width="50%" height='30'>
                            <p align='center'><b>접수번호</b></p>
                        </td>
                        <td width="50%">
                            <p align='center'><? echo "$my_s13_as_out_no"; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" width="50%" height='30'>
                            <p align='center'><b>일자</b></p>
                        </td>
                        <td width="50%">
                            <p align='center'><? echo "$my_s13_as_in_date"; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td width="10%" rowspan="4">
                            <p align='center'><b>접수<br></vr>신청</b></p>
                        </td>
                        <td width="30%" height='30'>
                            <p align='center'><b>상호</b></p>
                        </td>
                        <td width="60%">
                            <p align='center'><? echo "$my_ex_company"; ?>&nbsp;귀하</p>
                        </td>
                    </tr>
                    <tr>
                        <td width="30%" height='30'>
                            <p align='center'><b>연락처</b></p>
                        </td>
                        <td width="60%">
                            <p align='center'><? echo "$my_ex_tel"; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td width="30%" height='30'>
                            <p align='center'><b>세금계산서발행</b></p>
                        </td>
                        <td width="60%">
                            <p align='center'><? echo "$tax_on"; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td width="30%" height='30'>
                            <p align='center'><b>사업자등록번호</b></p>
                        </td>
                        <td width="60%">
                            <p align='center'><? echo "$my_ex_company_no"; ?></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <table border="0" width="100%" height='30'>
                    <tr>
                        <td>
                            <p align='left'>
                                <font size='3'><b>A/S 내역 및 비용</b></font>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php
                // AS 항목 및 비용 내역 표시
                $query_item_list = "SELECT s14_aiid, s14_asid, s14_model, s14_poor, s14_stat, s14_asrid, cost_name, cost_sn, as_start_view, as_end_result FROM step14_as_item WHERE s14_asid = $my_s13_asid";
                $result_item_list = mysql_query($query_item_list);
                if (!$result_item_list) {
                    error("QUERY_ERROR");
                    exit;
                }
                $no = '0';
                ?>
                <table border="1" width="100%" cellspacing="0" bordercolordark="black" bordercolorlight="black">
                    <tr>
                        <td width="4%" height='30'>
                            <p align='center'><b>No.</b></p>
                        </td>
                        <td width="16%" height='30'>
                            <p align='center'><b>모델명</b></p>
                        </td>
                        <td width="10%" height='30'>
                            <p align='center'><b>불량증상</b></p>
                        </td>
                        <td width="10%" height='30'>
                            <p align='center'><b>A/S 처리내용</b></p>
                        </td>
                        <td width="60%" height='15'>
                            <p align='center'><b>소모품신청</b></p>
                        </td>
                    </tr>
                    <?php
                    while ($row_item_list = mysql_fetch_array($result_item_list, MYSQL_ASSOC)) {
                        $my_s14_aiid = $row_item_list['s14_aiid'];
                        $no++;
                        $my_s14_asid = $row_item_list['s14_asid'];
                        $my_s14_model = $row_item_list['s14_model'];
                        $my_s14_poor = $row_item_list['s14_poor'];
                        $my_s14_stat = $row_item_list['s14_stat'];
                        $my_s14_asrid = $row_item_list['s14_asrid'];
                        $my_cost_name = $row_item_list['cost_name'];
                        $my_cost_sn = $row_item_list['cost_sn'];
                        $my_as_start_view = $row_item_list['as_start_view'];
                        $my_as_end_result = $row_item_list['as_end_result'];
                        ?>
                        <tr>
                            <td height='50' align='center' valign='middle'><b><?php echo $no; ?></b></td>
                            <td height='50' align='center' valign='middle'>
                                <b><?php echo $my_cost_name; ?></b>
                            </td>
                            <td height='50' align='center' valign='middle'>
                                <font color='red'><b><?php echo $my_as_start_view; ?></b></font>
                            </td>
                            <td height='50' align='center' valign='middle'>
                                <font color='red'><b><?php echo $my_as_end_result; ?></b></font>
                            </td>
                            <td height='50' align='center' valign='middle'>
                                <table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>
                                    <?php
                                    // 소모품 목록 표시
                                    $instant_query = "SELECT s18_accid, s18_aiid, s18_uid, s18_quantity, s18_sp_cost, s18_asid, cost_name, cost_sn, cost1, cost2, cost_sec FROM step18_as_cure_cart WHERE s18_aiid = $my_s14_aiid";
                                    $instant_result = mysql_query($instant_query);
                                    if (!$instant_result) {
                                        error("QUERY_ERROR");
                                        exit;
                                    }
                                    $instant_rows = mysql_num_rows($instant_result);
                                    if ($instant_rows > 0) {
                                        $total_cost = '0';
                                        while ($instant_reply = mysql_fetch_object($instant_result)) {
                                            $my_s18_accid = $instant_reply->s18_accid;
                                            $my_s18_aiid = $instant_reply->s18_aiid;
                                            $my_s18_uid = $instant_reply->s18_uid;
                                            $my_s18_quantity = $instant_reply->s18_quantity;
                                            $my_s18_sp_cost = $instant_reply->s18_sp_cost;
                                            $my_s18_asid = $instant_reply->s18_asid;
                                            $my_cost_name_part = $instant_reply->cost_name;
                                            $my_cost_sn_part = $instant_reply->cost_sn;
                                            $my_cost1 = $instant_reply->cost1;
                                            $my_cost2 = $instant_reply->cost2;
                                            $my_cost_sec = $instant_reply->cost_sec;

                                            // 가격정보
                                            if ($my_s18_sp_cost == "") {
                                                $parts_cost = $my_cost1 * $my_s18_quantity;
                                                $my_cost = $my_cost1;
                                            } else {
                                                $parts_cost = $my_cost2 * $my_s18_quantity;
                                                $my_cost = $my_cost2;
                                            }
                                            $my_cost = number_format($my_cost);
                                            $n_parts_cost = number_format($parts_cost);
                                            ?>
                                            <tr>
                                                <td align='left' width="50%">
                                                    &nbsp;-&nbsp;<?php echo $my_cost_name_part; ?>
                                                </td>
                                                <td align='right' width="15%">
                                                    <font color='red'><b><?php echo $my_s18_quantity; ?>&nbsp;개</b></font>
                                                </td>
                                                <td align='right' width="35%">
                                                    <font color='red'><b><?php echo $my_cost; ?>&nbsp;X
                                                            <?php echo $my_s18_quantity; ?> = <?php echo $n_parts_cost; ?></b>
                                                    </font>
                                                </td>
                                            </tr>
                                            <?php
                                            $total_cost = $total_cost + $parts_cost;
                                        }
                                    }
                                    $total_cost = number_format($total_cost);
                                    ?>
                                </table>
                            </td>
                        </tr>
                        <?php
                    }
                    $no++;
                    ?>
                    <tr>
                        <td height='50' align='center' valign='middle'><b><?php echo $no; ?></b></td>
                        <td height='50' align='center' valign='middle'><b>처리비용 총액</b></td>
                        <td height='50' align='center' valign='middle'><b>-</b></td>
                        <td height='50' align='center' valign='middle'><b>-</b></td>
                        <td height='50' align='right' valign='middle'>
                            <font color='red'><b>
                                    <?php
                                    // 전체 처리비용 합계
                                    $query_sum1 = "SELECT s18_asid, s18_uid, s18_quantity, s18_sp_cost, cost1, cost2 FROM step18_as_cure_cart WHERE s18_asid = $my_s13_asid";
                                    $result_sum1 = mysql_query($query_sum1);
                                    if (!$result_sum1) {
                                        error("QUERY_ERROR");
                                        exit;
                                    }
                                    $no_code = "0";
                                    $kid_total = "0";

                                    while ($row_sum1 = mysql_fetch_array($result_sum1, MYSQL_ASSOC)) {
                                        $my_s18_asid = $row_sum1['s18_asid'];
                                        $no_code++;
                                        $my_s18_uid = $row_sum1['s18_uid'];
                                        $my_s18_quantity = $row_sum1['s18_quantity'];
                                        $my_s18_sp_cost = $row_sum1['s18_sp_cost'];
                                        $my_cost1 = $row_sum1['cost1'];
                                        $my_cost2 = $row_sum1['cost2'];

                                        if ($my_s18_sp_cost == "") {
                                            $kid_cost[$no_code] = $my_s18_quantity * $my_cost1;
                                        } else
                                            if ($my_s18_sp_cost != "") {
                                                $kid_cost[$no_code] = $my_s18_quantity * $my_cost2;
                                            }

                                        $kid_total = $kid_total + $kid_cost[$no_code];
                                    }
                                    $kid_total = number_format($kid_total);
                                    echo $kid_total;
                                    ?>원
                                </b></font>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table border="1" width="100%" cellspacing="0" bordercolordark="black" bordercolorlight="black">
                    <tr>
                        <td width="25%" height='30'>
                            <p align='center'><b>입금계좌번호</b></p>
                        </td>
                        <td colspan="3" width="75%">
                            <p align='center'><b>신한 140-005-221339 (주)디지탈컴 정용호</b></p>
                        </td>
                    </tr>
                    <tr>
                        <td width="25%" height='30'>
                            <p align='center'><b>대금지급</b></p>
                        </td>
                        <td width="25%">
                            <p align='center'><b><? echo "$s13"; ?></b></p>
                        </td>
                        <td width="25%">
                            <p align='center'><b>입금일자</b></p>
                        </td>
                        <td width="25%">
                            <p align='center'><? echo "$my_s13_bank_check"; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td height='30'>
                            <p align='center'><b>A/S 처리완료일</b></p>
                        </td>
                        <td>
                            <p align='center'><? echo "$my_s13_as_out_date"; ?></p>
                        </td>
                        <td>
                            <p align='center'><b>택배운송번호</b></p>
                        </td>
                        <td>
                            <p align='center'><? echo "$dex_send"; ?></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table border="1" width="100%" cellspacing="0" bordercolordark="black" bordercolorlight="black">
                    <tr>
                        <td width="100%" colspan="3" height='30'>
                            <p align='center'><b>디지탈컴 A/S 센터</b></p>
                        </td>
                    </tr>
                    <tr>
                        <td width="15%" height='30'>
                            <p align='center'><b>지점</b></p>
                        </td>
                        <td width="60%">
                            <p align='center'><b>주소</b></p>
                        </td>
                        <td width="25%">
                            <p align='center'><b>전화번호</b></p>
                        </td>
                    </tr>
                    <tr>
                        <td height='30'>
                            <p align='center'><b>영등포</b></p>
                        </td>
                        <td>
                            <p align='left'>[우 150-723]서울특별시 영등포구 영등포로 109, 3층 가열 8호 (당산동2가,영등포 유통상가)</p>
                        </td>
                        <td>
                            <p align='center'>02-2671-9193</p>
                        </td>
                    </tr>
                    <tr>
                        <td height='30'>
                            <p align='center'><b>을지로</b></p>
                        </td>
                        <td>
                            <p align='left'>[우 100-340]서울특별시 중구 을지로 157, 라열 377호 (산림동, 대림상가)</p>
                        </td>
                        <td>
                            <p align='center'>02-2275-9193</p>
                        </td>
                    </tr>
                    <tr>
                        <td height='30'>
                            <p align='center'><b>본사</b></p>
                        </td>
                        <td>
                            <p align='left'>[우 421-742]경기도 부천시 오정구 석천로 397, 303동 801호-804호 (삼정동, 부천테크노파크 쌍용3차)</p>
                        </td>
                        <td>
                            <p align='center'>032-624-1980</p>
                        </td>
                    </tr>
                    <tr>
                        <td width="75%" colspan="2" height='30'>
                            <p align='center'><b>기술상담문의</b></p>
                        </td>
                        <td width="25%" height="0">
                            <p align='center'>1577-9193</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">※ A/S 택배 접수시 불량증상을 적어서 보내주시면 더욱 신속하게 처리됩니다.</td>
        </tr>
        <tr>
            <td colspan="2">※테두리가 진하게 표시된 네모박스안에 반드시 명기해 주세요.</td>
        </tr>
    </table>

    <div class="button-group">
        <button onclick="printNow()">인쇄</button>
        <button onclick="window.close()">닫기</button>
    </div>

    <p>&nbsp;</p>
    <?php mysql_close($connect); ?>
</body>

</html>