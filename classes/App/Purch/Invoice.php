<?php
  use ADV\App\Creditor\Creditor;
  use ADV\Core\JS;
  use ADV\Core\Ajax;
  use ADV\App\Forms;
  use ADV\Core\Cell;
  use ADV\Core\Table;
  use ADV\App\Tax\Tax;
  use ADV\Core\Input\Input;
  use ADV\App\Bank\Bank;
  use ADV\App\Ref;
  use ADV\App\Item\Item;
  use ADV\App\User;
  use ADV\App\Dates;
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Purch_Invoice
  {
    /**
     * @static
     *
     * @param $creditor_trans
     * @param $creditor_id
     */
    public static function get_supplier_to_trans($creditor_trans, $creditor_id) {
      $sql                               = "SELECT suppliers.name, payment_terms.terms, " . "payment_terms.days_before_due,
        payment_terms.day_in_following_month,
        suppliers.tax_group_id, tax_groups.name As tax_group_name
        From suppliers, payment_terms, tax_groups
        WHERE suppliers.tax_group_id = tax_groups.id
        AND suppliers.payment_terms=payment_terms.terms_indicator
        AND suppliers.creditor_id = " . DB::_escape($creditor_id);
      $result                            = DB::_query($sql, "The supplier record selected: " . $creditor_id . " cannot be retrieved");
      $myrow                             = DB::_fetch($result);
      $creditor_trans->creditor_id       = $creditor_id;
      $creditor_trans->supplier_name     = $myrow['name'];
      $creditor_trans->terms_description = $myrow['terms'];
      if ($myrow['days_before_due'] == 0) {
        $creditor_trans->terms = "1" . $myrow['day_in_following_month'];
      } else {
        $creditor_trans->terms = "0" . $myrow['days_before_due'];
      }
      $creditor_trans->tax_description = $myrow['tax_group_name'];
      $creditor_trans->tax_group_id    = $myrow['tax_group_id'];
      if ($creditor_trans->tran_date == "") {
        $creditor_trans->tran_date = Dates::_today();
        if (!Dates::_isDateInFiscalYear($creditor_trans->tran_date)) {
          $creditor_trans->tran_date = Dates::_endFiscalYear();
        }
      }
      //if ($creditor_trans->due_date=="") {
      //	Creditor_Trans::get_duedate_from_terms($creditor_trans);
      //}
      Creditor_Trans::get_duedate_from_terms($creditor_trans);
    }
    /**
     * @static
     *
     * @param      $id
     * @param      $po_detail_item
     * @param      $qty_invoiced
     * @param null $chg_price
     *
     * @return array
     */
    public static function update_supplier_received($id, $po_detail_item, $qty_invoiced, $chg_price = null) {
      if ($chg_price != null) {
        $sql
                    = "SELECT act_price, unit_price FROM purch_order_details WHERE
            po_detail_item = " . DB::_escape($po_detail_item);
        $result     = DB::_query($sql, "The old actual price of the purchase order line could not be retrieved");
        $row        = DB::_fetchRow($result);
        $ret        = $row[0];
        $unit_price = $row[1]; //Added by Rasmus
        $sql
                    = "SELECT delivery_date FROM grn_batch,grn_items WHERE
            grn_batch.id = grn_items.grn_batch_id AND " . "grn_items.id=" . DB::_escape($id);
        $result     = DB::_query($sql, "The old delivery date from the received record cout not be retrieved");
        $row        = DB::_fetchRow($result);
        $date       = $row[0];
      } else {
        $ret        = 0;
        $date       = "";
        $unit_price = 0; // Added by Rasmus
      }
      $sql
        = "UPDATE purch_order_details
        SET qty_invoiced = qty_invoiced + " . DB::_escape($qty_invoiced);
      if ($chg_price != null) {
        $sql .= " , act_price = " . DB::_escape($chg_price);
      }
      $sql .= " WHERE po_detail_item = " . DB::_escape($po_detail_item);
      DB::_query($sql, "The quantity invoiced of the purchase order line could not be updated");
      $sql
        = "UPDATE grn_items
 SET quantity_inv = quantity_inv + " . DB::_escape($qty_invoiced) . "
 WHERE id = " . DB::_escape($id);
      DB::_query($sql, "The quantity invoiced off the items received record could not be updated");
      return array($ret, $date, $unit_price);
    }
    /**
     * @static
     *
     * @param $supplier
     * @param $old_date
     * @param $date
     * @param $amount1
     * @param $amount2
     *
     * @return float
     */
    public static function get_diff_in_home_currency($supplier, $old_date, $date, $amount1, $amount2) {
      $dec = User::_price_dec();
      Num::_priceDecimal($amount2, $dec);
      $currency = Bank_Currency::for_creditor($supplier);
      $ex_rate  = Bank_Currency::exchange_rate_to_home($currency, $old_date);
      $amount1  = $amount1 / $ex_rate;
      $ex_rate  = Bank_Currency::exchange_rate_to_home($currency, $date);
      $amount2  = $amount2 / $ex_rate;
      $diff     = $amount2 - $amount1;
      return Num::_round($diff, $dec);
    }
    /**
     * @static
     *
     * @param Creditor_Trans $creditor_trans
     * @param int            $invoice_no
     *
     * @return int
     */
    public static function add(Creditor_Trans $creditor_trans, $invoice_no = 0) // do not receive as ref because we change locally
    {
      //$company_currency = Bank_Currency::for_company();
      /*Start an sql transaction */
      DB::_begin();
      $tax_total = 0;
      $taxes     = $creditor_trans->get_taxes($creditor_trans->tax_group_id);
      ;
      foreach ($taxes as $taxitem) {
        $taxitem['Value'] = Num::_round($taxitem['Value'], User::_price_dec());
        $tax_total += $taxitem['Value'];
      }
      $invoice_items_total = $creditor_trans->get_total_charged($creditor_trans->tax_group_id);
      $tax_total;
      if ($creditor_trans->is_invoice) {
        $trans_type = ST_SUPPINVOICE;
      } else {
        $trans_type = ST_SUPPCREDIT;
        // let's negate everything because it's a credit note
        $invoice_items_total         = -$invoice_items_total;
        $tax_total                   = -$tax_total;
        $creditor_trans->ov_discount = -$creditor_trans->ov_discount; // this isn't used at all...
      }
      $date_   = $creditor_trans->tran_date;
      $ex_rate = Bank_Currency::exchange_rate_from_home(Bank_Currency::for_creditor($creditor_trans->creditor_id), $date_);
      /*First insert the invoice into the creditor_trans table*/
      $invoice_id = Creditor_Trans::add(
        $trans_type, $creditor_trans->creditor_id, $date_, $creditor_trans->due_date, $creditor_trans->reference, $creditor_trans->supplier_reference, $invoice_items_total, $tax_total, $creditor_trans->ov_discount
      );
      $total      = 0;
      /* Now the control account */
      $supplier_accounts = Creditor::get_accounts_name($creditor_trans->creditor_id);
      $total += Creditor_Trans::add_gl(
        $trans_type, $invoice_id, $date_, $supplier_accounts["payable_account"], 0, 0, -($invoice_items_total + $tax_total + $creditor_trans->ov_discount), $creditor_trans->creditor_id, "The general ledger transaction for the control total could not be added"
      );
      /*Loop through the GL Entries and create a debit posting for each of the accounts entered */
      /*the postings here are a little tricky, the logic goes like this:
      if its a general ledger amount it goes straight to the account specified

      if its a GRN amount invoiced then :

      The cost as originally credited to GRN suspense on arrival of items is debited to GRN suspense. Any difference
      between the std cost and the currency cost charged as converted at the ex rate of of the invoice is written off
      to the purchase price variance account applicable to the item being invoiced.
      */
      foreach ($creditor_trans->gl_codes as $entered_gl_code) {
        /*GL Items are straight forward - just do the debit postings to the GL accounts specified -
the credit is to creditors control act done later for the total invoice value + tax*/
        if (!$creditor_trans->is_invoice) {
          $entered_gl_code->amount = -$entered_gl_code->amount;
        }
        $memo_ = $entered_gl_code->memo_;
        $total += Creditor_Trans::add_gl(
          $trans_type, $invoice_id, $date_, $entered_gl_code->gl_code, $entered_gl_code->gl_dim, $entered_gl_code->gl_dim2, $entered_gl_code->amount, $creditor_trans->creditor_id, "", 0, $memo_
        );
        Purch_Line::add_gl_item($trans_type, $invoice_id, $entered_gl_code->gl_code, $entered_gl_code->amount, $memo_);
        // store tax details if the gl account is a tax account
        if (!$creditor_trans->is_invoice) {
          $entered_gl_code->amount = -$entered_gl_code->amount;
        }
        GL_Trans::add_gl_tax_details($entered_gl_code->gl_code, $trans_type, $invoice_id, $entered_gl_code->amount, $ex_rate, $date_, $creditor_trans->supplier_reference);
      }
      foreach ($creditor_trans->grn_items as $entered_grn) {
        if (!$creditor_trans->is_invoice) {
          $entered_grn->this_quantity_inv = -$entered_grn->this_quantity_inv;
          Purch_GRN::set_item_credited($entered_grn, $creditor_trans->creditor_id, $invoice_id, $date_);
        }
        $line_taxfree  = $entered_grn->taxfree_charge_price($creditor_trans->tax_group_id);
        $line_tax      = $entered_grn->full_charge_price($creditor_trans->tax_group_id) - $line_taxfree;
        $stock_gl_code = Item::get_gl_code($entered_grn->item_code);
        $iv_act        = (Item::is_inventory_item($entered_grn->item_code) ? $stock_gl_code["inventory_account"] : $stock_gl_code["cogs_account"]);
        $total += Creditor_Trans::add_gl(
          $trans_type, $invoice_id, $date_, $iv_act, $stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'], $entered_grn->this_quantity_inv * $line_taxfree, $creditor_trans->creditor_id
        );
        // -------------- if price changed since po received. 16 Aug 2008 Joe Hunt
        if ($creditor_trans->is_invoice) {
          $old = static::update_supplier_received($entered_grn->id, $entered_grn->po_detail_item, $entered_grn->this_quantity_inv, $entered_grn->chg_price);
          // Since the standard cost is always calculated on basis of the po unit_price,
          // this is also the price that should be the base of calculating the price diff.
          // In cases where there is two different po invoices on the same delivery with different unit prices this will not work either
          //$old_price = $old[0];
          $old_price = $old[2];
          /*
          If statement is removed. Should always check for deliveries nomatter if there has been a price change.
          */
          //if ($old_price != $entered_grn->chg_price) // price-change, so update
          //{
          //$diff = $entered_grn->chg_price - $old_price;
          $old_date = Dates::_sqlToDate($old[1]);
          $diff     = static::get_diff_in_home_currency($creditor_trans->creditor_id, $old_date, $date_, $old_price, $entered_grn->chg_price);
          // always return due to change in currency.
          $mat_cost = Purch_GRN::update_average_material_cost(null, $entered_grn->item_code, $diff, $entered_grn->this_quantity_inv, $old_date, true);
          // added 2008-12-08 Joe Hunt. Update the purchase data table
          $current_purchase_price = Item_Price::getPriceBySupplier(Item::getStockID($entered_grn->item_code), $creditor_trans->creditor_id);
          $current_purchase_price = ($current_purchase_price) ? $current_purchase_price->price : 0;
          if ($current_purchase_price == 0 || $current_purchase_price > $entered_grn->chg_price) {
            Purch_Order::add_or_update_data($creditor_trans->creditor_id, $entered_grn->item_code, $entered_grn->chg_price);
          }
          $deliveries = Item::get_deliveries_between($entered_grn->item_code, $old_date, Dates::_today()); // extend the period, if invoice is before any deliveries.
          if ($deliveries[0] != 0) // have deliveries been done during the period?
          {
            $deliveries[1] /= $deliveries[0];
            $amt = ($mat_cost - $deliveries[1]) * $deliveries[0]; // $amt in home currency
            if ($amt != 0.0) {
              GL_Trans::add(
                $trans_type, $invoice_id, $date_, $stock_gl_code["cogs_account"], $stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'], _("Cost diff."), $amt, null, null, null, "The general ledger transaction could not be added for the price variance of the inventory item"
              );
              GL_Trans::add(
                $trans_type, $invoice_id, $date_, $iv_act, 0, 0, _("Cost diff."), -$amt, null, null, null, "The general ledger transaction could not be added for the price variance of the inventory item"
              );
            }
            Inv_Transfer::update_pid(ST_CUSTDELIVERY, $entered_grn->item_code, $old_date, $date_, 0, $mat_cost);
          }
          Inv_Transfer::update_pid(ST_SUPPRECEIVE, $entered_grn->item_code, $old_date, $old_date, $creditor_trans->creditor_id, $mat_cost);
          //}
        }
        // ----------------------------------------------------------------------
        Purch_Line::add_item(
          $trans_type, $invoice_id, $entered_grn->item_code, $entered_grn->description, 0, $line_taxfree, $line_tax, $entered_grn->this_quantity_inv, $entered_grn->id, $entered_grn->po_detail_item, "", "", $entered_grn->discount, $entered_grn->exp_price
        );
      } /* end of GRN postings */
      /* Now the TAX account */
      $taxes = $creditor_trans->get_taxes($creditor_trans->tax_group_id, 0, false); // 2009.08-18 Joe Hunt. We have already got the gl lines
      foreach ($taxes as $taxitem) {
        if ($taxitem['Net'] != 0) {
          if (!$creditor_trans->is_invoice) {
            $taxitem['Net']   = -$taxitem['Net'];
            $taxitem['Value'] = -$taxitem['Value'];
          }
          // here we suppose that tax is never included in price (we are company customer).
          GL_Trans::add_tax_details(
            $trans_type, $invoice_id, $taxitem['tax_type_id'], $taxitem['rate'], 0, $taxitem['Value'], $taxitem['Net'], $ex_rate, $date_, $creditor_trans->supplier_reference
          );
          if (!$creditor_trans->is_invoice) {
            $taxitem['Value'] = -$taxitem['Value'];
          }
          $total += Creditor_Trans::add_gl(
            $trans_type, $invoice_id, $date_, $taxitem['purchasing_gl_code'], 0, 0, $taxitem['Value'], $creditor_trans->creditor_id, "A general ledger transaction for the tax amount could not be added"
          );
        }
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance($trans_type, $invoice_id, $date_, -$total, PT_SUPPLIER, $creditor_trans->creditor_id);
      DB_Comments::add($trans_type, $invoice_id, $date_, $creditor_trans->Comments);
      Ref::save($trans_type, $creditor_trans->reference);
      if ($invoice_no != 0) {
        $invoice_alloc_balance = Purch_Allocation::get_balance(ST_SUPPINVOICE, $invoice_no);
        if ($invoice_alloc_balance > 0) { //the invoice is not already fully allocated
          $trans           = Creditor_Trans::get($invoice_no, ST_SUPPINVOICE);
          $total           = $trans['Total'];
          $allocate_amount = ($invoice_alloc_balance > $total) ? $total : $invoice_alloc_balance;
          /*Now insert the allocation record if > 0 */
          if ($allocate_amount != 0) {
            Purch_Allocation::update(ST_SUPPINVOICE, $invoice_no, $allocate_amount);
            Purch_Allocation::update(ST_SUPPCREDIT, $invoice_id, $allocate_amount); // ***
            Purch_Allocation::add($allocate_amount, ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no, $date_);
            // Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////
            Bank::exchange_variation(ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no, $date_, $allocate_amount, PT_SUPPLIER);
            ///////////////////////////////////////////////////////////////////////////
          }
        }
      }
      DB::_commit();
      return $invoice_id;
    }
    // get all the invoices/credits for a given PO - quite long route to get there !
    /**
     * @static
     *
     * @param $po_number
     *
     * @return null|PDOStatement
     */
    public static function get_po_credits($po_number) {
      $sql
        = "SELECT DISTINCT creditor_trans.trans_no, creditor_trans.type,
        ov_amount+ov_discount+ov_gst AS Total,
        creditor_trans.tran_date
        FROM creditor_trans, creditor_trans_details, " . "purch_order_details, purch_orders
        WHERE creditor_trans_details.creditor_trans_no = creditor_trans.trans_no
        AND creditor_trans_details.po_detail_item_id = purch_order_details.po_detail_item
        AND purch_orders.creditor_id = creditor_trans.creditor_id
        AND purch_order_details.order_no = " . DB::_escape($po_number);
      return DB::_query($sql, "The invoices/credits for the po $po_number could not be retreived");
    }
    /**
     * @static
     *
     * @param $trans_no
     * @param $trans_type
     * @param $creditor_trans
     *
     * @return void
     */
    public static function get($trans_no, $trans_type, $creditor_trans) {
      $sql
              = "SELECT creditor_trans.*, name FROM creditor_trans,suppliers
        WHERE trans_no = " . DB::_escape($trans_no) . " AND type = " . DB::_escape($trans_type) . "
        AND suppliers.creditor_id=creditor_trans.creditor_id";
      $result = DB::_query($sql, "Cannot retreive a supplier transaction");
      if (DB::_numRows($result) == 1) {
        $trans_row                     = DB::_fetch($result);
        $creditor_trans->creditor_id   = $trans_row["creditor_id"];
        $creditor_trans->supplier_name = $trans_row["name"];
        $creditor_trans->tran_date     = Dates::_sqlToDate($trans_row["tran_date"]);
        $creditor_trans->due_date      = Dates::_sqlToDate($trans_row["due_date"]);
        //$creditor_trans->Comments = $trans_row["TransText"];
        $creditor_trans->Comments           = "";
        $creditor_trans->reference          = $trans_row["reference"];
        $creditor_trans->supplier_reference = $trans_row["supplier_reference"];
        $creditor_trans->ov_amount          = $trans_row["ov_amount"];
        $creditor_trans->ov_discount        = $trans_row["ov_discount"];
        $creditor_trans->ov_gst             = $trans_row["ov_gst"];
        $id                                 = $trans_row["trans_no"];
        $result                             = Purch_Line::get_for_invoice($trans_type, $id);
        if (DB::_numRows($result) > 0) {
          while ($details_row = DB::_fetch($result)) {
            if ($details_row["gl_code"] == 0) {
              $creditor_trans->add_grn_to_trans(
                $details_row["grn_item_id"], $details_row["po_detail_item_id"], $details_row["stock_id"], $details_row["description"], 0, 0, $details_row["quantity"], 0, $details_row["FullUnitPrice"], false, 0, 0, $details_row['discount'], $details_row['exp_price']
              );
            } else {
              $creditor_trans->add_gl_codes_to_trans(
                $details_row["gl_code"], GL_Account::get_name($details_row["gl_code"]), 0, 0, $details_row["FullUnitPrice"], $details_row["memo_"]
              );
            }
          }
        } else {
          return Event::error("Invalid supptrans details for supptrans number : $trans_no and type : $trans_type", $sql, true);
        }
      } else {
        return Event::error("Invalid supptrans number : $trans_no and type : $trans_type", $sql, true);
      }
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $po_item_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get_for_item($stock_id, $po_item_id) {
      $sql
              = "SELECT *, tran_date FROM creditor_trans_details, creditor_trans
        WHERE creditor_trans_type = " . ST_SUPPINVOICE . " AND stock_id = " . DB::_escape($stock_id) . " AND po_detail_item_id = " . DB::_escape($po_item_id) . "
        AND creditor_trans_no = trans_no";
      $result = DB::_query($sql, "Cannot retreive supplier transaction detail records");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no) {
      DB::_begin();
      $trans = Creditor_Trans::get($type_no, $type);
      Bank_Trans::void($type, $type_no, true);
      GL_Trans::void($type, $type_no, true);
      Purch_Allocation::void($type, $type_no);
      Creditor_Trans::void($type, $type_no);
      $result = Purch_Line::get_for_invoice($type, $type_no);
      // now remove this invoice/credit from any GRNs/POs that it's related to
      if (DB::_numRows($result) > 0) {
        $date_ = Dates::_today();
        while ($details_row = DB::_fetch($result)) {
          if ((int) $details_row["grn_item_id"] > 0) // it can be empty for GL items
          {
            // Changed 2008-10-17 by Joe Hunt to get the avg. material cost updated
            $old = static::update_supplier_received(
              $details_row["grn_item_id"], $details_row["po_detail_item_id"], -$details_row["quantity"], $details_row["FullUnitPrice"]
            );
            //$diff = $details_row["FullUnitPrice"] - $old[2];
            $old_date = Dates::_sqlToDate($old[1]);
            $batch    = Purch_GRN::get_batch_for_item($details_row["grn_item_id"]);
            $grn      = Purch_GRN::get_batch($batch);
            if ($type == ST_SUPPCREDIT) // credit note 2009-06-14 Joe Hunt Must restore the po and grn
            { // We must get the corresponding invoice item to check for price chg.
              $match = static::get_for_item($details_row["stock_id"], $details_row["po_detail_item_id"]);
              if ($match !== false) {
                $mat_cost = Purch_GRN::update_average_material_cost(
                  $grn["creditor_id"], $details_row["stock_id"], $match["unit_price"], -$details_row["quantity"], Dates::_sqlToDate($match['tran_date']), $match['tran_date'] !== $trans['tran_date']
                );
              } else {
                $mat_cost = Purch_GRN::update_average_material_cost(
                  $grn["creditor_id"], $details_row["stock_id"], $details_row["FullUnitPrice"], -$details_row["quantity"], $old_date, $old[1] !== $trans['tran_date']
                );
              }
              $sql
                = "UPDATE purch_order_details
                 SET quantity_ordered = quantity_ordered + " . -$details_row["quantity"] . ", ";
              if ($match !== false) {
                $sql .= "act_price=" . $match['unit_price'] . ", ";
              }
              $sql .= "quantity_received = quantity_received + " . -$details_row["quantity"] . "
                  WHERE po_detail_item = " . $details_row["po_detail_item_id"];
              DB::_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
              $sql = "UPDATE grn_items SET qty_recd=qty_recd+" . -$details_row["quantity"] . "
                        WHERE id=" . $details_row["grn_item_id"];
              DB::_query($sql);
            } else {
              $diff = static::get_diff_in_home_currency(
                $grn["creditor_id"], $old_date, Dates::_sqlToDate($trans['tran_date']), $old[2], $details_row["FullUnitPrice"]
              );
              // Only adjust the avg for the diff
              $mat_cost = Purch_GRN::update_average_material_cost(null, $details_row["stock_id"], $diff, -$details_row["quantity"], $old_date, true);
            }
            $deliveries = Item::get_deliveries_between($details_row["stock_id"], $old_date, $date_);
            if ($deliveries[0] != 0) // have deliveries been done during the period?
            {
              Inv_Transfer::update_pid(ST_CUSTDELIVERY, $details_row["stock_id"], $old_date, $date_, 0, $mat_cost);
            }
            Inv_Transfer::update_pid(ST_SUPPRECEIVE, $details_row["stock_id"], $old_date, $old_date, $grn['creditor_id'], $mat_cost);
          }
        }
      }
      if ($type == ST_SUPPCREDIT) // void the credits in stock moves
      {
        Inv_Movement::void($type, $type_no);
      }
      Purch_Line::void_for_invoice($type, $type_no);
      GL_Trans::void_tax_details($type, $type_no);
      DB::_commit();
    }
    /**
     * @static
     *
     * @param $creditor_trans
     */
    public static function copy_from_trans($creditor_trans) {
      $_POST['Comments']           = $creditor_trans->Comments;
      $_POST['tran_date']          = $creditor_trans->tran_date;
      $_POST['due_date']           = $creditor_trans->due_date;
      $_POST['supplier_reference'] = $creditor_trans->supplier_reference;
      $_POST['reference']          = $creditor_trans->reference;
      $_POST['creditor_id']        = $creditor_trans->creditor_id;
      $_POST['ChgTax']             = $creditor_trans->tax_correction;
    }
    /**
     * @static
     *
     * @param $creditor_trans
     */
    public static function copy_to_trans($creditor_trans) {
      $creditor_trans->Comments           = Input::_post('Comments');
      $creditor_trans->tran_date          = $_POST['tran_date'];
      $creditor_trans->due_date           = $_POST['due_date'];
      $creditor_trans->supplier_reference = $_POST['supplier_reference'];
      $creditor_trans->reference          = $_POST['reference'];
      $creditor_trans->ov_amount          = 0; /* for starters */
      $creditor_trans->tax_correction     = Input::_post('ChgTax'); /* for starters */
      if (count($creditor_trans->grn_items) > 0) {
        foreach ($creditor_trans->grn_items as $grn) {
          $creditor_trans->ov_amount += Num::_round(($grn->this_quantity_inv * $grn->chg_price * (1 - $grn->discount / 100)), User::_price_dec());
        }
      }
      if (count($creditor_trans->gl_codes) > 0) {
        foreach ($creditor_trans->gl_codes as $gl_line) {
          ////////// 2009-08-18 Joe Hunt
          if (!Tax::is_account($gl_line->gl_code)) {
            $creditor_trans->ov_amount += $gl_line->amount;
          }
        }
      }
    }
    /**
     * @static
     *
     * @param $creditor_trans
     */
    public static function header($creditor_trans) {
      // if vars have been lost, recopy
      if (!isset($_POST['tran_date'])) {
        Purch_Invoice::copy_from_trans($creditor_trans);
      }
      Table::startOuter('standard width60');
      Ajax::_start_div('summary');
      Table::start();
      echo '<tr>';
      Table::header(_("Invoice #:"));
      if ($creditor_trans->is_invoice && isset($_POST['invoice_no'])) {
        Cell::labelled(
          null, $_POST['invoice_no'] . Forms::hidden('invoice_no', $_POST['invoice_no'], false) . Forms::hidden('supplier_reference', $_POST['invoice_no'], false)
        );
      } else {
        Forms::textCells(null, 'supplier_reference', $_POST['supplier_reference'], 20, 20);
      }
      echo '</tr>';
      echo '<tr>';
      Table::header(_("Date") . ":");
      Forms::dateCells(null, 'tran_date', '', true, 0, 0, 0, "", true);
      if (isset($_POST['_tran_date_changed'])) {
        Ajax::_activate('_ex_rate');
        $creditor_trans->tran_date = $_POST['tran_date'];
        echo '</tr>';
        echo '<tr>';
        Creditor_Trans::get_duedate_from_terms($creditor_trans);
        $_POST['due_date'] = $creditor_trans->due_date;
        Ajax::_activate('due_date');
      }
      echo '</tr>';
      echo '<tr>';
      Table::header(_("Due Date") . ":");
      Forms::dateCells(null, 'due_date');
      echo '</tr>';
      Table::end();
      Ajax::_end_div();
      Table::start();
      echo '<tr>';
      if (Input::_post(FORM_CONTROL) == 'creditor') {
        $creditor_trans->supplier_name = $_POST['creditor'];
        Ajax::i()->activate("_page_body");
      }
      if (isset($_POST['invoice_no'])) {
        $trans                = Creditor_Trans::get($_POST['invoice_no'], ST_SUPPINVOICE);
        $_POST['creditor_id'] = $trans['creditor_id'];
        $supp                 = $trans['supplier_name'] . " - " . $trans['SupplierCurrCode'];
        Cell::labelled('Supplier', $supp . Forms::hidden('creditor_id', $_POST['creditor_id'], false));
      } else {
        Creditor::newselect($creditor_trans->creditor_id, ['row' => false]);
      }
      if ($creditor_trans->creditor_id != $_POST['creditor_id']) {
        // supplier has changed
        // delete all the order items - drastic but necessary because of
        // change of currency, etc
        $creditor_trans->clear_items();
        Purch_Invoice::get_supplier_to_trans($creditor_trans, $_POST['creditor_id']);
        Purch_Invoice::copy_from_trans($creditor_trans);
      }
      if ($creditor_trans->is_invoice) {
        Forms::refCells("INVOICE #: ", 'reference', '', Ref::get_next(ST_SUPPINVOICE), 'colspan=2');
      } else {
        Forms::refCells("CREDIT #: ", 'reference', '', Ref::get_next(ST_SUPPCREDIT), 'colspan=2');
      }
      echo '<tr>';
      if (!empty($creditor_trans->terms_description)) {
        Cell::labelled(_("Terms:"), $creditor_trans->terms_description);
      }
      $supplier_currency = Bank_Currency::for_creditor($creditor_trans->creditor_id);
      $company_currency  = Bank_Currency::for_company();
      Session::_setGlobal('creditor_id', $_POST['creditor_id']);
      if ($supplier_currency && $supplier_currency != $company_currency) {
        GL_ExchangeRate::display($supplier_currency, $company_currency, $_POST['tran_date']);
        if (!empty($creditor_trans->tax_description)) {
          Cell::labelled(_("Tax Group:"), $creditor_trans->tax_description);
        }
        Cell::labelled(_("Supplier's Currency:"), "<span class='bold'>" . $supplier_currency . "</span>");
        echo '</tr>';
        echo '</tr>';
      }
      Table::end();
      Table::endOuter(1);
    }
    /**
     * @static
     *
     * @param Creditor_Trans $creditor_trans
     */
    public static function totals($creditor_trans) {
      Purch_Invoice::copy_to_trans($creditor_trans);
      $dim     = DB_Company::_get_pref('use_dimension');
      $colspan = ($dim == 2 ? 7 : ($dim == 1 ? 6 : 5));
      Table::start('standard width90');
      Table::label(_("Sub-total:"), Num::_priceFormat($creditor_trans->ov_amount), "colspan=$colspan class='alignright bold'", "class='alignright'");
      $taxes     = $creditor_trans->get_taxes($creditor_trans->tax_group_id);
      $tax_total = Tax::edit_items($taxes, $colspan, 0, null, true); // tax_included==0 (we are the company)
      Cell::label(_("Total Correction"), "colspan=$colspan class='alignright' style='width:90%'");
      Forms::amountCellsSmall(null, 'ChgTotal', Num::_priceFormat(Input::_post('ChgTotal'), 2));
      $total = $creditor_trans->ov_amount + $tax_total + Input::_post('ChgTotal');
      if ($creditor_trans->is_invoice) {
        Table::label(
          _("Invoice Total:"), Num::_priceFormat($total), "colspan=$colspan class='alignright bold'", "class='alignright' id='invoiceTotal' data-total=" . $total . " class='bold'"
        );
      } else {
        Table::label(
          _("Credit Note Total"), Num::_priceFormat($total), "colspan=$colspan class='bold red alignright'", " class='alignright nowrap' id='invoiceTotal' data-total=" . $total . " class='bold red'"
        );
      }
      Table::end(1);
      Table::start('standard');
      Forms::textareaRow(_("Memo:"), "Comments", null, 50, 3);
      Table::end(1);
    }
  }
