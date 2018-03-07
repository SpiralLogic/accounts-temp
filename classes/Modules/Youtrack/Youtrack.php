<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 14/05/12
   * Time: 4:28 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace Modules\Youtrack;

  use ADV\App\User;
  use ADV\Core\JS;
  use ADV\Core\Module;

  /** **/
  class Youtrack extends Module\Base
  {
    public function init() {
      $this->_login();
    }
    public function _login() {
      $host = 'advanced.advancedgroup.com.au/modules/youtrack';
      if (strpos($_SERVER['HTTP_HOST'], 'dev') !== false) {
        $host = 'dev.' . $host;
      } elseif (strpos($_SERVER['HTTP_HOST'], 'backup') !== false) {
        $host = 'backup.' . $host;
      }
      $js
        = <<<JS
$.get('http://$host');
JS;
      JS::_onload($js);
    }
    public function youtrack() {
      $ch = curl_init('http://advanced.advancedgroup.com.au:8090/rest/user/login');
      curl_setopt($ch, CURLOPT_POST, 2);
      $user = User::_i()->username;
      $key  = User::_i()->getHash();
      curl_setopt($ch, CURLOPT_POSTFIELDS, "login=$user&password=$key");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HEADER, 1);
      preg_match_all('/^Set-Cookie: (.*?);/m', curl_exec($ch), $m);
      foreach ($m[1] as $n) {
        $cookie = explode('=', $n);
        setcookie($cookie[0], $cookie[1], time() + mktime(12), '/', '.advanced.advancedgroup.com.au');
      }
    }
  }
