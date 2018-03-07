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
  use ADV\App\Item\Item;
  use ADV\App\Forms;

  // STOCK ITEMS
  /**
   *
   */
  class Item_UI
  {
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function manufactured($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
      return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => array("mb_flag= '" . STOCK_MANUFACTURE . "'")));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     */
    public static function manufactured_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo Item_UI::manufactured($name, $selected_id, $all_option, $submit_on_change, array('cells' => true));
      echo "\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     */
    public static function manufactured_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
      echo "<tr><td class='label'>$label</td>";
      Item_UI::manufactured_cells(null, $name, $selected_id, $all_option, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param      $parent_stock_id
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $editkey
     *
     * @return string
     */
    public static function component($name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
      return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => " stock_id != '$parent_stock_id' "));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param      $parent_stock_id
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $editkey
     */
    public static function component_cells($label, $name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo Item::select(
        $name,
        $selected_id,
        $all_option,
        $submit_on_change,
        array(
             'where' => "stock_id != '$parent_stock_id'",
             'cells' => true
        )
      );
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function costable($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
      return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => "mb_flag!='" . STOCK_SERVICE . "'"));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     */
    public static function costable_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo Item::select(
        $name,
        $selected_id,
        $all_option,
        $submit_on_change,
        array(
             'where'       => "mb_flag!='" . STOCK_SERVICE . "'",
             'cells'       => true,
             'description' => ''
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $enabled
     */
    public static function type_row($label, $name, $selected_id = null, $enabled = true) {
      echo "<tr>";
      if ($label != null) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo Forms::arraySelect(
        $name,
        $selected_id,
        Item::$types,
        array(
             'select_submit' => true,
             'disabled'      => !$enabled
        )
      );
      echo "</td></tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $enabled
     *
     * @return string
     */
    public static function type($name, $selected_id = null, $enabled = true) {
      return Forms::arraySelect(
        $name,
        $selected_id,
        Item::$types,
        array(
             'select_submit' => true,
             'disabled'      => !$enabled
        )
      );
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     * @param bool   $raw
     *
     * @return null|string
     */
    public static function viewTrans($type, $trans_no, $label = "", $icon = false, $class = '', $id = '', $raw = false) {
      $viewer = "inventory/view/";
      switch ($type) {
        case ST_INVADJUST:
          $viewer .= "adjustment.php";
          break;
        case ST_LOCTRANSFER:
          $viewer .= "transfer.php";
          break;
        default:
          return null;
      }
      $viewer .= "?trans_no=$trans_no";
      if ($raw) {
        return $viewer;
      }
      if ($label == "") {
        $label = $trans_no;
      }
      return Display::viewer_link($label, $viewer, $class, $id, $icon);
    }
    /**
     * @static
     *
     * @param      $stock_id
     * @param null $description
     * @param bool $echo
     *
     * @return string
     */
    public static function status($stock_id, $description = null, $echo = true) {
      if ($description) {
        $preview_str = "<a class='openWindow' target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' >" . (User::_show_codes() ? $stock_id . " - " :
          "") . $description . "</a>";
      } else {
        $preview_str = "<a class='openWindow' target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' >$stock_id</a>";
      }
      if ($echo) {
        echo $preview_str;
      }
      return $preview_str;
    }
    /**
     * @static
     *
     * @param      $stock_id
     * @param null $description
     */
    public static function status_cell($stock_id, $description = null) {
      echo "<td>";
      Item_UI::status($stock_id, $description);
      echo "</td>";
    }
  }
