<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  use ADV\Core\DB\DB;

  /** **/
  class Auth
  {
    /** @var */
    protected $username;
    /** @var */
    protected $id;


    /**
     * @param $username
     */
    public function __construct($username) {
      $this->username = $username;
    }

    /**
     * @throws \RuntimeException
     * @return string
     */
    public static function generateIV() {
      if (!extension_loaded('mcrypt')) {
        throw new \RuntimeException('Mcrypt extension must be installed');
      }
      return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB), MCRYPT_DEV_URANDOM));
    }

    /**
     * @param $password
     * @param $iv
     *
     * @return string
     */
    public static function fromIV($password, $iv) {
      return \AesCtr::decrypt(base64_decode($password), $iv, 256);
    }

    /**
     * @param     $id
     * @param     $password
     * @param int $change_password
     *
     * @return string
     */
    public function updatePassword($id, $password, $change_password = 0) {
      $change_password = $change_password == true ? 1 : 0;
      $hash            = $this->hashPassword($password);
      $result = DB::_update('users')->value('password', $hash)->value('user_id', $this->username)->value('hash', $this->makeHash($password, $id))
                  ->value('change_password', $change_password)->where('id=', $id)->exec();
      session_regenerate_id();
      return $result ? $hash : false;
    }

    /**
     * @internal param $password
     *
     * @param $password
     *
     * @return string
     */
    public function hashPassword($password) {
      $password = password_hash($password, PASSWORD_DEFAULT);
      return $password;
    }

    /**
     * @param $password
     *
     * @return string
     */
    public function oldHashPassword($password) {
      $password = crypt($password, '$6$rounds=5000$' . Config::_get('auth_salt') . '$');
      return $password;
    }

    /**
     * @param $username
     * @param $password
     *
     * @internal param $user_id
     * @internal param $password
     * @return bool|mixed
     */
    public function checkUserPassword($username, $password) {
           $result         = DB::_select()->from('users')->where('user_id=', $username)->andWhere('inactive =', 0)->fetch()->one();
      if (!password_get_info($result['password'])['algo'] && $result['password'] == $this->oldHashPassword($password)) {
        $result['password'] = $this->updatePassword($result['id'], $password);
      }

      if (!password_verify($password, $result['password'])) {
        return false;
      }
      if (!isset($result['hash']) || !$result['hash']) {
        $this->updatePassword($result['id'], $password);
        $result['hash'] = $this->makeHash($password, $result['id']);
      }
      unset($result['password']);
      DB::_insert('user_login_log')->values(['user' => $username, 'IP' => Auth::get_ip(), 'success' => (bool) $result])->exec();
      return $result;
    }

    /**
     * @return bool
     */
    public function isBruteForce() {
      $query = DB::_query('select COUNT(IP) FROM user_login_log WHERE success=0 AND timestamp>NOW() - INTERVAL 1 HOUR AND IP=' . DB::_escape(Auth::get_ip()));
      return (DB::_fetch($query)[0] > Config::_get('max_login_attempts', 50));
    }

    /**
     * @static
     *
     * @param $password
     * @param $user_id
     *
     * @return string
     */
    public function makeHash($password, $user_id) {
      return crypt($password, $user_id);
    }

    /**
     * @static
     *
     * @param      $password
     * @param bool $username
     *
     * @return array
     */
    public static function checkPasswordStrength($password, $username = false) {
      $returns = [
        'strength' => 0,
        'error'    => 0,
        'text'     => ''
      ];
      $length  = strlen($password);
      if ($length < 8) {
        $returns['error'] = 1;
        $returns['text']  = 'The password is not long enough';
      } else {
        //check for a couple of bad passwords:
        if ($username && strtolower($password) == strtolower($username)) {
          $returns['error'] = 4;
          $returns['text']  = 'Password cannot be the same as your Username';
        } elseif (strtolower($password) == 'password') {
          $returns['error'] = 3;
          $returns['text']  = 'Password is too common';
        } else {
          preg_match_all('/(.)\\1{2}/', $password, $matches);
          $consecutives = count($matches[0]);
          $returns['consecutives'] = $consecutives;
          preg_match_all("/\d/i", $password, $matches);
          $numbers = count($matches[0]);
          preg_match_all("/[A-Z]/", $password, $matches);
          $uppers = count($matches[0]);
          preg_match_all("/[^A-z0-9]/", $password, $matches);
          $others = count($matches[0]);
          //see if there are 3 consecutive chars (or more) and fail!
          if ($consecutives > 0) {
            $returns['error'] = 2;
            $returns['text']  = 'Too many consecutive characters';
          } elseif ($others > 1 || ($uppers > 1 && $numbers > 1)) {
            //bulletproof
            $returns['strength'] = 5;
            $returns['text']     = 'Virtually Bulletproof';
          } elseif (($uppers > 0 && $numbers > 0) || $length > 14) {
            //very strong
            $returns['strength'] = 4;
            $returns['text']     = 'Very Strong';
          } else {
            if ($uppers > 0 || $numbers > 2 || $length > 9) {
              //strong
              $returns['strength'] = 3;
              $returns['text']     = 'Strong';
            } else {
              if ($numbers > 1) {
                //fair
                $returns['strength'] = 2;
                $returns['text']     = 'Fair';
              } else {
                //weak
                $returns['strength'] = 1;
                $returns['text']     = 'Weak';
              }
            }
          }
        }
      }
      return $returns;
    }

    /**
     * @static
     * @return mixed
     */
    public static function get_ip() {
      /*
      This will find out if user is from behind proxy server.
      In that case, the script would count them all as 1 user.
      This public static function tryes to get real IP address.
      */
      if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
      } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
      } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
      } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
      } else {
        $ip = $_SERVER['REMOTE_ADDR'];
      }
      return $ip;
    }
  }
