<?php
namespace Session;

class Session {
    
    // Memastikan session telah dimulai saat class ini dipanggil
    private static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    // =========================
    // SET SESSION
    // =========================
    public static function setSession($id, $guru, $user, $roleUser, $must, $wali) {
        self::init();
        $_SESSION['id_user'] = (int)$id;
        $_SESSION['id_guru'] = (string)$guru;
        $_SESSION['username'] = (string)$user;
        $_SESSION['role'] = (string)$roleUser;
        $_SESSION['must_change_password'] = (bool)$must;
        $_SESSION['wali_kelas'] = (bool)$wali;
    }

    // =========================
    // CLEAR SESSION
    // =========================
    public static function clear() {
        self::init();
        $_SESSION['id_user'] = 0;
        $_SESSION['id_guru'] = '';
        $_SESSION['username'] = null;
        $_SESSION['role'] = null;
        $_SESSION['must_change_password'] = false;
        $_SESSION['wali_kelas'] = false;
        
        // Opsional: jika ingin menghapus seluruh session dari browser
        // session_destroy();
    }

    // =========================
    // GETTER
    // =========================
    public static function getIdUser() {
        self::init();
        return isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 0;
    }

    public static function getIdGuru() {
        self::init();
        return isset($_SESSION['id_guru']) ? $_SESSION['id_guru'] : '';
    }

    public static function getUsername() {
        self::init();
        return isset($_SESSION['username']) ? $_SESSION['username'] : null;
    }

    public static function getRole() {
        self::init();
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }

    public static function isMustChangePassword() {
        self::init();
        return isset($_SESSION['must_change_password']) ? $_SESSION['must_change_password'] : false;
    }

    // =========================
    // CEK WALI KELAS
    // =========================
    public static function isWaliKelas() {
        self::init();
        return isset($_SESSION['wali_kelas']) ? $_SESSION['wali_kelas'] : false;
    }
}
