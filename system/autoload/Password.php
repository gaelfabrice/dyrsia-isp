<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpwifizones/)
 *  by https://t.me/ibnux
 **/

class Password
{

    public static function _crypt($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function _verify($user_input, $hashed_password)
    {
        if ($hashed_password === null || $hashed_password === '') {
            return false;
        }
        if (self::isModernHash($hashed_password)) {
            return password_verify($user_input, $hashed_password);
        }
        if (strlen($hashed_password) === 40 && ctype_xdigit($hashed_password)) {
            return hash_equals($hashed_password, sha1($user_input));
        }
        return hash_equals((string) $hashed_password, (string) $user_input);
    }

    public static function _uverify($user_input, $hashed_password)
    {
        return self::_verify($user_input, $hashed_password);
    }

    /** Customer passwords may be legacy plain text, SHA1, or bcrypt. */
    public static function verifyCustomer($user_input, $stored_password)
    {
        return self::_verify($user_input, $stored_password);
    }

    public static function needsRehash($stored_password)
    {
        if ($stored_password === null || $stored_password === '') {
            return false;
        }
        if (self::isModernHash($stored_password)) {
            return password_needs_rehash($stored_password, PASSWORD_BCRYPT);
        }
        return true;
    }

    public static function upgradeStoredHash($plainPassword, $stored_password)
    {
        if (!self::_verify($plainPassword, $stored_password)) {
            return null;
        }
        if (!self::needsRehash($stored_password)) {
            return null;
        }
        return self::_crypt($plainPassword);
    }

    private static function isModernHash($hash)
    {
        return is_string($hash) && (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$2b$'));
    }

    public static function _gen()
    {
        $pass = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz@#!123456789', 8)), 0, 8);
        return $pass;
    }

    /**
     * verify CHAP password
     * @param string $realPassword
     * @param string $CHAPassword
     * @param string $CHAPChallenge
     * @return bool
     */
    public static function chap_verify($realPassword, $CHAPassword, $CHAPChallenge){
        $CHAPassword = substr($CHAPassword, 2);
        $chapid = substr($CHAPassword, 0, 2);
        $result = hex2bin($chapid) . $realPassword . hex2bin(substr($CHAPChallenge, 2));
        $response = $chapid . md5($result);
        return ($response != $CHAPassword);
    }
}
