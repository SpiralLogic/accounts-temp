<?php
  use ADV\App\Debtor\Debtor;
  use ADV\App\Dimensions;
  use ADV\App\Display;
  use ADV\App\Forms;
  use ADV\App\User;
  use ADV\App\WO\WO;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class GL_UI {
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $skip_bank_accounts
     * @param bool $cells
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $all
     *
     * @return string
     */
    public static function  all($name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false) {
      if ($skip_bank_accounts) {
        $sql
          = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
                FROM (chart_master chart,chart_types type) LEFT JOIN bank_accounts acc ON chart.account_code=acc.account_code
                    WHERE acc.account_code IS null
                AND chart.account_type=type.id";
      } else {
        $sql
          = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
                FROM chart_master chart,chart_types type
                WHERE chart.account_type=type.id";
      }
      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'chart.account_code',
        'chart.account_name',
        array(
             'cache'         => true,
             'format'        => 'Forms::accountFormat',
             'type'          => 2,
             'spec_option'   => $all_option === true ? _("Use Item Sales Accounts") : $all_option,
             'spec_id'       => '',
             'order'         => array(
               'type.id',
               'account_code'
             ),
             'search_box'    => $cells,
             'search_submit' => false,
             'size'          => 12,
             'max'           => 10,
             'cells'         => true,
             'select_submit' => $submit_on_change,
             'async'         => false,
             'category'      => 2,
             'show_inactive' => $all
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $skip_bank_accounts
     * @param bool $cells
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $all
     */
    public static function  all_cells(
      $label,
      $name,
      $selected_id = null,
      $skip_bank_accounts = false,
      $cells = false,
      $all_option = false,
      $submit_on_change = false,
      $all = false
    ) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_UI::all($name, $selected_id, $skip_bank_accounts, $cells, $all_option, $submit_on_change, $all);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $skip_bank_accounts
     * @param bool $cells
     * @param bool $all_option
     */
    public static function  all_row($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false) {
      echo "<tr><td class='label'>$label</td>";
      GL_UI::all_cells(null, $name, $selected_id, $skip_bank_accounts, $cells, $all_option);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $force
     * @param string $class
     * @param string $id
     *
     * @return string
     */
    public static function  view($type, $trans_no, $label = "", $force = false, $class = '', $id = '') {
      if (!$force && !User::_show_gl()) {
        return "";
      }
      $icon = false;
      if ($label == "") {
        $label = _("GL");
        $icon  = ICON_GL;
      }
      return Display::viewer_link($label, "banking/view/gl_trans.php?type_id=$type&trans_no=$trans_no", $class, $id, $icon);
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     *
     * @return string
     */
    public static function  view_cell($type, $trans_no, $label = "") {
      $str = GL_UI::view($type, $trans_no, $label);
      if ($str != "") {
        return "<td>$str</td>";
      }
      return $str;
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
      $view_str = Debtor::viewTrans($type, $trans_no, $label, $icon, $class, $id, $raw);
      if ($view_str != null) {
        return $view_str;
      }
      $view_str = Purch_UI::viewTrans($type, $trans_no, $label, $icon, $class, $id, $raw);
      if ($view_str != null) {
        return $view_str;
      }
      $view_str = Bank_UI::viewTrans($type, $trans_no, $label, $icon, $class, $id, $raw);

      if ($view_str != null) {
        return $view_str;
      }
      $view_str = Item_UI::viewTrans($type, $trans_no, $label, $icon, $class, $id, $raw);
      if ($view_str != null) {
        return $view_str;
      }
      $view_str = WO::viewTrans($type, $trans_no, $label, $icon, $class, $id, $raw);
      if ($view_str != null) {
        return $view_str;
      }
      $view_str = Dimensions::viewTrans($type, $trans_no, $label, $icon, $class, $id, $raw);
      if ($view_str != null) {
        return $view_str;
      }
      $view_str = GL_Journal::view($type, $trans_no, $label, $icon, $class, $id, $raw);
      if ($view_str != null) {
        return $view_str;
      }
      return null;
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function fiscalyears($name, $selected_id = null, $submit_on_change = false) {
      $sql = "SELECT * FROM fiscal_year";
      // default to the company current fiscal year
      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'id',
        '',
        array(
             'order'         => 'begin',
             'default'       => DB_Company::_get_pref('f_year'),
             'format'        => '\\ADV\\App\\Forms::fiscalYearFormat(',
             'select_submit' => $submit_on_change,
             'async'         => false
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
    public static function fiscalyears_cells($label, $name, $selected_id = null) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_UI::fiscalyears($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function fiscalyears_row($label, $name, $selected_id = null) {
      echo "<tr><td class='label'>$label</td>";
      GL_UI::fiscalyears_cells(null, $name, $selected_id);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     *
     * @return string
     */
    public static function payment_terms($name, $selected_id = null, $disabled = null) {
      if ($disabled === null) {
        $disabled = (!User::_i()->hasAccess(SA_CUSTOMER_CREDIT));
      }
      $sql = "SELECT terms_indicator, terms, inactive FROM payment_terms";
      return Forms::selectBox($name, $selected_id, $sql, 'terms_indicator', 'terms_indicator', array('disabled' => $disabled));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     */
    public static function payment_terms_cells($label, $name, $selected_id = null, $disabled = null) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_UI::payment_terms($name, $selected_id, $disabled);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     */
    public static function payment_terms_row($label, $name, $selected_id = null, $disabled = null) {
      echo "<tr><td class='label'>$label</td>";
      GL_UI::payment_terms_cells(null, $name, $selected_id, $disabled);
      echo "</tr>\n";
    }
  }
