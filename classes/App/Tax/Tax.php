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
  namespace ADV\App\Tax;

  use Tax_Groups;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\Cell;
  use ADV\App\User;
  use ADV\Core\Input\Input;
  use ADV\Core\Num;
  use ADV\Core\DB\DB;
  use Tax_ItemType;

  /** **/
  class Tax
  {
    /***
     * @static
     *
     * @param      $stock_id
     * @param      $price
     * @param      $tax_group
     * @param      $tax_included
     * @param null $tax_group_array
     *
     * @return float|int
     * returns the price of a given item minus any included taxes
     * for item $stock_id with line price $price,
     * with applicable tax rates $tax_group_array or group id $tax_group

     */
    public static function tax_free_price($stock_id, $price, $tax_group, $tax_included, $tax_group_array = null) {
      // if price is zero, then can't be taxed !
      if ($price == 0) {
        return 0;
      }
      if ($tax_included == 0) {
        return $price;
      }
      // if array already read, then make a copy and use that
      if ($tax_group_array) {
        $ret_tax_array = $tax_group_array;
      } else {
        $ret_tax_array = Tax_Groups::get_items_as_array($tax_group);
      }
      //print_r($ret_tax_array);
      $tax_array = Tax::get_all_for_item($stock_id, $ret_tax_array);
      // if no exemptions or taxgroup is empty, then no included/excluded taxes
      if ($tax_array == null) {
        return $price;
      }
      $tax_multiplier = 0;
      // loop for all items
      foreach ($tax_array as $taxitem) {
        $tax_multiplier += $taxitem["rate"];
      }
      return $price / (1 + ($tax_multiplier / 100));
    }
    /***
     * @static
     *
     * @param      $stock_id
     * @param      $price
     * @param bool $tax_group
     *
     * @return float|int
     * Full price (incl. TAX) for item $stock_id with line price $price,
     * with tax rates $tax_group_array or applicable group $tax_group

     */
    public static function for_item($stock_id, $price, $tax_group = false) {
      // if price is zero, then can't be taxed !
      if ($price == 0) {
        return 0;
      }
      if (!$tax_group) {
        $tax_group = Tax_Groups::get_for_item($stock_id);
      }
      // if array already read, then make a copy and use that
      $ret_tax_array = Tax_Groups::get_items_as_array($tax_group[0]);
      $tax_array     = Tax::get_all_for_item($stock_id, $ret_tax_array);
      // if no exemptions or taxgroup is empty, then no included/excluded taxes
      if ($tax_array == null) {
        return 0;
      }
      $tax_multiplier = 0;
      // loop for all items
      foreach ($tax_array as $taxitem) {
        $tax_multiplier += $taxitem["rate"];
      }
      return $price * (($tax_multiplier / 100));
    }
    /**
     * @static
     *
     * @param      $stock_id
     * @param      $price
     * @param      $tax_group
     * @param      $tax_included
     * @param null $tax_group_array
     *
     * @return int
     */
    public static function full_price_for_item($stock_id, $price, $tax_group, $tax_included, $tax_group_array = null) {
      // if price is zero, then can't be taxed !
      if ($price == 0) {
        return 0;
      }
      if ($tax_included == 1) {
        return $price;
      }
      // if array get_taxes_for_item already read, then make a copy and use that
      if ($tax_group_array) {
        $ret_tax_array = $tax_group_array;
      } else {
        $ret_tax_array = Tax_Groups::get_items_as_array($tax_group);
      }
      //print_r($ret_tax_array);
      $tax_array = Tax::get_all_for_item($stock_id, $ret_tax_array);
      // if no exemptions or taxgroup is empty, then no included/excluded taxes
      if ($tax_array == null) {
        return $price;
      }
      $tax_multiplier = 0;
      // loop for all items
      foreach ($tax_array as $taxitem) {
        $tax_multiplier += $taxitem["rate"];
      }
      return $price * (1 + ($tax_multiplier / 100));
    }
    /***
     * @static
     *
     * @param $stock_id
     * @param $tax_group_items_array
     *
     * @return array|null
     * return an array of (tax_type_id, tax_type_name, sales_gl_code, purchasing_gl_code, rate)
     */
    //
    public static function get_all_for_item($stock_id, $tax_group_items_array) {
      $item_tax_type = Tax_ItemType::get_for_item($stock_id);
      // if the item is exempt from all taxes then return 0
      if ($item_tax_type["exempt"]) {
        return null;
      }
      // get the exemptions for this item tax type
      $item_tax_type_exemptions_db = Tax_ItemType::get_exemptions($item_tax_type["id"]);
      // read them all into an array to minimize db querying
      $item_tax_type_exemptions = [];
      while ($item_tax_type_exemp = DB::_fetch($item_tax_type_exemptions_db)) {
        $item_tax_type_exemptions[] = $item_tax_type_exemp["tax_type_id"];
      }
      $ret_tax_array = [];
      // if any of the taxes of the tax group are inget_tax_for_items the exemptions, then skip
      foreach ($tax_group_items_array as $tax_group_item) {
        $skip = false;
        // if it's in the exemptions, skip
        foreach ($item_tax_type_exemptions as $exemption) {
          if (($tax_group_item['tax_type_id'] == $exemption)) {
            $skip = true;
            break;
          }
        }
        if (!$skip) {
          $index                 = $tax_group_item['tax_type_id'];
          $ret_tax_array[$index] = $tax_group_item;
        }
      }
      return $ret_tax_array;
    }
    /***
     * @static
     *
     * @param      $items
     * @param      $prices
     * @param      $shipping_cost
     * @param      $tax_group
     * @param null $tax_included
     * @param null $tax_items_array
     *
     * @return array|null
     * return an array of (tax_type_id, tax_type_name, sales_gl_code, purchasing_gl_code, rate, included_in_price, Value)

     */
    public static function for_items($items, $prices, $shipping_cost, $tax_group, $tax_included = null, $tax_items_array = null) {
      // first create and set an array with all the tax types of the tax group
      if ($tax_items_array != null) {
        $ret_tax_array = $tax_items_array;
      } else {
        $ret_tax_array = Tax_Groups::get_items_as_array($tax_group);
      }
      foreach ($ret_tax_array as $k => $t) {
        $ret_tax_array[$k]['Net'] = 0;
      }
      // loop for all items
      for ($i = 0; $i < count($items); $i++) {
        $item_taxes = Tax::get_all_for_item($items[$i], $ret_tax_array);
        if ($item_taxes != null) {
          foreach ($item_taxes as $item_tax) {
            $index = $item_tax['tax_type_id'];
            if ($tax_included == 1) { // 2008-11-26 Joe Hunt Taxes are stored without roundings
              $nprice = Tax::tax_free_price($items[$i], $prices[$i], $tax_group, $tax_included);
              $ret_tax_array[$index]['Value'] += ($nprice * $item_tax['rate'] / 100);
              $ret_tax_array[$index]['Net'] += $nprice;
            } else {
              $ret_tax_array[$index]['Value'] += ($prices[$i] * $item_tax['rate'] / 100);
              $ret_tax_array[$index]['Net'] += $prices[$i];
            }
          }
        }
      }
      // add the shipping taxes, only if non-zero, and only if tax group taxes shipping
      if ($shipping_cost != 0) {
        $item_taxes = Tax_Groups::for_shipping_as_array();
        if ($item_taxes != null) {
          if ($tax_included == 1) {
            $tax_rate = 0;
            foreach ($item_taxes as $item_tax) {
              $index = $item_tax['tax_type_id'];
              if (isset($ret_tax_array[$index])) {
                $tax_rate += $item_tax['rate'];
              }
            }
            $shipping_net = $shipping_cost / (1 + ($tax_rate / 100));
          }
          foreach ($item_taxes as $item_tax) {
            $index = $item_tax['tax_type_id'];
            if (isset($ret_tax_array[$index])) {
              if ($tax_included == 1) { // 2008-11-26 Joe Hunt Taxes are stored without roundings
                $ret_tax_array[$index]['Value'] += ($shipping_net * $item_tax['rate'] / 100);
                $ret_tax_array[$index]['Net'] += $shipping_net;
              } else {
                $ret_tax_array[$index]['Value'] += ($shipping_cost * $item_tax['rate'] / 100);
                $ret_tax_array[$index]['Net'] += $shipping_cost;
              }
            }
          }
        }
      }
      return $ret_tax_array;
    }
    /**
     * @static
     *
     * @param $account_code
     *
     * @return bool
     */
    public static function is_account($account_code) {
      $sql
              = "SELECT id FROM tax_types WHERE
        sales_gl_code=" . DB::_escape($account_code) . " OR purchasing_gl_code=" . DB::_escape($account_code);
      $result = DB::_query($sql, "checking account is tax account");
      if (DB::_numRows($result) > 0) {
        $acct = DB::_fetch($result);
        return $acct['id'];
      } else {
        return false;
      }
    }
    /**
     * @static
     *
     * @param      $taxes
     * @param      $columns
     * @param      $tax_included
     * @param int  $leftspan
     * @param bool $tax_correcting
     *
     * @return int|string
     */
    public static function edit_items($taxes, $columns, $tax_included, $leftspan = 0, $tax_correcting = false) {
      $total = 0;
      foreach ($taxes as $taxitem) {
        if ($tax_included) {
          Table::label(
            _("Included") . " " . $taxitem['tax_type_name'] . " (" . $taxitem['rate'] . "%) " . _("Amount:") . " ",
            Num::_format($taxitem['Value'], User::_price_dec()),
            "colspan=$columns class='alignright'",
            "class='alignright'",
            $leftspan
          );
        } else {
          $total += $taxitem['Value'];
          Table::label(
            $taxitem['tax_type_name'] . " (" . $taxitem['rate'] . "%)",
            Num::_format($taxitem['Value'], User::_price_dec()),
            "colspan=$columns class='alignright'",
            "class='alignright'",
            $leftspan
          );
        }
      }
      if ($tax_correcting) {
        Cell::label(_("Tax Correction"), "colspan=$columns style='background:inherit; text-align:right; width:90%'");
        Forms::amountCellsSmall(null, 'ChgTax', Num::_priceFormat(Input::_post('ChgTax'), User::_price_dec()));
        echo '</tr>';
        $total += Input::_post('ChgTax');
      }
      return $total;
    }
  }

