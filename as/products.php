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
$current_page = 'products';

// 현재 탭 설정
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'model';
$success_message = '';

// DELETE 요청 처리 (GET 기반)
if (isset($_GET['action']) && strpos($_GET['action'], 'delete_') === 0) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id > 0) {
        // 외래 키 제약 비활성화
        mysql_query("SET FOREIGN_KEY_CHECKS = 0");

        $delete_success = false;
        if ($action === 'delete_model') {
            $delete_result = mysql_query("DELETE FROM step15_as_model WHERE s15_amid = $id");
            $delete_success = ($delete_result !== false && mysql_affected_rows() > 0);
            $success_message = $delete_success ? '모델이 정상적으로 삭제되었습니다.' : '';
        } elseif ($action === 'delete_poor') {
            $delete_result = mysql_query("DELETE FROM step16_as_poor WHERE s16_apid = $id");
            $delete_success = ($delete_result !== false && mysql_affected_rows() > 0);
            $success_message = $delete_success ? '불량증상이 정상적으로 삭제되었습니다.' : '';
        } elseif ($action === 'delete_result') {
            $delete_result = mysql_query("DELETE FROM step19_as_result WHERE s19_asrid = $id");
            $delete_success = ($delete_result !== false && mysql_affected_rows() > 0);
            $success_message = $delete_success ? 'AS 결과가 정상적으로 삭제되었습니다.' : '';
        }

        // 외래 키 제약 다시 활성화
        mysql_query("SET FOREIGN_KEY_CHECKS = 1");

        // 성공시 기능 탭으로 리다이렉트
        if ($delete_success) {
            header('Location: products.php?tab=' . urlencode($current_tab) . '&deleted=1');
            exit;
        }
    }
}

// 삭제 스링 메시지 확인
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $success_message = "✓ 정상적으로 삭제되었습니다.";
}

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 검색 조건
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : (isset($_POST['search_keyword']) ? trim($_POST['search_keyword']) : '');

// 탭별 데이터 처리
$where = "1=1";
$table = '';
$id_field = '';
$name_field = '';

if ($current_tab === 'model') {
    $table = 'step15_as_model';
    $id_field = 's15_amid';
    $name_field = 's15_model_name';
    if (!empty($search_keyword)) {
        $search_keyword_esc = mysql_real_escape_string($search_keyword);
        $where .= " AND s15_model_name LIKE '%" . $search_keyword_esc . "%'";
    }
} elseif ($current_tab === 'poor') {
    $table = 'step16_as_poor';
    $id_field = 's16_apid';
    $name_field = 's16_poor';
    if (!empty($search_keyword)) {
        $search_keyword_esc = mysql_real_escape_string($search_keyword);
        $where .= " AND s16_poor LIKE '%" . $search_keyword_esc . "%'";
    }
} elseif ($current_tab === 'result') {
    $table = 'step19_as_result';
    $id_field = 's19_asrid';
    $name_field = 's19_result';
    if (!empty($search_keyword)) {
        $search_keyword_esc = mysql_real_escape_string($search_keyword);
        $where .= " AND s19_result LIKE '%" . $search_keyword_esc . "%'";
    }
}

// 전체 개수 조회
$count_result = mysql_query("SELECT COUNT(*) as cnt FROM $table WHERE $where");
$count_row = mysql_fetch_assoc($count_result);
$total_count = $count_row['cnt'];
$total_pages = ceil($total_count / $limit);

// 데이터 조회
if ($current_tab === 'model') {
    $result = mysql_query("
        SELECT s15_amid, s15_model_name, s15_model_sn
        FROM step15_as_model
        WHERE $where
        ORDER BY s15_amid DESC
        LIMIT $offset, $limit
    ");
} else {
    $result = mysql_query("
        SELECT $id_field, $name_field
        FROM $table
        WHERE $where
        ORDER BY $id_field DESC
        LIMIT $offset, $limit
    ");
}

// 자동완성 데이터 조회 (AJAX 요청일 경우)
if (isset($_GET['action']) && $_GET['action'] === 'autocomplete') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'model';

    header('Content-Type: application/json; charset=utf-8');

    $suggestions = array();
    if (!empty($q)) {
        if ($tab === 'model') {
            $query = "SELECT DISTINCT s15_model_name FROM step15_as_model WHERE s15_model_name LIKE '%" . mysql_real_escape_string($q) . "%' LIMIT 10";
        } elseif ($tab === 'poor') {
            $query = "SELECT DISTINCT s16_poor FROM step16_as_poor WHERE s16_poor LIKE '%" . mysql_real_escape_string($q) . "%' LIMIT 10";
        } elseif ($tab === 'result') {
            $query = "SELECT DISTINCT s19_result FROM step19_as_result WHERE s19_result LIKE '%" . mysql_real_escape_string($q) . "%' LIMIT 10";
        }

        if (isset($query)) {
            $res = mysql_query($query);
            while ($row = mysql_fetch_assoc($res)) {
                $field = $current_tab === 'model' ? 's15_model_name' : ($current_tab === 'poor' ? 's16_poor' : 's19_result');
                $suggestions[] = array(
                    'value' => $row[$field],
                    'label' => $row[$field]
                );
            }
        }
    }

    echo json_encode($suggestions);
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>제품 관리 - AS 시스템</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-btn:hover {
            background: #764ba2;
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

        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
        }

        .tab-button {
            padding: 12px 24px;
            background: #f0f0f0;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
            border-radius: 5px 5px 0 0;
        }

        .tab-button:hover {
            background: #e0e0e0;
        }

        .tab-button.active {
            background: #667eea;
            color: white;
            border-bottom: 3px solid #667eea;
        }

        .message {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            background: #efe;
            border: 1px solid #9f9;
            color: #3c3;
        }

        .message.error {
            background: #fee;
            border-color: #f99;
            color: #c33;
        }

        .search-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .search-box input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            flex: 1;
        }

        .search-box button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .search-box button:hover {
            background: #764ba2;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .register-btn {
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .register-btn:hover {
            background: #229954;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        th:last-child,
        td:last-child {
            border-right: none;
        }

        th {
            background: #667eea;
            color: white;
            font-weight: 700;
        }

        tr:hover {
            background: #f9f9f9;
        }

        /* Column 너비 정의 - Model Tab */
        table col.c-no {
            width: 8%;
        }

        table col.c-name {
            width: auto;
        }

        table col.c-sn {
            width: 20%;
        }

        table col.c-edit {
            width: 7%;
        }

        table col.c-del {
            width: 7%;
        }

        .action-link {
            padding: 5px 10px;
            margin: 0 3px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
            font-size: 12px;
            cursor: pointer;
        }

        .edit-link {
            background: #3498db;
            color: white;
        }

        .edit-link:hover {
            background: #2980b9;
        }

        .delete-link {
            background: #e74c3c;
            color: white;
        }

        .delete-link:hover {
            background: #c0392b;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            margin: 0 3px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: inline-block;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .info-text {
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .ac_results {
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .ac_results li {
            padding: 5px 10px;
            cursor: pointer;
        }

        .ac_results li.ac_over {
            background: #667eea;
            color: white;
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
        <a href="as_requests.php" class="nav-item">AS 작업</a>
        <a href="orders.php" class="nav-item">자재 판매</a>
        <a href="parts.php" class="nav-item">자재 관리</a>
        <a href="members.php" class="nav-item">고객 관리</a>
        <a href="products.php" class="nav-item <?php echo $current_page === 'products' ? 'active' : ''; ?>">제품 관리</a>
    </div>

    <div class="container">

        <div class="content">
            <h2>🎤 제품 관리</h2>

            <!-- 탭 버튼 -->
            <div class="tabs">
                <button class="tab-button <?php echo $current_tab === 'model' ? 'active' : ''; ?>"
                    onclick="location.href='products.php?tab=model'">모델 관리</button>
                <button class="tab-button <?php echo $current_tab === 'poor' ? 'active' : ''; ?>"
                    onclick="location.href='products.php?tab=poor'">불량증상 타입 관리</button>
                <button class="tab-button <?php echo $current_tab === 'result' ? 'active' : ''; ?>"
                    onclick="location.href='products.php?tab=result'">AS 결과 타입 관리</button>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="message">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- 검색 박스 (자동완성 포함) -->
            <form method="GET" class="search-box" id="search-form">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
                <input type="text" id="search_keyword" name="search_keyword" placeholder="검색어를 입력하세요"
                    value="<?php echo htmlspecialchars($search_keyword); ?>">
                <button type="submit">검색</button>
                <a href="products.php?tab=<?php echo htmlspecialchars($current_tab); ?>"
                    style="padding: 10px 20px; background: #95a5a6; color: white; border-radius: 5px; text-decoration: none;">초기화</a>
            </form>

            <!-- 액션 버튼 -->
            <div class="action-buttons">
                <?php if ($current_tab === 'model'): ?>
                    <button class="register-btn" onclick="location.href='product_add.php'">+ 새 제품 등록</button>
                <?php elseif ($current_tab === 'poor'): ?>
                    <button class="register-btn" onclick="location.href='poor_add.php'">+ 새 불량증상 등록</button>
                <?php elseif ($current_tab === 'result'): ?>
                    <button class="register-btn" onclick="location.href='result_add.php'">+ 새 결과 타입 등록</button>
                <?php endif; ?>
            </div>

            <!-- 정보 텍스트 -->
            <div class="info-text">
                총 <?php echo $total_count; ?>개의 정보 (페이지: <?php echo $page; ?>/<?php echo max(1, $total_pages); ?>)
            </div>

            <!-- 테이블 -->
            <table id="data-table">
                <colgroup>
                    <?php if ($current_tab === 'model'): ?>
                        <col class="c-no">
                        <col class="c-name">
                        <col class="c-sn">
                        <col class="c-edit">
                        <col class="c-del">
                    <?php else: ?>
                        <col class="c-no">
                        <col class="c-name">
                        <col class="c-edit">
                        <col class="c-del">
                    <?php endif; ?>
                </colgroup>
                <thead>
                    <tr>
                        <?php if ($current_tab === 'model'): ?>
                            <th>번호</th>
                            <th>모델명</th>
                            <th>시리얼 및 버전</th>
                            <th colspan="2">관리</th>
                        <?php else: ?>
                            <th>번호</th>
                            <th><?php echo $current_tab === 'poor' ? '불량증상' : 'AS 결과 타입'; ?></th>
                            <th colspan="2">관리</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && mysql_num_rows($result) > 0) {
                        while ($row = mysql_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row[$id_field]) . "</td>";

                            if ($current_tab === 'model') {
                                echo "<td>" . htmlspecialchars($row['s15_model_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['s15_model_sn']) . "</td>";
                                echo "<td>";
                                echo "<a href='product_edit.php?id=" . $row['s15_amid'] . "' class='action-link edit-link'>수정</a>";
                                echo "</td>";
                                echo "<td>";
                                echo "<a href='products.php?action=delete_model&id=" . $row['s15_amid'] . "&tab=" . urlencode($current_tab) . "' class='action-link delete-link' onclick=\"return confirm('삭제하시겠습니까?');\">삭제</a>";
                                echo "</td>";
                            } else {
                                echo "<td>" . htmlspecialchars($row[$name_field]) . "</td>";
                                echo "<td>";
                                if ($current_tab === 'poor') {
                                    echo "<a href='poor_edit.php?id=" . $row['s16_apid'] . "' class='action-link edit-link'>수정</a>";
                                } else {
                                    echo "<a href='result_edit.php?id=" . $row['s19_asrid'] . "' class='action-link edit-link'>수정</a>";
                                }
                                echo "</td>";
                                echo "<td>";
                                if ($current_tab === 'poor') {
                                    echo "<a href='products.php?action=delete_poor&id=" . $row['s16_apid'] . "&tab=" . urlencode($current_tab) . "' class='action-link delete-link' onclick=\"return confirm('삭제하시겠습니까?');\">삭제</a>";
                                } else {
                                    echo "<a href='products.php?action=delete_result&id=" . $row['s19_asrid'] . "&tab=" . urlencode($current_tab) . "' class='action-link delete-link' onclick=\"return confirm('삭제하시겠습니까?');\">삭제</a>";
                                }
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        $colspan = ($current_tab === 'model') ? 5 : 4;
                        echo "<tr><td colspan='$colspan' style='text-align: center; color: #999;'>정보가 없습니다.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- 페이지네이션 -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    // 검색 파라미터 유지
                    $search_params = '&tab=' . urlencode($current_tab);
                    if ($search_keyword) {
                        $search_params .= '&search_keyword=' . urlencode($search_keyword);
                    }

                    // 이전 페이지
                    if ($page > 1) {
                        echo "<a href='products.php?page=" . ($page - 1) . $search_params . "'>← 이전</a>";
                    }

                    // 페이지 번호
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo "<a href='products.php?page=1" . $search_params . "'>1</a>";
                        if ($start_page > 2)
                            echo "<span>...</span>";
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo "<span class='current'>" . $i . "</span>";
                        } else {
                            echo "<a href='products.php?page=" . $i . $search_params . "'>" . $i . "</a>";
                        }
                    }

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1)
                            echo "<span>...</span>";
                        echo "<a href='products.php?page=" . $total_pages . $search_params . "'>" . $total_pages . "</a>";
                    }

                    // 다음 페이지
                    if ($page < $total_pages) {
                        echo "<a href='products.php?page=" . ($page + 1) . $search_params . "'>다음 →</a>";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        $(function () {
            // 검색 자동완성 설정
            var currentTab = '<?php echo $current_tab; ?>';

            $('#search_keyword').autocomplete('products.php?action=autocomplete&tab=' + currentTab, {
                minChars: 1,
                width: 300,
                matchContains: true,
                autoFill: false,
                selectFirst: false
            });
        });
    </script>
</body>

</html>
<?php mysql_close($connect); ?>