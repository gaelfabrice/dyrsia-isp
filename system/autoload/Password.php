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

    /**
     * Mot de passe en clair pour MikroTik / FreeRADIUS (Cleartext-Password).
     * Le champ password en base est hashé (bcrypt) ; pppoe_password conserve le secret réseau.
     */
    public static function networkCleartext($customer)
    {
        if (is_object($customer)) {
            $customer = method_exists($customer, 'as_array') ? $customer->as_array() : (array) $customer;
        }
        if (!is_array($customer)) {
            return '';
        }
        $network = trim((string) ($customer['pppoe_password'] ?? ''));
        if ($network !== '') {
            return $network;
        }
        $stored = (string) ($customer['password'] ?? '');
        if ($stored === '' || self::isModernHash($stored)) {
            return '';
        }
        if (strlen($stored) === 40 && ctype_xdigit($stored)) {
            return '';
        }

        return $stored;
    }

    /**
     * Enregistre password (hash portail) + pppoe_password (clair MikroTik/RADIUS).
     */
    public static function assignCustomerCredentials($customer, $plainPassword, $networkPassword = '')
    {
        $plain = trim((string) $plainPassword);
        $network = trim((string) $networkPassword);
        $customer->password = self::_crypt($plain);
        $customer->pppoe_password = $network !== '' ? $network : $plain;
    }

    /** True si la valeur ressemble à un hash bcrypt (pas un mot de passe saisi). */
    public static function isStoredHash($value)
    {
        return self::isModernHash((string) $value);
    }

    private static function isModernHash($hash)
    {
        if (!is_string($hash) || strlen($hash) < 4) {
            return false;
        }
        $prefix = substr($hash, 0, 4);
        return $prefix === '$2y$' || $prefix === '$2a$' || $prefix === '$2b$';
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
