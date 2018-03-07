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

  use ADV\Core\Config;

  /** **/
  class Languages
  {
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    public static function select($name, $selected_id = null) {
      $items = [];
      $langs = Config::_get('languages.installed');
      foreach ($langs as $language) {
        $items[$language['code']] = $language['name'];
      }
      return Forms::arraySelect($name, $selected_id, $items);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function cells($label, $name, $selected_id = null) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Languages::select($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function row($label, $name, $selected_id = null) {
      echo "<tr><td class='label'>$label</td>";
      Languages::cells(null, $name, $selected_id);
      echo "</tr>\n";
    }
  }
