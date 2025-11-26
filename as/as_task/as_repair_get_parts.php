<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 로그인 확인
if (empty($_SESSION['member_id']) || empty($_SESSION['member_sid'])) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

require_once '../db_config.php';

$search_key = isset($_POST['search_key']) ? trim($_POST['search_key']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : 'all';
$member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 회원 구분 조회
$sec = '일반';  // 기본값
if ($member_id > 0) {
    $member_query = @mysql_query("SELECT s11_sec FROM step11_member WHERE s11_meid = $member_id");
    if ($member_query && mysql_num_rows($member_query) > 0) {
        $member_row = mysql_fetch_assoc($member_query);
        $sec = $member_row['s11_sec'];
    }
}

// WHERE 조건 구성
$where = "1=1";

// 카테고리 필터
if ($category !== 'all' && !empty($category)) {
    $where .= " AND p.s1_caid = '" . mysql_real_escape_string($category) . "'";
}

// 검색어
if (!empty($search_key)) {
    $search_esc = mysql_real_escape_string($search_key);
    $where .= " AND p.s1_name LIKE '%$search_esc%'";
}

// 총 개수 조회
$count_query = "SELECT COUNT(*) as total FROM step1_parts p WHERE $where";
$count_result = @mysql_query($count_query);
$count_row = mysql_fetch_assoc($count_result);
$total_count = intval($count_row['total']);
$total_pages = ceil($total_count / $per_page);

// 자재 조회 (AS 수리용 가격 필드)
$query = "SELECT p.s1_uid, p.s1_name, p.s1_caid, c.s5_category,
                 p.s1_cost_c_1, p.s1_cost_a_2, p.s1_cost_n_2
          FROM step1_parts p
          LEFT JOIN step5_category c ON p.s1_caid = c.s5_caid
          WHERE $where
          ORDER BY p.s1_uid DESC
          LIMIT $per_page OFFSET $offset";

$result = @mysql_query($query);
$parts = array();

if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_assoc($result)) {
        // AS 수리용 가격 선택
        $price = 0;
        if ($sec === '일반') {
            $price = floatval($row['s1_cost_n_2']);  // 일반 수리 가격
        } elseif ($sec === '대리점') {
            $price = floatval($row['s1_cost_a_2']);  // 대리점 수리 가격
        } else {  // 딜러
            $price = floatval($row['s1_cost_c_1']);  // AS센터 공급가
        }

        $parts[] = array(
            's1_uid' => $row['s1_uid'],
            's1_name' => $row['s1_name'],
            's1_caid' => $row['s1_caid'],
            's5_category' => $row['s5_category'],
            'price' => $price
        );
    }
}

echo json_encode([
    'success' => true,
    'parts' => $parts,
    'page' => $page,
    'total_pages' => $total_pages,
    'total_count' => $total_count
]);

mysql_close($connect);
?>
