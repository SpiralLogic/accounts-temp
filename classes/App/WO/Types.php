<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class WO_Types {
    //------------------------------------------------------------------------------------------------
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    public static function select($name, $selected_id = null) {
      return Forms::arraySelect(
        $name,
        $selected_id,
        WO::$types,
        array(
             'select_submit' => true,
             'async'         => true
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function row($label, $name, $selected_id = null) {
      echo "<tr><td class='label'>$label</td><td>\n";
      echo static::select($name, $selected_id);
      echo "</td></tr>\n";
    }
  }
