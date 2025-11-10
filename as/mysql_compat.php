<?php
/**
 * MySQL Compatibility Layer for PHP 7.4+
 * Provides mysql_* function compatibility using mysqli
 */

// Define old MySQL constants for backward compatibility
if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', MYSQLI_ASSOC);
}
if (!defined('MYSQL_NUM')) {
    define('MYSQL_NUM', MYSQLI_NUM);
}
if (!defined('MYSQL_BOTH')) {
    define('MYSQL_BOTH', MYSQLI_BOTH);
}

// Global database connection
$GLOBALS['___mysql_link'] = null;
$GLOBALS['___mysql_result'] = null;

function mysql_connect($server, $username, $password) {
    $link = mysqli_connect($server, $username, $password);
    if (!$link) {
        trigger_error('mysql_connect(): ' . mysqli_connect_error(), E_USER_WARNING);
        return false;
    }
    // UTF-8 문자 인코딩 및 Collation 설정
    mysqli_set_charset($link, 'utf8mb4');
    // SET COLLATION_CONNECTION을 명시적으로 설정
    $charset_query = "SET collation_connection = 'utf8mb4_unicode_ci'";
    if (!mysqli_query($link, $charset_query)) {
        trigger_error('mysql_connect(): Failed to set collation - ' . mysqli_error($link), E_USER_WARNING);
        return false;
    }
    $GLOBALS['___mysql_link'] = $link;
    return $link;
}

function mysql_select_db($database_name, $link = null) {
    if ($link === null) {
        $link = $GLOBALS['___mysql_link'];
    }
    if (!$link) {
        return false;
    }
    $result = mysqli_select_db($link, $database_name);
    if (!$result) {
        trigger_error('mysql_select_db(): ' . mysqli_error($link), E_USER_WARNING);
    }
    return $result;
}

function mysql_query($query, $link = null) {
    if ($link === null) {
        $link = $GLOBALS['___mysql_link'];
    }
    if (!$link) {
        return false;
    }
    $result = mysqli_query($link, $query);
    if (!$result) {
        // SELECT 쿼리가 아닌 경우에도 true를 반환할 수 있음 (INSERT, UPDATE, DELETE)
        // mysqli_query는 SELECT/SHOW/EXPLAIN/DESCRIBE 등에서만 결과 객체를 반환
        // 다른 쿼리는 성공하면 true, 실패하면 false를 반환
        trigger_error('mysql_query(): ' . mysqli_error($link), E_USER_WARNING);
        return false;
    }
    // DELETE, INSERT, UPDATE 등은 true를 반환 (객체가 아님)
    if ($result === true) {
        return true;
    }
    // SELECT 등은 결과 객체를 저장
    $GLOBALS['___mysql_result'] = $result;
    return $result;
}

function mysql_fetch_array($result, $result_type = MYSQLI_BOTH) {
    if ($result === null) {
        $result = $GLOBALS['___mysql_result'];
    }
    if ($result === false || $result === null || !is_object($result)) {
        return null;
    }

    // Convert old MySQL constants to mysqli constants if needed
    // MYSQL_ASSOC, MYSQL_NUM, MYSQL_BOTH are already defined above
    // This ensures compatibility with old code using these constants
    $converted_type = $result_type;
    if (is_int($result_type)) {
        $converted_type = $result_type;
    } else {
        // If for some reason it's not an int, use MYSQLI_BOTH as default
        $converted_type = MYSQLI_BOTH;
    }

    return mysqli_fetch_array($result, $converted_type);
}

function mysql_fetch_assoc($result) {
    if ($result === null) {
        $result = $GLOBALS['___mysql_result'];
    }
    if ($result === false || $result === null || !is_object($result)) {
        return null;
    }
    return mysqli_fetch_assoc($result);
}

function mysql_fetch_row($result) {
    if ($result === null) {
        $result = $GLOBALS['___mysql_result'];
    }
    if ($result === false || $result === null || !is_object($result)) {
        return null;
    }
    return mysqli_fetch_row($result);
}

function mysql_num_rows($result) {
    if ($result === null) {
        $result = $GLOBALS['___mysql_result'];
    }
    if ($result === false || $result === null || !is_object($result)) {
        return 0;
    }
    return mysqli_num_rows($result);
}

function mysql_num_fields($result) {
    if ($result === null) {
        $result = $GLOBALS['___mysql_result'];
    }
    if ($result === false || $result === null || !is_object($result)) {
        return 0;
    }
    return mysqli_num_fields($result);
}

function mysql_affected_rows($link = null) {
    if ($link === null) {
        $link = $GLOBALS['___mysql_link'];
    }
    return mysqli_affected_rows($link);
}

function mysql_insert_id($link = null) {
    if ($link === null) {
        $link = $GLOBALS['___mysql_link'];
    }
    return mysqli_insert_id($link);
}

function mysql_error($link = null) {
    if ($link === null) {
        $link = $GLOBALS['___mysql_link'];
    }
    return mysqli_error($link);
}

function mysql_errno($link = null) {
    if ($link === null) {
        $link = $GLOBALS['___mysql_link'];
    }
    return mysqli_errno($link);
}

function mysql_close($link = null) {
    if ($link === null) {
        $link = $GLOBALS['___mysql_link'];
    }
    if ($link) {
        mysqli_close($link);
    }
    return true;
}

function mysql_real_escape_string($unescaped_string, $link = null) {
    if ($link === null) {
        $link = $GLOBALS['___mysql_link'];
    }
    if (!$link) {
        return addslashes($unescaped_string);
    }
    return mysqli_real_escape_string($link, $unescaped_string);
}

function mysql_fetch_object($result) {
    if ($result === null) {
        $result = $GLOBALS['___mysql_result'];
    }
    if ($result === false || $result === null || !is_object($result)) {
        return null;
    }
    return mysqli_fetch_object($result);
}

function mysql_result($result, $row = 0, $field = 0) {
    if ($result === null) {
        $result = $GLOBALS['___mysql_result'];
    }
    if ($result === false || $result === null || !is_object($result)) {
        return false;
    }

    // Fetch rows until we reach the desired row number
    $current_row = 0;
    while ($current_row < $row) {
        mysqli_fetch_array($result, MYSQLI_NUM);
        $current_row++;
    }

    $data = mysqli_fetch_array($result, MYSQLI_NUM);
    if ($data === null) {
        return false;
    }
    return isset($data[$field]) ? $data[$field] : false;
}
?>
