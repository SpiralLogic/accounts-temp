<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  // insert/update sales delivery
  //
  class Sales_Delivery {
    /**
     * @static
     *
     * @param $delivery
     * @param $bo_policy
     *
     * @return int
     */
    public static function add(Sales_Order $delivery, $bo_policy) {
      $trans_no = $delivery->trans_no;
      if (is_array($trans_no)) {
        $trans_no = key($trans_no);
      }
      DB::_begin();
      $customer             = Debtor::get($delivery->debtor_id);
      $delivery_items_total = $delivery->get_items_total_dispatch();
      $freight_tax          = $delivery->get_shipping_tax();
      // mark sales order for concurrency conflicts check
      Sales_Order::update_version($delivery->src_docs);
      $tax_total = 0;
      $taxes     = $delivery->get_taxes(); // all taxes with freight_tax
      foreach ($taxes as $taxitem) {
        $tax_total += $taxitem['Value'];
      }
      /* Insert/update the debtor_trans */
      $delivery_no = Debtor_Trans::write(
        ST_CUSTDELIVERY,
        $trans_no,
        $delivery->debtor_id,
        $delivery->Branch,
        $delivery->document_date,
        $delivery->reference,
        $delivery_items_total,
        0,
        $delivery->tax_included ? 0 : $tax_total - $freight_tax,
        $delivery->freight_cost,
        $delivery->tax_included ? 0 : $freight_tax,
        $delivery->sales_type,
        $delivery->order_no,
        0,
        $delivery->ship_via,
        $delivery->due_date,
        0,
        0,
        $delivery->dimension_id,
        $delivery->dimension2_id
      );
      if ($trans_no == 0) {
        $delivery->trans_no = array($delivery_no => 0);
      } else {
        GL_Trans::void(ST_CUSTDELIVERY, $delivery_no, true);
        Inv_Movement::void(ST_CUSTDELIVERY, $delivery_no);
        GL_Trans::void_tax_details(ST_CUSTDELIVERY, $delivery_no);
        DB_Comments::delete(ST_CUSTDELIVERY, $delivery_no);
      }
      /** @var Sales_Line $delivery_line */
      foreach ($delivery->line_items as $delivery_line) {
        $line_price         = $delivery_line->line_price();
        $line_taxfree_price = Tax::tax_free_price($delivery_line->stock_id, $delivery_line->price, 0, $delivery->tax_included, $delivery->tax_group_array);
        $line_tax           = Tax::full_price_for_item(
          $delivery_line->stock_id,
          $delivery_line->price,
          0,
          $delivery->tax_included,
          $delivery->tax_group_array
        ) - $line_taxfree_price;
        if ($trans_no != 0) // Inserted 2008-09-25 Joe Hunt
        {
          $delivery_line->standard_cost = Item_Price::get_standard_cost($delivery_line->stock_id);
        }
        /* add delivery details for all lines */
        Debtor_TransDetail::add(
          ST_CUSTDELIVERY,
          $delivery_no,
          $delivery_line->stock_id,
          $delivery_line->description,
          $delivery_line->qty_dispatched,
          $delivery_line->line_price(),
          $line_tax,
          $delivery_line->discount_percent,
          $delivery_line->standard_cost,
          $trans_no ? $delivery_line->id : 0
        );
        // Now update sales_order_details for the quantity delivered
        if ($delivery_line->qty_old != $delivery_line->qty_dispatched) {
          Sales_Order::update_parent_line(ST_CUSTDELIVERY, $delivery_line->src_id, $delivery_line->qty_dispatched - $delivery_line->qty_old);
        }
        if ($delivery_line->qty_dispatched != 0) {
          Inv_Movement::add_for_debtor(
            ST_CUSTDELIVERY,
            $delivery_line->stock_id,
            $delivery_no,
            $delivery->location,
            $delivery->document_date,
            $delivery->reference,
            -$delivery_line->qty_dispatched,
            $delivery_line->standard_cost,
            1,
            $line_price,
            $delivery_line->discount_percent
          );
          $stock_gl_code = Item::get_gl_code($delivery_line->stock_id);
          /* insert gl_trans to credit stock and debit cost of sales at standard cost*/
          if ($delivery_line->standard_cost != 0) {
            /*first the cost of sales entry*/
            // 2008-08-01. If there is a Customer Dimension, then override with this,
            // else take the Item Dimension (if any)
            $dim  = ($delivery->dimension_id != $customer['dimension_id'] ? $delivery->dimension_id :
              ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_code["dimension_id"]));
            $dim2 = ($delivery->dimension2_id != $customer['dimension2_id'] ? $delivery->dimension2_id :
              ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] : $stock_gl_code["dimension2_id"]));
            GL_Trans::add_std_cost(
              ST_CUSTDELIVERY,
              $delivery_no,
              $delivery->document_date,
              $stock_gl_code["cogs_account"],
              $dim,
              $dim2,
              "",
              $delivery_line->standard_cost * $delivery_line->qty_dispatched,
              PT_CUSTOMER,
              $delivery->debtor_id,
              "The cost of sales GL posting could not be inserted"
            );
            /*now the stock entry*/
            GL_Trans::add_std_cost(
              ST_CUSTDELIVERY,
              $delivery_no,
              $delivery->document_date,
              $stock_gl_code["inventory_account"],
              0,
              0,
              "",
              (-$delivery_line->standard_cost * $delivery_line->qty_dispatched),
              PT_CUSTOMER,
              $delivery->debtor_id,
              "The stock side of the cost of sales GL posting could not be inserted"
            );
          } /* end of if GL and stock integrated and standard cost !=0 */
        } /*quantity dispatched is more than 0 */
      } /*end of order_line loop */
      if ($bo_policy == 0) {
        // if cancelling any remaining quantities
        Sales_Order::close($delivery->order_no);
      }
      // taxes - this is for printing purposes
      foreach ($taxes as $taxitem) {
        if ($taxitem['Net'] != 0) {
          $ex_rate = Bank_Currency::exchange_rate_from_home(Bank_Currency::for_debtor($delivery->debtor_id), $delivery->document_date);
          GL_Trans::add_tax_details(
            ST_CUSTDELIVERY,
            $delivery_no,
            $taxitem['tax_type_id'],
            $taxitem['rate'],
            $delivery->tax_included,
            $taxitem['Value'],
            $taxitem['Net'],
            $ex_rate,
            $delivery->document_date,
            $delivery->reference
          );
        }
      }
      DB_Comments::add(ST_CUSTDELIVERY, $delivery_no, $delivery->document_date, $delivery->Comments);
      if ($trans_no == 0) {
        Ref::save(ST_CUSTDELIVERY, $delivery->reference);
      }
      DB::_commit();
      return $delivery_no;
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no) {
      DB::_begin();
      GL_Trans::void($type, $type_no, true);
      // reverse all the changes in the sales order
      $items_result = Debtor_TransDetail::get($type, $type_no);
      $order        = Debtor_Trans::get_order($type, $type_no);
      if ($order) {
        $order_items = Sales_Order::get_details($order, ST_SALESORDER);
        while ($row = DB::_fetch($items_result)) {
          $order_line = DB::_fetch($order_items);
          Sales_Order::update_parent_line(ST_CUSTDELIVERY, $order_line['id'], -$row['quantity']);
        }
      }
      // clear details after they've been reversed in the sales order
      Debtor_TransDetail::void($type, $type_no);
      GL_Trans::void_tax_details($type, $type_no);
      Sales_Allocation::void($type, $type_no);
      // do this last because other voidings can depend on it
      // DO NOT MOVE THIS ABOVE VOIDING or we can end up with trans with alloc < 0
      Debtor_Trans::void($type, $type_no);
      DB::_commit();
    }
    /**
     * @static
     *
     * @param $order
     *
     * @return bool
     */
    public static function check_data($order) {
      if (!isset($_POST['DispatchDate']) || !Dates::_isDate($_POST['DispatchDate'])) {
        Event::error(_("The entered date of delivery is invalid."));
        JS::_setFocus('DispatchDate');
        return false;
      }
      if (!Dates::_isDateInFiscalYear($_POST['DispatchDate'])) {
        Event::error(_("The entered date of delivery is not in fiscal year."));
        JS::_setFocus('DispatchDate');
        return false;
      }
      if (!isset($_POST['due_date']) || !Dates::_isDate($_POST['due_date'])) {
        Event::error(_("The entered dead-line for invoice is invalid."));
        JS::_setFocus('due_date');
        return false;
      }
      if ($order->trans_no == 0) {
        if (!Ref::is_valid($_POST['ref'])) {
          Event::error(_("You must enter a reference."));
          JS::_setFocus('ref');
          return false;
        }
        if ($order->trans_no == 0 && !Ref::is_new($_POST['ref'], ST_CUSTDELIVERY)) {
          $_POST['ref'] = Ref::get_next(ST_CUSTDELIVERY);
        }
      }
      if ($_POST['ChargeFreightCost'] == "") {
        $_POST['ChargeFreightCost'] = Num::_priceFormat(0);
      }
      if (!Validation::post_num('ChargeFreightCost', 0)) {
        Event::error(_("The entered shipping value is not numeric."));
        JS::_setFocus('ChargeFreightCost');
        return false;
      }
      if ($order->has_items_dispatch() == 0 && Validation::input_num('ChargeFreightCost') == 0) {
        Event::error(_("There are no item quantities on this delivery note."));
        return false;
      }
      if (!static::check_quantities($order)) {
        return false;
      }
      return true;
    }
    /**
     * @static
     *
     * @param $order
     */
    public static function copyFromPost($order) {
      $order->ship_via      = $_POST['ship_via'];
      $order->freight_cost  = Validation::input_num('ChargeFreightCost');
      $order->document_date = $_POST['DispatchDate'];
      $order->due_date      = $_POST['due_date'];
      $order->location      = $_POST['location'];
      $order->Comments      = $_POST['Comments'];
      if ($order->trans_no == 0) {
        $order->reference = $_POST['ref'];
      }
    }
    /**
     * @static
     *
     * @param $order
     */
    public static function copyToPost($order) {
      $order                      = Sales_Order::check_edit_conflicts($order);
      $_POST['ship_via']          = $order->ship_via;
      $_POST['ChargeFreightCost'] = Num::_priceFormat($order->freight_cost);
      $_POST['DispatchDate']      = $order->document_date;
      $_POST['due_date']          = $order->due_date;
      $_POST['location']          = $order->location;
      $_POST['Comments']          = $order->Comments;
      $_POST['ref']               = $order->reference;
      $_POST['order_id']          = $order->order_id;
      Orders::session_set($order);
    }
    /**
     * @static
     *
     * @param $order
     *
     * @return int
     */
    public static function check_quantities($order) {
      $ok = 1;
      // Update order delivery quantities/descriptions
      foreach ($order->line_items as $line => $itm) {
        if (isset($_POST['Line' . $line])) {
          if ($order->trans_no) {
            $min = $itm->qty_done;
            $max = $itm->quantity;
          } else {
            $min = 0;
            $max = $itm->quantity - $itm->qty_done;
          }
          if ($itm->quantity > 0 && Validation::post_num('Line' . $line, $min, $max)) {
            $order->line_items[$line]->qty_dispatched = Validation::input_num('Line' . $line);
          } elseif ($itm->quantity < 0 && Validation::post_num('Line' . $line, $max, $min)) {
            $order->line_items[$line]->qty_dispatched = Validation::input_num('Line' . $line);
          } else {
            JS::_setFocus('Line' . $line);
            $ok = 0;
          }
        }
        if (isset($_POST['Line' . $line . 'Desc'])) {
          $line_desc = $_POST['Line' . $line . 'Desc'];
          if (strlen($line_desc) > 0) {
            $order->line_items[$line]->description = $line_desc;
          }
        }
      }
      // ...
      //	else
      //	 $order->freight_cost = Validation::input_num('ChargeFreightCost');
      return $ok;
    }
    /**
     * @static
     *
     * @param $order
     *
     * @return bool
     */
    public static function check_qoh($order) {
      if (!DB_Company::_get_pref('allow_negative_stock')) {
        foreach ($order->line_items as $itm) {
          if ($itm->qty_dispatched && WO::has_stock_holding($itm->mb_flag)) {
            $qoh = Item::get_qoh_on_date($itm->stock_id, $_POST['location'], $_POST['DispatchDate']);
            if ($itm->qty_dispatched > $qoh) {
              Event::error(_("The delivery cannot be processed because there is an insufficient quantity for item:") . " " . $itm->stock_id . " - " . $itm->description);
              return false;
            }
          }
        }
      }
      return true;
    }
  }
