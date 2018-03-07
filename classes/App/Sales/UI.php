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
  use ADV\App\Forms;
  use ADV\App\Item\Item;
  use ADV\Core\Cell;

  /** **/
  class Sales_UI
  {
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     *
     * @return string
     */
    public static function  persons($name, $selected_id = null, $spec_opt = false) {
      $sql = "SELECT salesman_code, salesman_name, inactive FROM salesman";
      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'salesman_code',
        'salesman_name',
        array(
             'order'       => array('salesman_name'),
             'spec_option' => $spec_opt,
             'spec_id'     => ALL_NUMERIC
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     */
    public static function  persons_cells($label, $name, $selected_id = null, $spec_opt = false) {
      if ($label != null) {
        echo "<td class='label' > <label for=\"$name\"> $label</label></td>";
      }
      echo "<td>\n";
      echo Sales_UI::persons($name, $selected_id, $spec_opt);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     */
    public static function  persons_row($label, $name, $selected_id = null, $spec_opt = false) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::persons_cells(null, $name, $selected_id, $spec_opt);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    public static function  areas($name, $selected_id = null) {
      $sql = "SELECT area_code, description, inactive FROM areas";
      return Forms::selectBox($name, $selected_id, $sql, 'area_code', 'description', []);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function  areas_cells($label, $name, $selected_id = null) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_UI::areas($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function  areas_row($label, $name, $selected_id = null) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::areas_cells(null, $name, $selected_id);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     *
     * @return string
     */
    public static function  groups($name, $selected_id = null, $special_option = false) {
      $sql = "SELECT id, description, inactive FROM groups";
      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'id',
        'description',
        array(
             'spec_option' => $special_option === true ? ' ' : $special_option,
             'order'       => 'description',
             'spec_id'     => 0,
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     */
    public static function  groups_cells($label, $name, $selected_id = null, $special_option = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_UI::groups($name, $selected_id, $special_option);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     */
    public static function  groups_row($label, $name, $selected_id = null, $special_option = false) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::groups_cells(null, $name, $selected_id, $special_option);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    public static function  shippers($name, $selected_id = null) {
      $sql = "SELECT shipper_id, shipper_name, inactive FROM shippers";
      return Forms::selectBox($name, $selected_id, $sql, 'shipper_id', 'shipper_name', array('order' => array('shipper_name')));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function  shippers_cells($label, $name, $selected_id = null) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_UI::shippers($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function  shippers_row($label, $name, $selected_id = null) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::shippers_cells(null, $name, $selected_id);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     */
    public static function  policy_cells($label, $name, $selected = null) {
      if ($label != null) {
        Cell::label($label);
      }
      echo "<td>\n";
      echo Forms::arraySelect(
        $name,
        $selected,
        array(
             ''    => _("Automatically put balance on back order"),
             'CAN' => _("Cancel any quantites not delivered")
        )
      );
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     */
    public static function  policy_row($label, $name, $selected = null) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::policy_cells(null, $name, $selected);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     *
     * @return string
     */
    public static function templates($name, $selected_id = null, $special_option = false) {
      $sql
        = "SELECT sorder.order_no,	Sum(line.unit_price*line.quantity*(1-line.discount_percent)) AS OrderValue
                FROM sales_orders as sorder, sales_order_details as line
                WHERE sorder.order_no = line.order_no AND sorder.type = 1 GROUP BY line.order_no";
      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'order_no',
        'OrderValue',
        array(
             'format'      => 'Forms::templateItemsFormat',
             'spec_option' => $special_option === true ? ' ' : $special_option,
             'order'       => 'order_no',
             'spec_id'     => 0,
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     */
    public static function templates_cells($label, $name, $selected_id = null, $special_option = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_UI::templates($name, $selected_id, $special_option);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     */
    public static function templates_row($label, $name, $selected_id = null, $special_option = false) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::templates_cells(null, $name, $selected_id, $special_option);
      echo "</tr>\n";
    }
    /**
     *  Select item via foreign code.
     *
     * @param        $name
     * @param null   $selected_id
     * @param bool   $all_option
     * @param bool   $submit_on_change
     * @param string $type
     * @param array  $opts             'description' => false,<br>
    'disabled' => false,<br>
    'editable' => true,<br>
    'selected' => '',<br>
    'label' => false,<br>
    'cells' => false,<br>
    'inactive' => false,<br>
    'purchase' => false,<br>
    'sale' => false,<br>
    'js' => '',<br>
    'selectjs' => '',<br>
    'submitonselect' => '',<br>
    'sales_type' => 1,<br>
    'no_sale' => false,<br>
    'select' => false,<br>
    'type' => 'local',<br>
    'kits'=>true,<br>
    'where' => '',<br>
    'size'=>'20px'<br>'
     * @param bool   $legacy
     *
     * @return string|void
     */
    public static function items($name, $selected_id = null, $all_option = false, $submit_on_change = false, $type = '', $opts = [], $legacy = false) {
      // all sales codes
      if (!$legacy) {
        Item::addSearchBox(
          $name,
          array_merge(
            array(
                 'selected' => $selected_id,
                 'type'     => $type,
                 'cells'    => true,
                 'sale'     => true
            ),
            $opts
          )
        );
        return null;
      }
      $where = ($type == 'local') ? " AND !i.is_foreign" : ' ';
      if ($type == 'kits') {
        $where .= " AND !i.is_foreign AND i.item_code!=i.stock_id ";
      }
      $sql
        = "SELECT i.item_code, i.description, c.description, count(*)>1 as kit,
                     i.inactive, if(count(*)>1, '0', s.editable) as editable, s.long_description
                    FROM stock_master s, item_codes i LEFT JOIN stock_category c ON i.category_id=c.category_id
                    WHERE i.stock_id=s.stock_id $where AND !i.inactive AND !s.inactive AND !s.no_sale GROUP BY i.item_code";
      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'i.item_code',
        'c.description',
        array_merge(
          array(
               'format'        => 'Forms::stockItemsFormat',
               'spec_option'   => $all_option === true ? _("All Items") : $all_option,
               'spec_id'       => ALL_TEXT,
               'search_box'    => true,
               'search'        => array(
                 "i.item_code",
                 "c.description",
                 "i.description"
               ),
               'search_submit' => DB_Company::_get_pref('no_item_list') != 0,
               'size'          => 15,
               'select_submit' => $submit_on_change,
               'category'      => 2,
               'order'         => array(
                 'c.description',
                 'i.item_code'
               ),
               'editable'      => 30,
               'max'           => 50
          ),
          $opts
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param      $opts
     */
    public static function items_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo Sales_UI::items(
        $name,
        $selected_id,
        $all_option,
        $submit_on_change,
        '',
        array_merge(
          array(
               'cells'       => true,
               'class'       => 'med ',
               'description' => ''
          ),
          $opts
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
     * @return string|void
     */
    public static function kits($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
      return Sales_UI::items(
        $name,
        $selected_id,
        $all_option,
        $submit_on_change,
        'kits',
        array(
             'cells' => false,
             'sale'  => false,
             'kits'  => false
        ),
        true
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $legacy
     */
    public static function local_items_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $legacy = true) {
      echo "<tr>";
      if ($label != null) {
        echo "<td class='label'>$label</td>\n<td>";
      }
      echo Sales_UI::items($name, $selected_id, $all_option, $submit_on_change, 'local', array('cells' => false), $legacy);
      echo "</td></tr>";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    public static function payment_cells($label, $name, $selected_id = null, $submit_on_change = false) {
      if ($label != null) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo  Forms::yesnoList($name, $selected_id, _('Cash'), _('Delayed'), $submit_on_change);
      echo "</td>\n";
    }
  }
