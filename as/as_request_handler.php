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

$user_name = $_SESSION['member_id'];
$current_page = 'as_requests';

// 요청 방식에 따른 처리
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$response = array('success' => false, 'message' => '');

// Edit mode 확인
$edit_as_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$is_edit_mode = ($edit_as_id > 0);

// AJAX 요청 처리
if ($action === 'get_as_request_data') {
    // 기존 AS 요청 정보 로드 (수정용)
    $as_id = isset($_POST['as_id']) ? intval($_POST['as_id']) : 0;

    if (empty($as_id)) {
        $response['message'] = 'AS 요청 ID가 필요합니다.';
        echo json_encode($response);
        exit;
    }

    // AS 기본 정보 조회
    $as_result = @mysql_query("SELECT s13_asid, s13_meid, s13_as_in_how FROM step13_as WHERE s13_asid = $as_id");
    if (!$as_result || mysql_num_rows($as_result) == 0) {
        $response['message'] = 'AS 요청을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }

    $as_info = mysql_fetch_assoc($as_result);

    // 회원 정보 조회
    $member_id = $as_info['s13_meid'];
    $member_result = @mysql_query("SELECT s11_meid, s11_com_name, s11_phone1, s11_phone2, s11_phone3 FROM step11_member WHERE s11_meid = $member_id");
    $member_info = @mysql_fetch_assoc($member_result);

    // AS 항목 조회 (모델명, 불량증상명 포함)
    $items = array();
    $items_result = @mysql_query("SELECT ai.s14_aiid, ai.s14_model, ai.s14_poor,
                                         md.s15_model_name, pd.s16_poor
                                  FROM step14_as_item ai
                                  LEFT JOIN step15_as_model md ON ai.s14_model = md.s15_amid
                                  LEFT JOIN step16_as_poor pd ON ai.s14_poor = pd.s16_apid
                                  WHERE ai.s14_asid = $as_id ORDER BY ai.s14_aiid ASC");
    if ($items_result) {
        while ($row = mysql_fetch_assoc($items_result)) {
            $items[] = array(
                'model_id' => $row['s14_model'],
                'poor_id' => $row['s14_poor'],
                'model_name' => $row['s15_model_name'],
                'poor_name' => $row['s16_poor']
            );
        }
    }

    $response['success'] = true;
    $response['as_info'] = $as_info;
    $response['member_info'] = $member_info;
    $response['products'] = $items;
    echo json_encode($response);
    exit;
}

if ($action === 'load_step3_data') {
    // Step 3 데이터 (제품 및 불량증상) 로드
    $models = array();
    $poors = array();

    $model_result = @mysql_query("SELECT s15_amid, s15_model_name, s15_model_sn FROM step15_as_model ORDER BY s15_amid DESC");
    if ($model_result) {
        while ($row = mysql_fetch_assoc($model_result)) {
            $models[] = $row;
        }
    }

    $poor_result = @mysql_query("SELECT s16_apid, s16_poor FROM step16_as_poor ORDER BY s16_apid ASC");
    if ($poor_result) {
        while ($row = mysql_fetch_assoc($poor_result)) {
            $poors[] = $row;
        }
    }

    $response['success'] = true;
    $response['models'] = $models;
    $response['poors'] = $poors;
    echo json_encode($response);
    exit;
}

if ($action === 'search_member') {
    $search_name = isset($_POST['search_name']) ? trim($_POST['search_name']) : '';

    if (!empty($search_name)) {
        $search_esc = mysql_real_escape_string($search_name);
        $result = @mysql_query("SELECT s11_meid, s11_com_name, s11_phone1, s11_phone2, s11_phone3, s11_sec FROM step11_member WHERE s11_com_name LIKE '%$search_esc%' LIMIT 10");

        if ($result && mysql_num_rows($result) > 0) {
            $members = array();
            while ($row = mysql_fetch_assoc($result)) {
                $members[] = $row;
            }
            $response['success'] = true;
            $response['members'] = $members;
        } else {
            $response['success'] = false;
            $response['message'] = '검색 결과가 없습니다. 새로 등록해주세요.';
        }
    }
    echo json_encode($response);
    exit;
}

if ($action === 'save_as_request') {
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    $in_how = isset($_POST['in_how']) ? trim($_POST['in_how']) : '내방';
    $products_json = isset($_POST['products']) ? $_POST['products'] : '[]';

    if (empty($member_id)) {
        $response['message'] = '업체를 선택해주세요.';
        echo json_encode($response);
        exit;
    }

    $products = json_decode($products_json, true);
    if (empty($products) || !is_array($products)) {
        $response['message'] = '제품을 1개 이상 추가해주세요.';
        echo json_encode($response);
        exit;
    }

    // 유효한 수령 방법 확인
    $valid_in_how = array('내방', '택배', '퀵');
    if (!in_array($in_how, $valid_in_how)) {
        $in_how = '내방';
    }

    // 회원(업체) 정보 조회
    $member_info_result = @mysql_query("SELECT s11_meid, s11_com_name, s11_phone1, s11_phone2, s11_phone3, s11_sec FROM step11_member WHERE s11_meid = $member_id");
    $member_info = @mysql_fetch_assoc($member_info_result);

    if (!$member_info) {
        $response['message'] = '업체 정보를 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }

    $s11_meid = $member_info['s11_meid'];
    $member_name = $member_info['s11_com_name'];
    $member_phone = $member_info['s11_phone1'] . '-' . $member_info['s11_phone2'] . '-' . $member_info['s11_phone3'];
    $member_sec = $member_info['s11_sec'];

    // s13_asid 계산 (기존 최대값 + 1)
    $max_id_result = @mysql_query("SELECT MAX(s13_asid) as max_id FROM step13_as");
    $max_id_row = @mysql_fetch_assoc($max_id_result);
    $new_as_id = intval($max_id_row['max_id']) + 1;

    // AS 요청 기본 정보 저장 (step13_as)
    $now = date('Y-m-d H:i:s');
    $as_center = 'center1283763850'; // AS센터 코드
    $member_id_esc = mysql_real_escape_string($s11_meid);
    $in_how_esc = mysql_real_escape_string($in_how);
    $member_name_esc = mysql_real_escape_string($member_name);
    $member_phone_esc = mysql_real_escape_string($member_phone);
    $member_sec_esc = mysql_real_escape_string($member_sec);

    $insert_as_query = "INSERT INTO step13_as (s13_asid, s13_as_center, s13_meid, s13_as_in_how, s13_as_in_date, s13_as_level, ex_company, ex_tel, ex_sec1)
                        VALUES ('$new_as_id', '$as_center', '$member_id_esc', '$in_how_esc', '$now', '1', '$member_name_esc', '$member_phone_esc', '$member_sec_esc')";

    $insert_as_result = @mysql_query($insert_as_query);

    if (!$insert_as_result) {
        $response['message'] = 'AS 요청 저장 중 오류가 발생했습니다. (DB 오류: ' . mysql_error() . ')';
        echo json_encode($response);
        exit;
    }

    $as_id = $new_as_id;

    // 최적화: 모든 모델 ID와 불량증상 ID 추출
    $valid_products = array();
    $model_ids = array();
    $poor_ids = array();
    foreach ($products as $product) {
        $model_id = intval($product['model_id']);
        $poor_id = intval($product['poor_id']);

        if ($model_id > 0 && $poor_id > 0) {
            $valid_products[] = array('model_id' => $model_id, 'poor_id' => $poor_id);
            $model_ids[] = $model_id;
            $poor_ids[] = $poor_id;
        }
    }

    if (empty($valid_products)) {
        $response['message'] = '유효한 제품이 없습니다.';
        echo json_encode($response);
        exit;
    }

    // 최적화: 모든 모델명을 한 번에 조회 (N+1 문제 해결)
    $model_ids_str = implode(',', $model_ids);
    $models_result = @mysql_query("SELECT s15_amid, s15_model_name FROM step15_as_model WHERE s15_amid IN ($model_ids_str)");

    $model_names_map = array();
    while ($model_row = @mysql_fetch_assoc($models_result)) {
        $model_names_map[$model_row['s15_amid']] = $model_row['s15_model_name'];
    }

    // 최적화: 모든 불량증상명을 한 번에 조회 (N+1 문제 해결)
    $poor_ids_str = implode(',', $poor_ids);
    $poors_result = @mysql_query("SELECT s16_apid, s16_poor FROM step16_as_poor WHERE s16_apid IN ($poor_ids_str)");

    $poor_names_map = array();
    while ($poor_row = @mysql_fetch_assoc($poors_result)) {
        $poor_names_map[$poor_row['s16_apid']] = $poor_row['s16_poor'];
    }

    // 최적화: 모든 제품을 한 번의 INSERT로 처리 (bulk insert)
    $insert_values = array();
    $first_product_name = '';
    foreach ($valid_products as $idx => $product) {
        $model_id = $product['model_id'];
        $poor_id = $product['poor_id'];

        // cost_name 추출 (모델명)
        $cost_name = isset($model_names_map[$model_id]) ? $model_names_map[$model_id] : '';
        $cost_name_esc = mysql_real_escape_string($cost_name);

        // as_start_view 추출 (불량증상명)
        $as_start_view = isset($poor_names_map[$poor_id]) ? $poor_names_map[$poor_id] : '';
        $as_start_view_esc = mysql_real_escape_string($as_start_view);

        // s14_stat: '입고' (기존 코드 패턴 참고)
        // s14_asrid, s14_cart는 빈 문자열로 초기화 (NOT NULL 제약)
        $insert_values[] = "($as_id, $model_id, '$poor_id', '입고', '', '', '$cost_name_esc', '$as_start_view_esc')";

        // 첫 번째 제품명 기록
        if ($idx == 0 && !empty($cost_name)) {
            $first_product_name = $cost_name;
        }
    }

    if (!empty($insert_values)) {
        $bulk_insert_query = "INSERT INTO step14_as_item (s14_asid, s14_model, s14_poor, s14_stat, s14_asrid, s14_cart, cost_name, as_start_view) VALUES " . implode(',', $insert_values);
        $insert_items_result = @mysql_query($bulk_insert_query);

        if (!$insert_items_result) {
            $mysql_error = mysql_error();
            $response['message'] = 'AS 아이템 저장 중 오류가 발생했습니다. (DB 오류: ' . $mysql_error . ')';
            $response['debug_query'] = $bulk_insert_query; // 디버깅용
            echo json_encode($response);
            exit;
        }

        // 저장 확인
        $verify_query = "SELECT COUNT(*) as cnt FROM step14_as_item WHERE s14_asid = $as_id";
        $verify_result = @mysql_query($verify_query);
        $verify_row = @mysql_fetch_assoc($verify_result);

        if (intval($verify_row['cnt']) === 0) {
            $response['message'] = 'AS 아이템이 저장되었으나 확인할 수 없습니다.';
            echo json_encode($response);
            exit;
        }
    }

    // 제품명 업데이트 (첫 번째 제품명으로)
    if (!empty($first_product_name)) {
        $product_name_esc = mysql_real_escape_string($first_product_name);
        @mysql_query("UPDATE step13_as SET s13_product = '$product_name_esc' WHERE s13_asid = '$as_id'");
    }

    $response['success'] = true;
    $response['as_id'] = $as_id;
    $response['message'] = 'AS 요청이 등록되었습니다.';
    echo json_encode($response);
    exit;
}

if ($action === 'update_as_request') {
    $as_id = isset($_POST['as_id']) ? intval($_POST['as_id']) : 0;
    $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    $in_how = isset($_POST['in_how']) ? trim($_POST['in_how']) : '내방';
    $products_json = isset($_POST['products']) ? $_POST['products'] : '[]';

    if (empty($as_id)) {
        $response['message'] = 'AS 요청 ID가 필요합니다.';
        echo json_encode($response);
        exit;
    }

    if (empty($member_id)) {
        $response['message'] = '업체를 선택해주세요.';
        echo json_encode($response);
        exit;
    }

    $products = json_decode($products_json, true);
    if (empty($products) || !is_array($products)) {
        $response['message'] = '제품을 1개 이상 추가해주세요.';
        echo json_encode($response);
        exit;
    }

    // 기존 AS 요청 확인
    $as_check_result = @mysql_query("SELECT s13_asid FROM step13_as WHERE s13_asid = $as_id");
    if (!$as_check_result || mysql_num_rows($as_check_result) == 0) {
        $response['message'] = 'AS 요청을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }

    // 유효한 수령 방법 확인
    $valid_in_how = array('내방', '택배', '퀵');
    if (!in_array($in_how, $valid_in_how)) {
        $in_how = '내방';
    }

    // 회원(업체) 정보 조회
    $member_info_result = @mysql_query("SELECT s11_meid, s11_com_name, s11_phone1, s11_phone2, s11_phone3, s11_sec FROM step11_member WHERE s11_meid = $member_id");
    $member_info = @mysql_fetch_assoc($member_info_result);

    if (!$member_info) {
        $response['message'] = '업체 정보를 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }

    $s11_meid = $member_info['s11_meid'];
    $member_name = $member_info['s11_com_name'];
    $member_phone = $member_info['s11_phone1'] . '-' . $member_info['s11_phone2'] . '-' . $member_info['s11_phone3'];
    $member_sec = $member_info['s11_sec'];

    // step13_as 업데이트 (회원정보 및 수령 방법)
    $member_id_esc = mysql_real_escape_string($s11_meid);
    $in_how_esc = mysql_real_escape_string($in_how);
    $member_name_esc = mysql_real_escape_string($member_name);
    $member_phone_esc = mysql_real_escape_string($member_phone);
    $member_sec_esc = mysql_real_escape_string($member_sec);

    $update_as_query = "UPDATE step13_as SET s13_meid = '$member_id_esc', s13_as_in_how = '$in_how_esc', ex_company = '$member_name_esc', ex_tel = '$member_phone_esc', ex_sec1 = '$member_sec_esc' WHERE s13_asid = $as_id";

    $update_as_result = @mysql_query($update_as_query);

    if (!$update_as_result) {
        $response['message'] = 'AS 요청 업데이트 중 오류가 발생했습니다. (DB 오류: ' . mysql_error() . ')';
        echo json_encode($response);
        exit;
    }

    // 최적화: 모든 모델 ID와 불량증상 ID 추출
    $valid_products = array();
    $model_ids = array();
    $poor_ids = array();
    foreach ($products as $product) {
        $model_id = intval($product['model_id']);
        $poor_id = intval($product['poor_id']);

        if ($model_id > 0 && $poor_id > 0) {
            $valid_products[] = array('model_id' => $model_id, 'poor_id' => $poor_id);
            $model_ids[] = $model_id;
            $poor_ids[] = $poor_id;
        }
    }

    if (empty($valid_products)) {
        $response['message'] = '유효한 제품이 없습니다.';
        echo json_encode($response);
        exit;
    }

    // 기존 step14_as_item 삭제
    $delete_items_query = "DELETE FROM step14_as_item WHERE s14_asid = $as_id";
    @mysql_query($delete_items_query);

    // 최적화: 모든 모델명을 한 번에 조회 (N+1 문제 해결)
    $model_ids_str = implode(',', $model_ids);
    $models_result = @mysql_query("SELECT s15_amid, s15_model_name FROM step15_as_model WHERE s15_amid IN ($model_ids_str)");

    $model_names_map = array();
    while ($model_row = @mysql_fetch_assoc($models_result)) {
        $model_names_map[$model_row['s15_amid']] = $model_row['s15_model_name'];
    }

    // 최적화: 모든 불량증상명을 한 번에 조회 (N+1 문제 해결)
    $poor_ids_str = implode(',', $poor_ids);
    $poors_result = @mysql_query("SELECT s16_apid, s16_poor FROM step16_as_poor WHERE s16_apid IN ($poor_ids_str)");

    $poor_names_map = array();
    while ($poor_row = @mysql_fetch_assoc($poors_result)) {
        $poor_names_map[$poor_row['s16_apid']] = $poor_row['s16_poor'];
    }

    // 최적화: 모든 제품을 한 번의 INSERT로 처리 (bulk insert)
    $insert_values = array();
    $first_product_name = '';
    foreach ($valid_products as $idx => $product) {
        $model_id = $product['model_id'];
        $poor_id = $product['poor_id'];

        // cost_name 추출 (모델명)
        $cost_name = isset($model_names_map[$model_id]) ? $model_names_map[$model_id] : '';
        $cost_name_esc = mysql_real_escape_string($cost_name);

        // as_start_view 추출 (불량증상명)
        $as_start_view = isset($poor_names_map[$poor_id]) ? $poor_names_map[$poor_id] : '';
        $as_start_view_esc = mysql_real_escape_string($as_start_view);

        // s14_stat: '입고' (기존 코드 패턴 참고)
        // s14_asrid, s14_cart는 빈 문자열로 초기화 (NOT NULL 제약)
        $insert_values[] = "($as_id, $model_id, '$poor_id', '입고', '', '', '$cost_name_esc', '$as_start_view_esc')";

        // 첫 번째 제품명 기록
        if ($idx == 0 && !empty($cost_name)) {
            $first_product_name = $cost_name;
        }
    }

    if (!empty($insert_values)) {
        $bulk_insert_query = "INSERT INTO step14_as_item (s14_asid, s14_model, s14_poor, s14_stat, s14_asrid, s14_cart, cost_name, as_start_view) VALUES " . implode(',', $insert_values);
        $insert_items_result = @mysql_query($bulk_insert_query);

        if (!$insert_items_result) {
            $mysql_error = mysql_error();
            $response['message'] = 'AS 아이템 저장 중 오류가 발생했습니다. (DB 오류: ' . $mysql_error . ')';
            echo json_encode($response);
            exit;
        }

        // 저장 확인
        $verify_query = "SELECT COUNT(*) as cnt FROM step14_as_item WHERE s14_asid = $as_id";
        $verify_result = @mysql_query($verify_query);
        $verify_row = @mysql_fetch_assoc($verify_result);

        if (intval($verify_row['cnt']) === 0) {
            $response['message'] = 'AS 아이템이 저장되었으나 확인할 수 없습니다.';
            echo json_encode($response);
            exit;
        }
    }

    // 제품명 업데이트 (첫 번째 제품명으로)
    if (!empty($first_product_name)) {
        $product_name_esc = mysql_real_escape_string($first_product_name);
        @mysql_query("UPDATE step13_as SET s13_product = '$product_name_esc' WHERE s13_asid = '$as_id'");
    }

    $response['success'] = true;
    $response['as_id'] = $as_id;
    $response['message'] = 'AS 요청이 수정되었습니다.';
    echo json_encode($response);
    exit;
}

if ($action === 'add_member') {
    $com_name = isset($_POST['com_name']) ? trim($_POST['com_name']) : '';
    $phone1 = isset($_POST['phone1']) ? trim($_POST['phone1']) : '';
    $phone2 = isset($_POST['phone2']) ? trim($_POST['phone2']) : '';
    $phone3 = isset($_POST['phone3']) ? trim($_POST['phone3']) : '';
    $sec = isset($_POST['sec']) ? trim($_POST['sec']) : '일반';

    if (empty($com_name) || empty($phone1) || empty($phone2) || empty($phone3)) {
        $response['message'] = '모든 항목을 입력해주세요.';
        echo json_encode($response);
        exit;
    }

    // 업체 종류 유효성 검사
    $valid_sec = array('일반', '대리점', '딜러');
    if (!in_array($sec, $valid_sec)) {
        $sec = '일반';
    }

    $com_name_esc = mysql_real_escape_string($com_name);
    $phone1_esc = mysql_real_escape_string($phone1);
    $phone2_esc = mysql_real_escape_string($phone2);
    $phone3_esc = mysql_real_escape_string($phone3);
    $sec_esc = mysql_real_escape_string($sec);

    $query = "INSERT INTO step11_member (s11_sec, s11_com_name, s11_phone1, s11_phone2, s11_phone3, s11_phone4, s11_phone5, s11_phone6, s11_com_num1, s11_com_num2, s11_com_num3, s11_com_zip1, s11_com_zip2, s11_oaddr, s11_com_sec1, s11_com_sec2) VALUES ('$sec_esc', '$com_name_esc', '$phone1_esc', '$phone2_esc', '$phone3_esc', '0', '0', '0', '000', '00', '00000', '000', '00', '', '', '')";

    $result = @mysql_query($query);
    if ($result) {
        $new_id = mysql_insert_id();
        $response['success'] = true;
        $response['member_id'] = $new_id;
        $response['com_name'] = $com_name;
        $response['message'] = '업체가 등록되었습니다.';
    } else {
        $response['message'] = '등록 중 오류가 발생했습니다.';
    }
    echo json_encode($response);
    exit;
}

// completeAS 액션 처리
if ($action === 'completeAS') {
    $asid = isset($_POST['asid']) ? intval($_POST['asid']) : 0;

    if (empty($asid)) {
        $response['message'] = 'AS ID가 필요합니다.';
        echo json_encode($response);
        exit;
    }

    // 현재 datetime 생성
    $now = date('Y-m-d H:i:s');
    $date_part = date('ymd'); // yymmdd 형식

    // 같은 날짜의 이전 레코드 개수 + 1 = 순번
    $count_query = "SELECT COUNT(*) as cnt FROM step13_as WHERE DATE(s13_as_out_date) = DATE('$now') AND s13_as_out_no IS NOT NULL";
    $count_result = @mysql_query($count_query);
    $count_row = @mysql_fetch_assoc($count_result);
    $seq = intval($count_row['cnt']) + 1;
    $seq_str = str_pad($seq, 3, '0', STR_PAD_LEFT);

    // s13_as_out_no: "NOyymmdd-SSS" 형식
    $new_out_no = 'NO' . $date_part . '-' . $seq_str;
    // s13_as_out_no2: "yymmddSSS" 형식
    $new_out_no2 = $date_part . $seq_str;

    // s13_total_cost 계산 (step18_as_cure_cart의 합계)
    $cost_query = "SELECT COALESCE(SUM(cost1 * s18_quantity), 0) as total_cost FROM step18_as_cure_cart WHERE s18_asid = $asid";
    $cost_result = @mysql_query($cost_query);
    $cost_row = @mysql_fetch_assoc($cost_result);
    $total_cost = intval($cost_row['total_cost'] ?? 0);

    // AS 완료 처리: s13_as_out_date, s13_bank_check에 현재 시간, s13_as_level을 5로, s13_bankcheck_w에 'center' 설정, s13_as_out_no/s13_as_out_no2 생성, s13_total_cost 계산
    $update_query = "UPDATE step13_as SET s13_as_out_date = '$now', s13_bank_check = '$now', s13_as_level = '5', s13_bankcheck_w = 'center', s13_as_out_no = '$new_out_no', s13_as_out_no2 = '$new_out_no2', s13_total_cost = $total_cost WHERE s13_asid = $asid";

    if (@mysql_query($update_query)) {
        $response['success'] = true;
        $response['message'] = 'AS가 완료되었습니다.';
    } else {
        $response['message'] = 'AS 완료 처리 중 오류가 발생했습니다.';
    }
    echo json_encode($response);
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AS 요청 신청 - AS 시스템</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .header-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border: 1px solid white;
            border-radius: 5px;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        .nav-bar {
            background: white;
            padding: 0;
            border-bottom: 2px solid #ddd;
            display: flex;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .nav-item {
            padding: 15px 25px;
            text-decoration: none;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-item:hover {
            background: #f5f5f5;
            color: #667eea;
        }

        .nav-item.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f9f9ff;
        }

        .container {
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }

        .content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background: #764ba2;
        }

        .member-search {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .member-search input {
            flex: 1;
        }

        .member-search button {
            margin: 0;
            white-space: nowrap;
        }

        .member-info {
            background: #f9f9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .member-info.show {
            display: block;
        }

        .member-select {
            display: none;
            background: #f9f9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            max-height: 200px;
            overflow-y: auto;
        }

        .member-select.show {
            display: block;
        }

        .member-option {
            padding: 8px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            border-radius: 3px;
            cursor: pointer;
            background: white;
        }

        .member-option:hover {
            background: #e8f0ff;
        }

        .member-option.selected {
            background: #667eea;
            color: white;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message.show {
            display: block;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>디지탈컴 AS 시스템</h1>
        <div class="header-right">
            <span><?php echo htmlspecialchars($user_name); ?>님</span>
            <form method="POST" action="logout.php" style="margin: 0;">
                <button type="submit" class="logout-btn">로그아웃</button>
            </form>
        </div>
    </div>

    <div class="nav-bar">
        <a href="dashboard.php" class="nav-item">대시보드</a>
        <a href="as_requests.php" class="nav-item <?php echo $current_page === 'as_requests' ? 'active' : ''; ?>">AS
            작업</a>
        <a href="orders.php" class="nav-item">자재 판매</a>
        <a href="parts.php" class="nav-item">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
    </div>

    <div class="container">
        <div class="content">
            <h2><?php echo $is_edit_mode ? 'AS 요청 수정' : 'AS 요청'; ?></h2>

            <div id="successMessage" class="success-message"></div>
            <div id="errorMessage" class="error-message"></div>

            <?php if ($is_edit_mode): ?>
            <input type="hidden" id="editAsId" value="<?php echo $edit_as_id; ?>">
            <?php endif; ?>

            <!-- Step 1: 업체명 확인 -->
            <div class="form-group">
                <label>1단계: 업체명 검색</label>
                <div class="member-search">
                    <input type="text" id="searchMember" placeholder="업체명을 입력하세요"
                        onkeypress="if(event.key==='Enter') searchMember();">
                    <button onclick="searchMember()">검색</button>
                    <button onclick="showNewMemberForm()" style="background: #27ae60;">고객 등록</button>
                </div>
            </div>

            <!-- 검색 결과 -->
            <div id="memberSelect" class="member-select"></div>

            <!-- 선택된 업체 정보 -->
            <div id="memberInfo" class="member-info">
                <strong>선택된 업체:</strong> <span id="selectedMemberName"></span>
                <br><strong>전화:</strong> <span id="selectedMemberPhone"></span>
                <br><strong>고객타입:</strong> <span id="selectedMemberType"></span>
                <input type="hidden" id="selectedMemberId">
            </div>

            <!-- 새 업체 등록 폼 -->
            <div id="newMemberForm"
                style="display:none; background: #f9f9ff; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4>새 업체 등록</h4>
                <div class="form-group">
                    <label>업체명</label>
                    <input type="text" id="newComName" placeholder="업체명을 입력하세요">
                </div>
                <div class="form-group">
                    <label>전화번호</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="text" id="newPhone1" value="010" style="flex: 1;">
                        <span>-</span>
                        <input type="text" id="newPhone2" value="1234" style="flex: 1;">
                        <span>-</span>
                        <input type="text" id="newPhone3" value="5678" style="flex: 1;">
                    </div>
                </div>
                <div class="form-group">
                    <label>업체 종류</label>
                    <div style="display: flex; gap: 20px; margin-top: 10px;">
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                            <input type="radio" name="comSec" value="일반" checked style="cursor: pointer;">
                            일반
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                            <input type="radio" name="comSec" value="대리점" style="cursor: pointer;">
                            대리점
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                            <input type="radio" name="comSec" value="딜러" style="cursor: pointer;">
                            AS센터 공급가(딜러)
                        </label>
                    </div>
                </div>
                <button onclick="addNewMember()" style="background: #27ae60;">등록</button>
                <button onclick="cancelNewMemberForm()" style="background: #95a5a6;">취소</button>
            </div>

            <!-- Step 2: 수령 방법 선택 -->
            <div id="step2Container" class="form-group"
                style="display: none; background: #f0f8ff; padding: 20px; border-radius: 8px; margin-top: 30px; border-left: 4px solid #3498db;">
                <label style="font-size: 16px; font-weight: 600;">2단계: 수령 방법 선택</label>
                <div style="display: flex; gap: 30px; margin-top: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                        <input type="radio" name="inHow" value="내방" checked style="cursor: pointer;">
                        <span>내방</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                        <input type="radio" name="inHow" value="택배" style="cursor: pointer;">
                        <span>택배</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 0;">
                        <input type="radio" name="inHow" value="퀵" style="cursor: pointer;">
                        <span>퀵</span>
                    </label>
                </div>
                <input type="hidden" id="selectedInHow" value="내방">
            </div>

            <!-- Step 3: 제품 및 불량증상 선택 -->
            <div id="step3Container" class="form-group"
                style="display: none; background: #f0fff0; padding: 20px; border-radius: 8px; margin-top: 30px; border-left: 4px solid #27ae60;">
                <label style="font-size: 16px; font-weight: 600;">3단계: 제품 및 불량증상 등록</label>

                <!-- 제품 선택 -->
                <div class="form-group" style="margin-top: 15px;">
                    <label>제품 선택</label>
                    <select id="modelSelect"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                        <option value="">제품을 선택하세요</option>
                    </select>
                </div>

                <!-- 불량증상 선택 -->
                <div class="form-group">
                    <label>불량증상</label>
                    <select id="poorSelect"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                        <option value="">불량증상을 선택하세요</option>
                    </select>
                </div>

                <!-- 추가 버튼 -->
                <div style="margin-top: 15px;">
                    <button onclick="addProductToCart()" style="background: #27ae60; width: 100%; padding: 12px; font-size: 15px; font-weight: 600;">제품 추가</button>
                </div>

                <!-- 추가된 제품 목록 -->
                <div style="margin-top: 20px;">
                    <label style="font-size: 14px; font-weight: 600; display: block; margin-bottom: 10px;">추가된 제품
                        목록</label>
                    <table style="width: 100%; border-collapse: collapse; background: white;">
                        <thead>
                            <tr style="background: #f0f0f0; border-bottom: 2px solid #ddd;">
                                <th style="padding: 10px; text-align: left;">제품</th>
                                <th style="padding: 10px; text-align: left;">불량증상</th>
                                <th style="padding: 10px; text-align: center; width: 80px;">삭제</th>
                            </tr>
                        </thead>
                        <tbody id="selectedProductsBody">
                            <tr>
                                <td colspan="3" style="padding: 20px; text-align: center; color: #999;">추가된 제품이 없습니다.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Step 4 이상 추가 예정 -->
            <div
                style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #ffc107; border-radius: 5px; color: #666;">
                <p><strong>📝 추후 추가될 기능:</strong></p>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Step 4: AS 내용 등록</li>
                    <li>최종 저장</li>
                </ul>
            </div>

            <!-- 저장/취소 버튼 -->
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                <div id="submitButtonContainer" style="display: none;">
                    <button onclick="saveAsRequest()" class="btn-submit" id="submitBtn"><?php echo $is_edit_mode ? 'AS 작업 수정' : 'AS 작업 등록'; ?></button>
                </div>
                <button onclick="location.href='as_requests.php'" class="btn-cancel">취소</button>
            </div>

        </div>
    </div>

    <script>
        let selectedMemberId = null;
        let selectedProducts = [];
        let isEditMode = <?php echo $is_edit_mode ? 'true' : 'false'; ?>;
        let editAsId = <?php echo $is_edit_mode ? $edit_as_id : '0'; ?>;

        // 페이지 로드 시 Edit mode 초기화
        document.addEventListener('DOMContentLoaded', function() {
            if (isEditMode) {
                loadExistingAsRequest(editAsId);
            }
        });

        // 기존 AS 요청 데이터 로드
        function loadExistingAsRequest(asId) {
            fetch('as_request_handler.php?action=get_as_request_data', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'as_id=' + encodeURIComponent(asId)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // 회원 정보 선택
                        selectMember(
                            data.member_info.s11_meid,
                            data.member_info.s11_com_name,
                            data.member_info.s11_phone1,
                            data.member_info.s11_phone2,
                            data.member_info.s11_phone3
                        );

                        // 수령 방법 설정
                        const inHowRadios = document.querySelectorAll('input[name="inHow"]');
                        inHowRadios.forEach(radio => {
                            if (radio.value === data.as_info.s13_as_in_how) {
                                radio.checked = true;
                                document.getElementById('selectedInHow').value = data.as_info.s13_as_in_how;
                            }
                        });

                        // 기존 제품 목록 로드
                        selectedProducts = [];
                        data.products.forEach(product => {
                            selectedProducts.push(product);
                        });
                        updateSelectedProductsList();
                        updateSubmitButtonVisibility();

                        showSuccess('기존 AS 요청이 로드되었습니다.');
                    } else {
                        showError(data.message);
                    }
                })
                .catch(err => {
                    console.error('데이터 로드 오류:', err);
                    showError('AS 요청 데이터를 불러올 수 없습니다.');
                });
        }

        function searchMember() {
            const searchName = document.getElementById('searchMember').value.trim();
            if (!searchName) {
                alert('업체명을 입력하세요.');
                return;
            }

            fetch('as_request_handler.php?action=search_member', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'search_name=' + encodeURIComponent(searchName)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        displayMemberList(data.members);
                    } else {
                        showError(data.message);
                        document.getElementById('memberSelect').innerHTML = '';
                    }
                });
        }

        function displayMemberList(members) {
            const select = document.getElementById('memberSelect');
            select.innerHTML = '';
            members.forEach(member => {
                const div = document.createElement('div');
                div.className = 'member-option';

                // 고객 타입 표시 (딜러는 특별히 표시)
                let typeDisplay = member.s11_sec;
                if (member.s11_sec === '딜러') {
                    typeDisplay = '딜러(AS center 공급가)';
                }

                div.innerHTML = `${member.s11_com_name} (${member.s11_phone1}-${member.s11_phone2}-${member.s11_phone3}) <span style="font-size: 12px; color: #999; margin-left: 8px;">${typeDisplay}</span>`;
                div.onclick = () => selectMember(member.s11_meid, member.s11_com_name, member.s11_phone1, member.s11_phone2, member.s11_phone3, member.s11_sec);
                select.appendChild(div);
            });
            select.classList.add('show');
        }

        function selectMember(id, name, phone1, phone2, phone3, sec) {
            selectedMemberId = id;
            document.getElementById('selectedMemberId').value = id;
            document.getElementById('selectedMemberName').textContent = name;
            document.getElementById('selectedMemberPhone').textContent = phone1 + '-' + phone2 + '-' + phone3;

            // 고객 타입 표시 (딜러는 특별히 표시)
            let typeDisplay = sec;
            if (sec === '딜러') {
                typeDisplay = '딜러(AS center 공급가)';
            }
            document.getElementById('selectedMemberType').textContent = typeDisplay;

            document.getElementById('memberInfo').classList.add('show');
            document.getElementById('memberSelect').classList.remove('show');
            document.getElementById('newMemberForm').style.display = 'none';
            document.getElementById('searchMember').value = '';

            // Step 2 표시
            document.getElementById('step2Container').style.display = 'block';

            // Step 2 라디오 버튼 change 이벤트 설정
            setupStep2Events();

            // Step 3 표시 및 데이터 로드
            document.getElementById('step3Container').style.display = 'block';
            loadStep3Data();

            // 제출 버튼 가시성 업데이트
            updateSubmitButtonVisibility();

            showSuccess('업체가 선택되었습니다.');
        }

        function showNewMemberForm() {
            document.getElementById('newMemberForm').style.display = 'block';
            document.getElementById('memberSelect').classList.remove('show');
        }

        function cancelNewMemberForm() {
            document.getElementById('newMemberForm').style.display = 'none';
        }

        function addNewMember() {
            const comName = document.getElementById('newComName').value.trim();
            const phone1 = document.getElementById('newPhone1').value.trim();
            const phone2 = document.getElementById('newPhone2').value.trim();
            const phone3 = document.getElementById('newPhone3').value.trim();
            const sec = document.querySelector('input[name="comSec"]:checked').value;

            if (!comName || !phone1 || !phone2 || !phone3) {
                showError('모든 항목을 입력해주세요.');
                return;
            }

            fetch('as_request_handler.php?action=add_member', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'com_name=' + encodeURIComponent(comName) + '&phone1=' + encodeURIComponent(phone1) + '&phone2=' + encodeURIComponent(phone2) + '&phone3=' + encodeURIComponent(phone3) + '&sec=' + encodeURIComponent(sec)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        selectMember(data.member_id, comName, phone1, phone2, phone3, sec);
                        document.getElementById('newMemberForm').style.display = 'none';
                        document.getElementById('newComName').value = '';
                        document.getElementById('newPhone1').value = '';
                        document.getElementById('newPhone2').value = '';
                        document.getElementById('newPhone3').value = '';
                        document.querySelector('input[name="comSec"]').checked = true;
                        showSuccess(data.message);
                    } else {
                        showError(data.message);
                    }
                });
        }

        function showSuccess(msg) {
            const el = document.getElementById('successMessage');
            el.textContent = msg;
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 4000);
        }

        function showError(msg) {
            const el = document.getElementById('errorMessage');
            el.textContent = msg;
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 4000);
        }

        // Step 2: 수령 방법 선택 이벤트
        function setupStep2Events() {
            const inHowRadios = document.querySelectorAll('input[name="inHow"]');
            inHowRadios.forEach(radio => {
                radio.addEventListener('change', function () {
                    document.getElementById('selectedInHow').value = this.value;
                });
            });
        }

        // Step 3: 제품 및 불량증상 데이터 로드
        function loadStep3Data() {
            fetch('as_request_handler.php?action=load_step3_data')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // 제품 드롭다운 채우기
                        const modelSelect = document.getElementById('modelSelect');
                        modelSelect.innerHTML = '<option value="">제품을 선택하세요</option>';
                        data.models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model.s15_amid;
                            option.textContent = model.s15_model_name + (model.s15_model_sn ? ` (${model.s15_model_sn})` : '');
                            modelSelect.appendChild(option);
                        });

                        // 불량증상 드롭다운 채우기
                        const poorSelect = document.getElementById('poorSelect');
                        poorSelect.innerHTML = '<option value="">불량증상을 선택하세요</option>';
                        data.poors.forEach(poor => {
                            const option = document.createElement('option');
                            option.value = poor.s16_apid;
                            option.textContent = poor.s16_poor;
                            poorSelect.appendChild(option);
                        });

                        // Step 3 드롭다운 change 이벤트 설정
                        setupStep3Events();
                    } else {
                        showError('제품 및 불량증상 데이터를 불러올 수 없습니다.');
                    }
                })
                .catch(err => {
                    console.error('Step 3 데이터 로드 오류:', err);
                    showError('데이터 로드 중 오류가 발생했습니다.');
                });
        }

        // Step 3: 제품 및 불량증상 선택 이벤트
        function setupStep3Events() {
            // 이벤트 리스너 설정 (필요시 추가)
        }

        // Step 3: 제품을 카트에 추가
        function addProductToCart() {
            const modelSelect = document.getElementById('modelSelect');
            const poorSelect = document.getElementById('poorSelect');

            const modelId = modelSelect.value;
            const modelName = modelSelect.options[modelSelect.selectedIndex].text;
            const poorId = poorSelect.value;
            const poorName = poorSelect.options[poorSelect.selectedIndex].text;

            // 유효성 검사
            if (!modelId || modelId === '') {
                showError('제품을 선택해주세요.');
                return;
            }

            if (!poorId || poorId === '') {
                showError('불량증상을 선택해주세요.');
                return;
            }

            // 선택된 제품을 배열에 추가
            selectedProducts.push({
                model_id: modelId,
                model_name: modelName,
                poor_id: poorId,
                poor_name: poorName
            });

            // 목록 업데이트
            updateSelectedProductsList();

            // 제출 버튼 가시성 업데이트
            updateSubmitButtonVisibility();

            // 드롭다운 초기화
            modelSelect.value = '';
            poorSelect.value = '';

            showSuccess('제품이 추가되었습니다.');
        }

        // Step 3: 추가된 제품 목록 업데이트
        function updateSelectedProductsList() {
            const tbody = document.getElementById('selectedProductsBody');
            tbody.innerHTML = '';

            if (selectedProducts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="padding: 20px; text-align: center; color: #999;">추가된 제품이 없습니다.</td></tr>';
                return;
            }

            selectedProducts.forEach((product, index) => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #eee';
                tr.innerHTML = `
                    <td style="padding: 10px;">${product.model_name}</td>
                    <td style="padding: 10px;">${product.poor_name}</td>
                    <td style="padding: 10px; text-align: center;">
                        <button onclick="removeFromSelectedProducts(${index})" style="background: #e74c3c; color: white; padding: 5px 12px; border: none; border-radius: 3px; cursor: pointer;">삭제</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Step 3: 제품 삭제
        function removeFromSelectedProducts(index) {
            selectedProducts.splice(index, 1);
            updateSelectedProductsList();
            updateSubmitButtonVisibility();
        }

        // 제출 버튼 가시성 업데이트
        function updateSubmitButtonVisibility() {
            const submitButton = document.getElementById('submitButtonContainer');

            // 업체명이 선택되고 제품이 1개 이상 추가되었을 때만 표시
            if (selectedMemberId && selectedProducts.length > 0) {
                submitButton.style.display = 'block';
            } else {
                submitButton.style.display = 'none';
            }
        }

        // AS 요청 저장
        function saveAsRequest() {
            // 버튼 중복 클릭 방지
            const submitBtn = document.querySelector('button[onclick="saveAsRequest()"]');
            if (submitBtn.disabled) {
                return;
            }

            if (!selectedMemberId) {
                alert('업체를 선택해주세요.');
                return;
            }

            if (selectedProducts.length === 0) {
                alert('제품을 1개 이상 추가해주세요.');
                return;
            }

            // 버튼 비활성화
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
            const originalText = submitBtn.textContent;
            submitBtn.textContent = isEditMode ? '수정 중...' : '등록 중...';

            // 제품 배열을 JSON 형식으로 변환
            const productsArray = selectedProducts.map(product => ({
                model_id: product.model_id,
                poor_id: product.poor_id
            }));

            const inHow = document.getElementById('selectedInHow').value || '내방';

            // URL Encoded 형식으로 변환
            let body = 'member_id=' + encodeURIComponent(selectedMemberId) +
                '&in_how=' + encodeURIComponent(inHow) +
                '&products=' + encodeURIComponent(JSON.stringify(productsArray));

            // Edit mode일 때는 as_id 추가
            if (isEditMode) {
                body += '&as_id=' + encodeURIComponent(editAsId);
            }

            const action = isEditMode ? 'update_as_request' : 'save_as_request';
            fetch('as_request_handler.php?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showSuccess(data.message);
                        setTimeout(() => {
                            location.href = 'as_requests.php';
                        }, 1500);
                    } else {
                        showError('❌ ' + data.message);
                        // 실패 시 버튼 복구
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('에러 발생:', error);
                    showError('요청 중 오류가 발생했습니다: ' + error);
                    // 실패 시 버튼 복구
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                    submitBtn.textContent = originalText;
                });
        }
    </script>
</body>

</html>
<?php mysql_close($connect); ?>