<?php
/**
 * Session Management
 * 세션 관리 유틸리티
 */

session_start();

class Session {
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    public static function delete($key) {
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        session_destroy();
    }

    public static function is_logged_in() {
        return self::has('member_id') && self::has('member_sid');
    }

    public static function get_user_id() {
        return self::get('member_id');
    }

    public static function get_user_level() {
        return self::get('member_level');
    }

    public static function login($user_id, $user_level, $user_name = '') {
        self::set('member_id', $user_id);
        self::set('member_level', $user_level);
        self::set('user_name', $user_name);
        self::set('login_time', time());
    }

    public static function logout() {
        self::destroy();
    }
}
?>
