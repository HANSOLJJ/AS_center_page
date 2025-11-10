<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

// MySQL 호환성 레이어 로드
require_once 'mysql_compat.php';

// 데이터베이스 연결
$connect = mysql_connect('mysql', 'mic4u_user', 'change_me');
mysql_select_db('mic4u', $connect);

$response = array('success' => false, 'message' => '');

// 요청 방식에 따른 처리
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'save_repair_step') {
    // 수리 방법 선택 및 자재 정보 저장
    $itemid = isset($_POST['itemid']) ? intval($_POST['itemid']) : 0;
    $as_end_result = isset($_POST['as_end_result']) ? trim($_POST['as_end_result']) : '';
    $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
    $parts_data_json = isset($_POST['parts_data']) ? $_POST['parts_data'] : '[]';

    // 유효성 검사
    if (empty($itemid) || empty($as_end_result)) {
        $response['message'] = '아이템 ID와 수리 방법이 필요합니다.';
        echo json_encode($response);
        exit;
    }

    // 해당 아이템이 존재하는지 확인 및 AS ID 조회
    $verify_query = "SELECT s14_aiid, s14_asid FROM step14_as_item WHERE s14_aiid = $itemid";
    $verify_result = @mysql_query($verify_query);

    if (!$verify_result || mysql_num_rows($verify_result) == 0) {
        $response['message'] = 'AS 아이템을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }

    $item_row = mysql_fetch_assoc($verify_result);
    $asid = intval($item_row['s14_asid']);

    // Parts 데이터 파싱
    $parts_data = json_decode($parts_data_json, true);
    if (!is_array($parts_data)) {
        $parts_data = array();
    }

    // 자재 정보 저장 (step18_as_cure_cart)
    // 기존 자재들 조회 (s18_accid 유지를 위해)
    $existing_parts_query = "SELECT s18_accid, s18_uid FROM step18_as_cure_cart WHERE s18_aiid = $itemid";
    $existing_result = @mysql_query($existing_parts_query);
    $existing_map = array(); // part_id => s18_accid 매핑

    if ($existing_result) {
        while ($row = mysql_fetch_assoc($existing_result)) {
            $existing_map[intval($row['s18_uid'])] = intval($row['s18_accid']);
        }
    }

    // 들어온 부품 part_id 목록
    $incoming_part_ids = array();
    foreach ($parts_data as $part) {
        $incoming_part_ids[] = intval($part['part_id']);
    }

    // 기존에 있었지만 들어온 데이터에 없는 자재는 삭제
    foreach ($existing_map as $part_id => $accid) {
        if (!in_array($part_id, $incoming_part_ids)) {
            $delete_query = "DELETE FROM step18_as_cure_cart WHERE s18_accid = $accid";
            @mysql_query($delete_query);
        }
    }

    // 새 자재들 insert/update
    $insert_success = true;
    if (count($parts_data) > 0) {
        $signdate = date('Y-m-d H:i:s');

        foreach ($parts_data as $part) {
            $part_id = isset($part['part_id']) ? intval($part['part_id']) : 0;
            $part_name = isset($part['part_name']) ? trim($part['part_name']) : '';
            $cost = isset($part['cost']) ? floatval($part['cost']) : 0;
            $quantity = isset($part['quantity']) ? intval($part['quantity']) : 1;

            if ($part_id <= 0 || empty($part_name)) {
                $insert_success = false;
                break;
            }

            // 기본 고객타입 (AS 수리용 - 대리점 또는 일반 선택 가능)
            $cost_sec = '일반';

            $part_name_esc = mysql_real_escape_string($part_name);
            $cost_sec_esc = mysql_real_escape_string($cost_sec);

            // 기존 자재인지 확인
            if (isset($existing_map[$part_id])) {
                // 기존 자재: UPDATE (s18_accid 유지)
                $update_query = "UPDATE step18_as_cure_cart
                                SET s18_quantity = $quantity, cost_name = '$part_name_esc', cost1 = $cost, cost_sec = '$cost_sec_esc', s18_signdate = '$signdate'
                                WHERE s18_accid = " . $existing_map[$part_id];
                if (!@mysql_query($update_query)) {
                    $insert_success = false;
                    break;
                }
            } else {
                // 새 자재: INSERT
                $insert_query = "INSERT INTO step18_as_cure_cart
                                (s18_asid, s18_aiid, s18_uid, s18_quantity, cost_name, cost1, cost_sec, s18_signdate)
                                VALUES ($asid, $itemid, $part_id, $quantity, '$part_name_esc', $cost, '$cost_sec_esc', '$signdate')";
                if (!@mysql_query($insert_query)) {
                    $insert_success = false;
                    break;
                }
            }
        }
    }

    // 모든 자재 저장 성공 시 as_end_result 및 제품명도 저장
    if ($insert_success) {
        $as_end_result_esc = mysql_real_escape_string($as_end_result);

        // 제품 ID가 있으면 제품명 조회
        $product_model_id = intval($product_name);
        $model_name = '';

        if ($product_model_id > 0) {
            $model_query = "SELECT s15_model_name FROM step15_as_model WHERE s15_amid = $product_model_id";
            $model_result = @mysql_query($model_query);
            if ($model_result && mysql_num_rows($model_result) > 0) {
                $model_row = mysql_fetch_assoc($model_result);
                $model_name = $model_row['s15_model_name'];
            }
        }

        // 자재 종류의 개수 계산 (s14_cart)
        $cart_count_query = "SELECT COUNT(*) as cart_count FROM step18_as_cure_cart WHERE s18_aiid = $itemid";
        $cart_count_result = @mysql_query($cart_count_query);
        $cart_count = 0;
        if ($cart_count_result) {
            $cart_row = mysql_fetch_assoc($cart_count_result);
            $cart_count = intval($cart_row['cart_count']);
        }

        if (!empty($model_name)) {
            $model_name_esc = mysql_real_escape_string($model_name);
            $update_query = "UPDATE step14_as_item SET as_end_result = '$as_end_result_esc', s14_model = $product_model_id, cost_name = '$model_name_esc', s14_cart = $cart_count WHERE s14_aiid = $itemid";
        } else {
            // 제품 ID가 없거나 조회 실패 시 as_end_result와 s14_cart만 업데이트
            $update_query = "UPDATE step14_as_item SET as_end_result = '$as_end_result_esc', s14_cart = $cart_count WHERE s14_aiid = $itemid";
        }

        if (@mysql_query($update_query)) {
            $response['success'] = true;
            $response['message'] = '수리 정보가 저장되었습니다.';
        } else {
            $response['message'] = '수리 방법 저장 중 오류가 발생했습니다: ' . mysql_error();
        }
    } else {
        $response['message'] = '자재 정보 저장 중 오류가 발생했습니다.';
    }

    echo json_encode($response);
    exit;
}

// 알 수 없는 action
$response['message'] = '알 수 없는 요청입니다.';
echo json_encode($response);

mysql_close($connect);
?>