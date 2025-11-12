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

// AJAX 삭제 요청 처리
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    ob_start();
    header('Content-Type: application/json; charset=utf-8');

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $success = false;
    $message = '삭제에 실패했습니다.';

    if ($id > 0) {
        // 외래 키 제약 비활성화
        mysql_query("SET FOREIGN_KEY_CHECKS = 0");

        $delete_result = mysql_query("DELETE FROM step11_member WHERE s11_meid = $id");
        if ($delete_result !== false && mysql_affected_rows() > 0) {
            $success = true;
            $message = '고객 정보가 정상적으로 삭제되었습니다.';
        }

        // 외래 키 제약 다시 활성화
        mysql_query("SET FOREIGN_KEY_CHECKS = 1");
    }

    ob_end_clean();
    echo json_encode(array('success' => $success, 'message' => $message));
    exit;
}

$user_name = $_SESSION['member_id'];
$success_message = '';
$current_page = 'members';

// 삭제 완료 체크
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $success_message = '고객 정보가 정상적으로 삭제되었습니다.';
}

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 검색 조건 (GET으로 받아서 검색 유지)
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : (isset($_POST['search_type']) ? $_POST['search_type'] : '');
$search_keyword = isset($_GET['search_keyword']) ? trim($_GET['search_keyword']) : (isset($_POST['search_keyword']) ? trim($_POST['search_keyword']) : '');

// WHERE 조건 생성
$where = "1=1";
if (!empty($search_keyword)) {
    $search_keyword_esc = mysql_real_escape_string($search_keyword);
    if (empty($search_type) || $search_type === '') {
        // 전체 검색: 업체명 또는 전화번호에 포함되면 검색
        $where .= " AND (s11_com_name LIKE '%" . $search_keyword_esc . "%' OR CONCAT(s11_phone1, '-', s11_phone2, '-', s11_phone3) LIKE '%" . $search_keyword_esc . "%' OR s11_phone1 LIKE '%" . $search_keyword_esc . "%' OR s11_phone2 LIKE '%" . $search_keyword_esc . "%' OR s11_phone3 LIKE '%" . $search_keyword_esc . "%')";
    } elseif ($search_type == 'company') {
        $where .= " AND s11_com_name LIKE '%" . $search_keyword_esc . "%'";
    } elseif ($search_type == 'number') {
        // 전화번호 검색: 세 부분 중 어느 하나에도 포함되면 검색
        $where .= " AND (CONCAT(s11_phone1, '-', s11_phone2, '-', s11_phone3) LIKE '%" . $search_keyword_esc . "%' OR s11_phone1 LIKE '%" . $search_keyword_esc . "%' OR s11_phone2 LIKE '%" . $search_keyword_esc . "%' OR s11_phone3 LIKE '%" . $search_keyword_esc . "%')";
    }
}

// 전체 개수 조회
$count_result = mysql_query("SELECT COUNT(*) as cnt FROM step11_member WHERE $where");
$count_row = mysql_fetch_assoc($count_result);
$total_count = $count_row['cnt'];
$total_pages = ceil($total_count / $limit);

// 데이터 조회 (DESC 순서)
$result = mysql_query("
    SELECT s11_meid, s11_sec, s11_com_name, s11_phone1, s11_phone2, s11_phone3
    FROM step11_member
    WHERE $where
    ORDER BY s11_meid DESC
    LIMIT $offset, $limit
");
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>고객 관리 - AS 시스템</title>
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

        .alert {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .search-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .search-box select,
        .search-box input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
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

        .search-box a {
            padding: 10px 20px;
            background: #95a5a6;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }

        .search-box a:hover {
            background: #7f8c8d;
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

        .action-link {
            padding: 5px 10px;
            margin: 0 3px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
            font-size: 12px;
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

        /* Column 너비 정의 */
        table col.c-no {
            width: 10%;
        }

        table col.c-sec {
            width: 15%;
        }

        table col.c-company {
            width: 35%;
        }

        table col.c-phone {
            width: 25%;
        }

        table col.c-edit {
            width: 7.5%;
        }

        table col.c-del {
            width: 7.5%;
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
        <a href="members.php" class="nav-item <?php echo $current_page === 'members' ? 'active' : ''; ?>">고객 관리</a>
        <a href="products.php" class="nav-item">제품 관리</a>
        <a href="statistics.php" class="nav-item">통계/분석</a>
    </div>


    <div class="container">
        <div class="content">
            <h2>👥 고객 관리</h2>
            <p>고객 정보를 조회 및 관리합니다.</p>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- 검색 박스 -->
            <form method="GET" class="search-box">
                <select name="search_type">
                    <option value="">전체</option>
                    <option value="company" <?php echo $search_type == 'company' ? 'selected' : ''; ?>>업체명</option>
                    <option value="number" <?php echo $search_type == 'number' ? 'selected' : ''; ?>>번호</option>
                </select>
                <input type="text" name="search_keyword" placeholder="검색어를 입력하세요"
                    value="<?php echo htmlspecialchars($search_keyword); ?>">
                <button type="submit">검색</button>
                <a href="members.php">초기화</a>
            </form>

            <!-- 액션 버튼 -->
            <div class="action-buttons">
                <button class="register-btn" onclick="location.href='member_add.php'">+ 새 고객 등록</button>
            </div>

            <!-- 정보 텍스트 -->
            <div class="info-text">
                총 <?php echo $total_count; ?>개의 고객 정보 (페이지: <?php echo $page; ?>/<?php echo max(1, $total_pages); ?>)
            </div>

            <!-- 테이블 -->
            <table>
                <colgroup>
                    <col class="c-no">
                    <col class="c-sec">
                    <col class="c-company">
                    <col class="c-phone">
                    <col class="c-edit">
                    <col class="c-del">
                </colgroup>
                <thead>
                    <tr>
                        <th>번호</th>
                        <th>분류</th>
                        <th>업체명</th>
                        <th>전화</th>
                        <th colspan="2">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && mysql_num_rows($result) > 0) {
                        $row_number = $total_count - $offset;
                        while ($row = mysql_fetch_assoc($result)) {
                            $phone = $row['s11_phone1'] . '-' . $row['s11_phone2'] . '-' . $row['s11_phone3'];
                            echo "<tr>";
                            echo "<td>" . $row_number . "</td>";
                            echo "<td>" . htmlspecialchars($row['s11_sec']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['s11_com_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($phone) . "</td>";
                            echo "<td>";
                            echo "<a href='member_edit.php?id=" . $row['s11_meid'] . "' class='action-link edit-link'>수정</a>";
                            echo "</td>";
                            echo "<td>";
                            echo "<a href='#' class='action-link delete-link' data-id='" . $row['s11_meid'] . "' onclick='return deleteItem(this);'>삭제</a>";
                            echo "</td>";
                            echo "</tr>";
                            $row_number--;
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; color: #999;'>고객 정보가 없습니다.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- 페이지네이션 -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    // 검색 파라미터 유지
                    $search_params = '';
                    if ($search_keyword) {
                        $search_params = '&search_type=' . urlencode($search_type) . '&search_keyword=' . urlencode($search_keyword);
                    }

                    // 이전 페이지
                    if ($page > 1) {
                        echo "<a href='members.php?page=" . ($page - 1) . $search_params . "'>← 이전</a>";
                    }

                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo "<a href='members.php?page=1" . $search_params . "'>1</a>";
                        if ($start_page > 2)
                            echo "<span>...</span>";
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo "<span class='current'>" . $i . "</span>";
                        } else {
                            echo "<a href='members.php?page=" . $i . $search_params . "'>" . $i . "</a>";
                        }
                    }

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1)
                            echo "<span>...</span>";
                        echo "<a href='members.php?page=" . $total_pages . $search_params . "'>" . $total_pages . "</a>";
                    }

                    if ($page < $total_pages) {
                        echo "<a href='members.php?page=" . ($page + 1) . $search_params . "'>다음 →</a>";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading.show {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 30px 50px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .loading-content h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .loading-content p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .message {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
    <script>
        // 로딩 표시
        function showLoading() {
            var $loading = $('<div class="loading show">' +
                '<div class="loading-content">' +
                '<div class="spinner"></div>' +
                '<h3>처리 중...</h3>' +
                '<p>삭제 중입니다.</p>' +
                '</div>' +
                '</div>');
            $('body').append($loading);
            return $loading;
        }

        // 로딩 숨기기
        function hideLoading($loading) {
            if ($loading) {
                $loading.remove();
            }
        }

        // AJAX 삭제 함수
        function deleteItem(el) {
            if (!confirm('정말 삭제하시겠습니까?')) {
                return false;
            }

            var id = $(el).data('id');
            var $el = $(el);

            // 로딩 표시
            var $loading = showLoading();

            $.ajax({
                url: 'members.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'delete',
                    id: id
                },
                success: function (response) {
                    hideLoading($loading);

                    if (response.success) {
                        // 행 제거 애니메이션
                        $el.closest('tr').fadeOut(300, function () {
                            $(this).remove();

                            // 성공 메시지 표시
                            showMessage(response.message, 'success');

                            // 1초 후 페이지 새로고침
                            setTimeout(function () {
                                location.reload();
                            }, 1000);
                        });
                    } else {
                        showMessage(response.message, 'error');
                    }
                },
                error: function () {
                    hideLoading($loading);
                    showMessage('삭제 중 오류가 발생했습니다.', 'error');
                }
            });

            return false;
        }

        // 메시지 표시 함수
        function showMessage(message, type) {
            var messageClass = type === 'error' ? 'error' : '';
            var $message = $('<div class="message ' + messageClass + '"></div>')
                .text((type === 'error' ? '✗ ' : '✓ ') + message)
                .prependTo('.content')
                .fadeIn();

            setTimeout(function () {
                $message.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3000);
        }
    </script>
</body>

</html>
<?php mysql_close($connect); ?>