<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use ADV\Core\Input\Input;

  /** **/
  class Display
  {
    /**
     * @static
     *
     * @param      $string
     * @param bool $clean
     *
     * @internal param $label
     * @return array|mixed|string
     */
    public static function access_string($string, $clean = false) {
      static $used = [];
      $access = '';
      $string = preg_replace_callback(
        '/&([a-zA-Z0-9])/',
        function ($match) use (&$access, $clean, &$used) {
          if ($clean || in_array($match[1], $used)) {
            return $match[1];
          }
          $access = " accesskey='" . strtoupper($match[1]) . "'";
          $used[] = $match[1];
          return '<span class="u">' . $match[1] . '</span>';
        },
        $string
      );
      return $clean ? $string : array($string, $access);
    }
    /**
     * @static
     *
     * @param $msg
     */
    public static function heading($msg) {
      echo "<div class='center'><span class='headingtext'>$msg</span></div>\n";
    }
    /**
     * @static
     *
     * @param        $forward_to
     * @param string $params
     */
    public static function meta_forward($forward_to, $params = "") {
      echo "<meta http-equiv='Refresh' content='0; url=$forward_to?$params'>\n";
      echo "<div class='center'><br>" . _("You should automatically be forwarded.");
      echo " " . _("If this does not happen") . " <a href='$forward_to?$params'>" . _("click here") . "</a> " . _("to continue") . ".<br><br></div>\n";
      if ($params != '') {
        $params = '?' . $params;
      }
      \ADV\Core\Ajax::_redirect($forward_to . $params);
      exit;
    }
    /**
     * @static
     *
     * @param        $msg
     * @param int    $br
     * @param int    $br2
     * @param string $extra
     */
    public static function note($msg, $br = 0, $br2 = 0, $extra = "") {
      echo str_repeat("<br>", $br);
      if ($extra) {
        $msg = "<span $extra>$msg</span>";
      }
      echo "<div class='center'>$msg</div>\n";
      str_repeat("<br>", $br2);
    }
    /**
     * @param      $label
     * @param      $url
     * @param bool $icon
     *
     * @return string
     */
    public static function link_button($label, $url, $icon = false) {
      if (User::_graphic_links() && $icon) {
        $label = Forms::setIcon($icon, $label);
      }
      $href = '/' . ltrim($url, '/');
      $href = (Input::_request('frame')) ? "javascript:window.parent.location='$href'" : $href;
      return '<a href="' . e($href) . '" class="button">' . $label . "</a>";
    }
    /**
     * @static
     *
     * @param        $target
     * @param        $label
     * @param string $link_params
     * @param bool   $center
     * @param string $params
     */
    public static function link_params($target, $label, $link_params = '', $center = true, $params = '') {
      $pars = Display::access_string($label);
      if (!$target) {
        $target = $_SERVER['DOCUMENT_URI'];
      }
      $link = "<a  href='$target?$link_params' $params $pars[1] >$pars[0]</a>\n";
      if ($center) {
        $link = "<br><div class='center'>$link</div>";
      }
      echo $link;
    }
    /**
     * @static
     *
     * @param      $title
     * @param      $url
     * @param null $id
     */
    public static function submenu_option($title, $url, $id = null) {
      $url    = ROOT_URL . ltrim($url, '/');
      $pars   = Display::access_string($title);
      $button = "<a href='$url' class='button  button-large' id='$id' $pars[1]>$pars[0]</a>";
      echo "<br><div class='center'>$button</div>";
    }
    /**
     * @static
     *
     * @param          $title
     * @param          $type
     * @param          $number
     * @param null     $id
     * @param int|null $email
     * @param int      $extra
     *
     * @return string
     */
    public static function submenu_print($title, $type, $number, $id = null, $email = 0, $extra = 0) {
      return Reporting::print_doc_link($number, $title, true, $type, false, 'button printlink', $id, $email, $extra);
    }
    /**
     * @static
     *
     * @param        $label
     * @param string $url
     * @param string $class
     * @param string $id
     * @param null   $icon
     *
     * @return string
     */
    public static function viewer_link($label, $url = '', $class = '', $id = '', $icon = null) {
      if ($url) {
        $class .= " openWindow";
      }
      if ($class) {
        $class = " class='$class'";
      }
      if ($id) {
        $class = " id='$id'";
      }
      if ($url) {
        $pars = Display::access_string($label);
        if (User::_graphic_links() && $icon) {
          $pars[0] = Forms::setIcon($icon, $pars[0]);
        }
        $preview_str = "<a target='_blank' $class $id href='/" . e(ltrim($url, '/')) . "' $pars[1]>$pars[0]</a>";
      } else {
        $preview_str = $label;
      }
      return $preview_str;
    }
  }
