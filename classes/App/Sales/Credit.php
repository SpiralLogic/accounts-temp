<?php
  use ADV\App\Forms;
  use ADV\Core\Num;
  use ADV\Core\Session;
  use ADV\App\Orders;
  use ADV\Core\JS;
  use ADV\App\Validation;
  use ADV\App\Display;
  use ADV\Core\Input\Input;
  use ADV\App\Dimensions;
  use ADV\Core\Table;
  use ADV\App\Debtor\Debtor;
  use ADV\App\Item\Item;
  use ADV\App\Ref;
  use ADV\App\Tax\Tax;
  use ADV\App\Bank\Bank;
  use ADV\App\User;
  use ADV\Core\DB\DB;
  use ADV\Core\Ajax;
  use ADV\Core\Cell;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /**
   * if ($writeoff_acc==0) return goods into $order->location
   * if src_docs!=0 => credit invoice else credit note
   */
  class Sales_Credit
  {
    /**
     * @static
     *
     * @param Sales_Order $credit_note
     * @param             $write_off_acc
     *
     * @return int
     */
    public static function add($credit_note, $write_off_acc) {
      $credit_invoice = is_array($credit_note->src_docs) ? reset(array_keys($credit_note->src_docs)) : $credit_note->src_docs;
      $credit_date    = $credit_note->document_date;
      $tax_group_id   = $credit_note->tax_group_id;
      $trans_no       = $credit_note->trans_no;
      if (is_array($trans_no)) {
        $trans_no = key($trans_no);
      }
      $credit_type = $write_off_acc == 0 ? 'Return' : 'WriteOff';
      DB::_begin();
      $company_data      = DB_Company::_get_prefs();
      $branch_data       = Sales_Branch::get_accounts($credit_note->Branch);
      $credit_note_total = $credit_note->get_items_total_dispatch();
      $freight_tax       = $credit_note->get_shipping_tax();
      $taxes             = $credit_note->get_taxes();
      $tax_total         = 0;
      foreach ($taxes as $taxitem) {
        $taxitem['Value'] = Num::_round($taxitem['Value'], User::_price_dec());
        $tax_total += $taxitem['Value'];
      }
      if ($credit_note->tax_included == 0) {
        $items_added_tax   = $tax_total - $freight_tax;
        $freight_added_tax = $freight_tax;
      } else {
        $items_added_tax   = 0;
        $freight_added_tax = 0;
      }
      // 2006-06-14. If the Customer Branch AR Account is set to a Bank Account,
      // the transaction will be settled at once.
      if (Bank_Account::is($branch_data['receivables_account'])) {
        $alloc = $credit_note_total + $items_added_tax + $credit_note->freight_cost + $freight_added_tax;
      } else {
        $alloc = 0;
      }
      //	$sales_order=$invoice->order_no;	//?
      // if (is_array($sales_order)) $sales_order = $sales_order[0]; //?
      if (!$credit_note->order_no) {
        $credit_note->order_no = 0;
      }
      /*Now insert the Credit Note into the debtor_trans table with the allocations as calculated above*/
      // all amounts in debtor's currency
      $credit_no = Debtor_Trans::write(
        ST_CUSTCREDIT, $trans_no, $credit_note->debtor_id, $credit_note->Branch, $credit_date, $credit_note->reference, $credit_note_total, 0, $items_added_tax, $credit_note->freight_cost, $freight_added_tax, $credit_note->sales_type, $credit_note->order_no, $credit_invoice ? :
          0, $credit_note->ship_via, null, $alloc, 0, $credit_note->dimension_id, $credit_note->dimension2_id
      );
      // 2008-06-14 extra $alloc, 2008-11-12 dimension_id Joe Hunt
      if ($trans_no == 0) {
        $credit_note->trans_no = array($credit_no => 0);
        Debtor_Trans::set_parent($credit_note);
      } else {
        DB_Comments::delete(ST_CUSTCREDIT, $credit_no);
        Sales_Allocation::void(ST_CUSTCREDIT, $credit_no, $credit_date);
        GL_Trans::void(ST_CUSTCREDIT, $credit_no, true);
        Inv_Movement::void(ST_CUSTCREDIT, $credit_no);
        GL_Trans::void_tax_details(ST_CUSTCREDIT, $credit_no);
      }
      if ($credit_invoice) {
        $invoice_alloc_balance = Sales_Allocation::get_balance(ST_SALESINVOICE, $credit_invoice);
        Debtor_Trans::update_version(Debtor_Trans::get_parent_type(ST_CUSTCREDIT), $credit_note->src_docs);
        if ($invoice_alloc_balance > 0) { //the invoice is not already fully allocated
          $total           = $credit_note_total + $credit_note->freight_cost + $items_added_tax + $freight_added_tax;
          $allocate_amount = ($invoice_alloc_balance > $total) ? $total : $invoice_alloc_balance;
          /*Now insert the allocation record if > 0 */
          if ($allocate_amount != 0) {
            Sales_Allocation::update(ST_SALESINVOICE, $credit_invoice, $allocate_amount);
            Sales_Allocation::update(ST_CUSTCREDIT, $credit_no, $allocate_amount); // ***
            Sales_Allocation::add($allocate_amount, ST_CUSTCREDIT, $credit_no, ST_SALESINVOICE, $credit_invoice);
            // Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////
            Bank::exchange_variation(ST_CUSTCREDIT, $credit_no, ST_SALESINVOICE, $credit_invoice, $credit_date, $allocate_amount, PT_CUSTOMER);
            ///////////////////////////////////////////////////////////////////////////
          }
        }
      }
      $total = 0;
      foreach ($credit_note->line_items as $credit_line) {
        if ($credit_invoice && $credit_line->qty_dispatched != $credit_line->qty_old) {
          Sales_Order::update_parent_line(11, $credit_line->src_id, ($credit_line->qty_dispatched - $credit_line->qty_old));
        }
        $line_taxfree_price = Tax::tax_free_price($credit_line->stock_id, $credit_line->price, 0, $credit_note->tax_included, $credit_note->tax_group_array);
        $line_tax           = Tax::full_price_for_item(
          $credit_line->stock_id, $credit_line->price, 0, $credit_note->tax_included, $credit_note->tax_group_array
        ) - $line_taxfree_price;
        Debtor_TransDetail::add(
          ST_CUSTCREDIT, $credit_no, $credit_line->stock_id, $credit_line->description, $credit_line->qty_dispatched, $credit_line->line_price(), $line_tax, $credit_line->discount_percent, $credit_line->standard_cost, $trans_no == 0 ?
            0 : $credit_line->id
        );
        Sales_Credit::add_movements($credit_note, $credit_line, $credit_type, $line_taxfree_price + $line_tax, $credit_invoice);
        $total += Sales_Credit::add_gl_costs($credit_note, $credit_line, $credit_no, $credit_date, $credit_type, $write_off_acc, $branch_data);
      } /*end of credit_line loop */
      /*Post credit note transaction to GL credit debtors,
debit freight re-charged and debit sales */
      if (($credit_note_total + $credit_note->freight_cost) != 0) {
        $total += Debtor_TransDetail::add_gl_trans(
          ST_CUSTCREDIT, $credit_no, $credit_date, $branch_data["receivables_account"], 0, 0, -($credit_note_total + $credit_note->freight_cost + $items_added_tax + $freight_added_tax), $credit_note->debtor_id, "The total debtor GL posting for the credit note could not be inserted"
        );
      }
      if ($credit_note->freight_cost != 0) {
        $total += Debtor_TransDetail::add_gl_trans(
          ST_CUSTCREDIT, $credit_no, $credit_date, $company_data["freight_act"], 0, 0, $credit_note->get_tax_free_shipping(), $credit_note->debtor_id, "The freight GL posting for this credit note could not be inserted"
        );
      }
      foreach ($taxes as $taxitem) {
        if ($taxitem['Net'] != 0) {
          $ex_rate = Bank_Currency::exchange_rate_from_home(Bank_Currency::for_debtor($credit_note->debtor_id), $credit_note->document_date);
          GL_Trans::add_tax_details(
            ST_CUSTCREDIT, $credit_no, $taxitem['tax_type_id'], $taxitem['rate'], $credit_note->tax_included, $taxitem['Value'], $taxitem['Net'], $ex_rate, $credit_note->document_date, $credit_note->reference
          );
          $total += Debtor_TransDetail::add_gl_trans(
            ST_CUSTCREDIT, $credit_no, $credit_date, $taxitem['sales_gl_code'], 0, 0, $taxitem['Value'], $credit_note->debtor_id, "A tax GL posting for this credit note could not be inserted"
          );
        }
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance(ST_CUSTCREDIT, $credit_no, $credit_date, -$total, PT_CUSTOMER, $credit_note->debtor_id);
      DB_Comments::add(ST_CUSTCREDIT, $credit_no, $credit_date, $credit_note->Comments);
      if ($trans_no == 0) {
        Ref::save(ST_CUSTCREDIT, $credit_note->reference);
      }
      DB::_commit();
      return $credit_no;
    }
    /**
     * @static
     *
     * @param     $credit_note
     * @param     $credit_line
     * @param     $credit_type
     * @param     $price
     * @param int $credited_invoice
     * Insert a stock movement coming back in to show the credit note and
     * a reversing stock movement to show the write off

     */
    public static function add_movements($credit_note, $credit_line, $credit_type, $price, $credited_invoice = 0) {
      if ($credit_type == "Return") {
        $reference = "Return ";
        if ($credited_invoice) {
          $reference .= "Ex Inv: " . $credited_invoice;
        }
      } elseif ($credit_type == "WriteOff") {
        $reference = "WriteOff ";
        if ($credited_invoice) {
          $reference .= "Ex Inv: " . $credited_invoice;
        }
        Inv_Movement::add_for_debtor(
          ST_CUSTCREDIT, $credit_line->stock_id, key($credit_note->trans_no), $credit_note->location, $credit_note->document_date, $reference, -$credit_line->qty_dispatched, $credit_line->standard_cost, 0, $price, $credit_line->discount_percent
        );
      }
      Inv_Movement::add_for_debtor(
        ST_CUSTCREDIT, $credit_line->stock_id, key($credit_note->trans_no), $credit_note->location, $credit_note->document_date, $credit_note->reference, $credit_line->qty_dispatched, $credit_line->standard_cost, 0, $price, $credit_line->discount_percent
      );
    }
    /**
     * @static
     *
     * @param            $order
     * @param Sales_Line $order_line
     * @param            $credit_no
     * @param            $date_
     * @param            $credit_type
     * @param            $write_off_gl_code
     * @param            $branch_data
     *
     * @return float|int
     */
    public static function add_gl_costs($order, $order_line, $credit_no, $date_, $credit_type, $write_off_gl_code, &$branch_data) {
      $stock_gl_codes = Item::get_gl_code($order_line->stock_id);
      $customer       = Debtor::get($order->debtor_id);
      // 2008-08-01. If there is a Customer Dimension, then override with this,
      // else take the Item Dimension (if any)
      $dim   = ($order->dimension_id != $customer['dimension_id'] ? $order->dimension_id :
        ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_codes["dimension_id"]));
      $dim2  = ($order->dimension2_id != $customer['dimension2_id'] ? $order->dimension2_id :
        ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] : $stock_gl_codes["dimension2_id"]));
      $total = 0;
      /* insert gl_trans to credit stock and debit cost of sales at standard cost*/
      $standard_cost = Item_Price::get_standard_cost($order_line->stock_id);
      if ($standard_cost != 0) {
        /*first the cost of sales entry*/
        $total += GL_Trans::add_std_cost(
          ST_CUSTCREDIT, $credit_no, $date_, $stock_gl_codes["cogs_account"], $dim, $dim2, "", -($standard_cost * $order_line->qty_dispatched), PT_CUSTOMER, $order->debtor_id, "The cost of sales GL posting could not be inserted"
        );
        /*now the stock entry*/
        if ($credit_type == "WriteOff") {
          $stock_entry_account = $write_off_gl_code;
        } else {
          $stock_gl_code       = Item::get_gl_code($order_line->stock_id);
          $stock_entry_account = $stock_gl_code["inventory_account"];
        }
        $total += GL_Trans::add_std_cost(
          ST_CUSTCREDIT, $credit_no, $date_, $stock_entry_account, 0, 0, "", ($standard_cost * $order_line->qty_dispatched), PT_CUSTOMER, $order->debtor_id, "The stock side (or write off) of the cost of sales GL posting could not be inserted"
        );
      } /* end of if GL and stock integrated and standard cost !=0 */
      if ($order_line->line_price() != 0) {
        $line_taxfree_price = Tax::tax_free_price($order_line->stock_id, $order_line->price, 0, $order->tax_included, $order->tax_group_array);
        $line_tax           = Tax::full_price_for_item($order_line->stock_id, $order_line->price, 0, $order->tax_included, $order->tax_group_array) - $line_taxfree_price;
        //Post sales transaction to GL credit sales
        // 2008-06-14. If there is a Branch Sales Account, then override with this,
        // else take the Item Sales Account
        if ($branch_data['sales_account'] != "") {
          $sales_account = $branch_data['sales_account'];
        } else {
          $sales_account = $stock_gl_codes['sales_account'];
        }
        $total += Debtor_TransDetail::add_gl_trans(
          ST_CUSTCREDIT, $credit_no, $date_, $sales_account, $dim, $dim2, ($line_taxfree_price * $order_line->qty_dispatched), $order->debtor_id, "The credit note GL posting could not be inserted"
        );
        if ($order_line->discount_percent != 0) {
          $total += Debtor_TransDetail::add_gl_trans(
            ST_CUSTCREDIT, $credit_no, $date_, $branch_data["sales_discount_account"], $dim, $dim2, -($line_taxfree_price * $order_line->qty_dispatched * $order_line->discount_percent), $order->debtor_id, "The credit note discount GL posting could not be inserted"
          );
        } /*end of if discount !=0 */
      } /*if line_price!=0 */
      return $total;
    }
    /**
     * @static
     *
     * @param $order
     *
     * @return mixed|string
     */
    public static function header($order) {
      Table::startOuter('padded width90');
      Table::section(1);
      $customer_error = "";
      $change_prices  = 0;
      if (!isset($_POST['debtor_id']) && Session::_getGlobal('debtor_id')) {
        $_POST['debtor_id'] = Session::_getGlobal('debtor_id');
      }
      Debtor::newselect();
      if ($order->debtor_id != $_POST['debtor_id'] /*|| $order->sales_type != $_POST['sales_type_id']*/) {
        // customer has changed
        Ajax::_activate('branch_id');
        JS::_setFocus('stock_id');
      }
      Debtor_Branch::row(_("Branch:"), $_POST['debtor_id'], 'branch_id', null, false, true, true, true);
      //if (($_SESSION['credit_items']->order_no == 0) ||
      //	($order->debtor_id != $_POST['debtor_id']) ||
      //	($order->Branch != $_POST['branch_id']))
      //	$customer_error = $order->customer_to_order($_POST['debtor_id'], $_POST['branch_id']);
      if (is_object($order) && $order->debtor_id > 0 && ($order->debtor_id != $_POST['debtor_id'] || $order->Branch != $_POST['branch_id'])
      ) {
        $old_order                 = clone($order);
        $customer_error            = $order->customer_to_order($_POST['debtor_id'], $_POST['branch_id']);
        $_POST['location']         = $order->location;
        $_POST['deliver_to']       = $order->deliver_to;
        $_POST['delivery_address'] = $order->delivery_address;
        $_POST['name']             = $order->name;
        $_POST['phone']            = $order->phone;
        Ajax::_activate('location');
        Ajax::_activate('deliver_to');
        Ajax::_activate('name');
        Ajax::_activate('phone');
        Ajax::_activate('delivery_address');
        if ($old_order->customer_currency != $order->customer_currency) {
          $change_prices = 1;
        }
        if ($old_order->sales_type != $order->sales_type) {
          // || $old_order->default_discount!=$order->default_discount
          $_POST['sales_type_id'] = $order->sales_type;
          Ajax::_activate('sales_type_id');
          $change_prices = 1;
        }
        if ($old_order->dimension_id != $order->dimension_id) {
          $_POST['dimension_id'] = $order->dimension_id;
          Ajax::_activate('dimension_id');
        }
        if ($old_order->dimension2_id != $order->dimension2_id) {
          $_POST['dimension2_id'] = $order->dimension2_id;
          Ajax::_activate('dimension2_id');
        }
        unset($old_order);
      }
      Session::_setGlobal('debtor_id', $_POST['debtor_id']);
      if ($order->trans_no == 0) {
        Forms::refRow(_("Reference") . ':', 'ref', _('Reference number unique for this document type'), $order->reference ? : Ref::get_next($order->trans_type), '');
      } else {
        $order->reference = $order->reference ? : Ref::get_next($order->trans_type);
        Table::label(_("Reference") . ':', $order->reference);
      }
      if (!Bank_Currency::is_company($order->customer_currency)) {
        Table::section(2);
        Table::label(_("Customer Currency:"), $order->customer_currency);
        GL_ExchangeRate::display($order->customer_currency, Bank_Currency::for_company(), $_POST['OrderDate']);
      }
      Table::section(3);
      if (!isset($_POST['sales_type_id'])) {
        $_POST['sales_type_id'] = $order->sales_type;
      }
      Sales_Type::row(_("Sales Type"), 'sales_type_id', $_POST['sales_type_id'], true);
      if ($order->sales_type != $_POST['sales_type_id']) {
        $myrow = Sales_Type::get($_POST['sales_type_id']);
        $order->set_sales_type($myrow['id'], $myrow['sales_type'], $myrow['tax_included'], $myrow['factor']);
        Ajax::_activate('sales_type_id');
        $change_prices = 1;
      }
      Sales_UI::shippers_row(_("Shipping Company:"), 'ShipperID', $order->ship_via);
      Table::label(_("Customer Discount:"), ($order->default_discount * 100) . "%");
      Table::section(4);
      if (!isset($_POST['OrderDate']) || $_POST['OrderDate'] == "") {
        $_POST['OrderDate'] = $order->document_date;
      }
      Forms::dateRow(_("Date:"), 'OrderDate', '', $order->trans_no == 0, 0, 0, 0, null, true);
      if (isset($_POST['_OrderDate_changed'])) {
        if (!Bank_Currency::is_company($order->customer_currency) && (DB_Company::_get_base_sales_type() > 0)
        ) {
          $change_prices = 1;
        }
        Ajax::_activate('_ex_rate');
      }
      // 2008-11-12 Joe Hunt added dimensions
      $dim = DB_Company::_get_pref('use_dimension');
      if ($dim > 0) {
        Dimensions::select_row(_("Dimension") . ":", 'dimension_id', null, true, ' ', false, 1, false);
      } else {
        Forms::hidden('dimension_id', 0);
      }
      if ($dim > 1) {
        Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', null, true, ' ', false, 2, false);
      } else {
        Forms::hidden('dimension2_id', 0);
      }
      Table::endOuter(1); // outer table
      if ($change_prices != 0) {
        foreach ($order->line_items as $line) {
          $line->price = Item_Price::get_calculated_price($line->stock_id, $order->customer_currency, $order->sales_type, $order->price_factor, Input::_post('OrderDate'));
          //		$line->discount_percent = $order->default_discount;
        }
        Ajax::_activate('items_table');
      }
      return $customer_error;
    }
    /**
     * @static
     *
     * @param             $title
     * @param Sales_Order $order
     */
    public static function display_items($title, $order) {
      Display::heading($title);
      Ajax::_start_div('items_table');
      Table::start('padded grid width90');
      $th = array(
        _("Item Code"),
        _("Item Description"),
        _("Quantity"),
        _("Unit"),
        _("Price"),
        _("Discount %"),
        _("Total"),
        ''
      );
      if (count($order->line_items)) {
        $th[] = '';
      }
      Table::header($th);
      $subtotal = 0;
      $k        = 0; //row colour counter
      $id       = Forms::findPostPrefix(MODE_EDIT);
      foreach ($order->line_items as $line_no => $line) {
        $line_total = round($line->qty_dispatched * $line->price * (1 - $line->discount_percent), User::_price_dec());
        if ($id != $line_no) {
          Cell::label("<a target='_blank' href='" . ROOT_URL . "inventory/inquiry/stock_status.php?stock_id=" . $line->stock_id . "'>$line->stock_id</a>");
          Cell::label($line->description, ' class="nowrap"');
          Cell::qty($line->qty_dispatched, false, Item::qty_dec($line->stock_id));
          Cell::label($line->units);
          Cell::amount($line->price);
          Cell::percent($line->discount_percent * 100);
          Cell::amount($line_total);
          Forms::buttonEditCell("Edit$line_no", _("Edit"), _('Edit document line'));
          Forms::buttonDeleteCell("Delete$line_no", _('Delete'), _('Remove line from document'));
          echo '</tr>';
        } else {
          Sales_Credit::item_controls($order, $k, $line_no);
        }
        $subtotal += $line_total;
      }
      if ($id == -1) {
        Sales_Credit::item_controls($order, $k);
      }
      Table::foot();
      $colspan           = 6;
      $display_sub_total = Num::_priceFormat($subtotal);
      Table::label(_("Sub-total"), $display_sub_total, "colspan=$colspan class='alignright bold'", "class='alignright'", 2);
      if (!isset($_POST['ChargeFreightCost']) OR ($_POST['ChargeFreightCost'] == "")) {
        $_POST['ChargeFreightCost'] = 0;
      }
      echo '<tr>';
      Cell::label(_("Shipping"), "colspan=$colspan class='alignright bold'");
      Forms::amountCellsSmall(null, 'ChargeFreightCost', Num::_priceFormat(Input::_post('ChargeFreightCost', null, 0)), null, ['$']);
      Cell::label('', 'colspan=2');
      echo '</tr>';
      $taxes         = $order->get_taxes($_POST['ChargeFreightCost']);
      $tax_total     = Tax::edit_items($taxes, $colspan, $order->tax_included, 2);
      $display_total = Num::_priceFormat(($subtotal + $_POST['ChargeFreightCost'] + $tax_total));
      Table::label(_("Credit Note Total"), $display_total, "colspan=$colspan class='alignright bold'", "class='amount'", 2);
      Table::footEnd();
      Table::end();
      Ajax::_end_div();
    }
    /**
     * @static
     *
     * @param $order
     * @param $rowcounter
     * @param $line_no
     */
    public static function item_controls($order, $rowcounter, $line_no = -1) {
      $id = Forms::findPostPrefix(MODE_EDIT);
      if ($line_no != -1 && $line_no == $id) {
        $_POST['stock_id'] = $order->line_items[$id]->stock_id;
        $item_info         = Item::get_edit_info(Input::_post('stock_id'));
        $dec               = $item_info['decimals'];
        $_POST['qty']      = Item::qty_format($order->line_items[$id]->qty_dispatched, $_POST['stock_id']);
        $_POST['price']    = Num::_priceFormat($order->line_items[$id]->price);
        $_POST['Disc']     = Num::_percentFormat(($order->line_items[$id]->discount_percent) * 100);
        $_POST['units']    = $order->line_items[$id]->units;
        Forms::hidden('stock_id', $_POST['stock_id']);
        Cell::label($_POST['stock_id']);
        Cell::label($order->line_items[$id]->description, ' class="nowrap"');
        Ajax::_activate('items_table');
      } else {
        Sales_UI::items_cells(null, 'stock_id', null, false, false, array('description' => $order->line_items[$id]->description));
        if (Forms::isListUpdated('stock_id')) {
          Ajax::_activate('price');
          Ajax::_activate('qty');
          Ajax::_activate('units');
          Ajax::_activate('line_total');
        }
        $item_info      = Item::get_edit_info(Input::_post('stock_id'));
        $dec            = $item_info['decimals'];
        $_POST['qty']   = Num::_format(1, $dec);
        $_POST['units'] = $item_info["units"];
        $_POST['price'] = Num::_priceFormat(
          Item_Price::get_calculated_price(Input::_post('stock_id'), $order->customer_currency, $order->sales_type, $order->price_factor, $order->document_date)
        );
        // default to the customer's discount %
        $_POST['Disc'] = Num::_percentFormat($order->default_discount * 100);
      }
      Forms::qtyCells(null, 'qty', $_POST['qty'], null, null, $dec);
      Cell::label($_POST['units']);
      Forms::amountCells(null, 'price', null);
      Forms::amountCellsSmall(null, 'Disc', Num::_percentFormat(0), null, '%', User::_percent_dec());
      Cell::amount(Validation::input_num('qty') * Validation::input_num('price') * (1 - Validation::input_num('Disc') / 100));
      if ($id != -1) {
        Forms::buttonCell(Orders::UPDATE_ITEM, _("Update"), _('Confirm changes'), ICON_UPDATE);
        Forms::buttonCell('cancelItem', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        Forms::hidden('line_no', $line_no);
        JS::_setFocus('qty');
      } else {
        Forms::submitCells(Orders::ADD_LINE, _("Add Item"), "colspan=2", _('Add new item to document'), true);
      }
      echo '</tr>';
    }
    /**
     * @static
     *
     * @param $credit
     */
    public static function option_controls($credit) {
      echo "<br>";
      if (isset($_POST['_CreditType_update'])) {
        Ajax::_activate('options');
      }
      Ajax::_start_div('options');
      Table::start('standard');
      Sales_Credit::row(_("Credit Note Type"), 'CreditType', null, true);
      if ($_POST['CreditType'] == "Return") {
        /*if the credit note is a return of goods then need to know which location to receive them into */
        if (!isset($_POST['location'])) {
          $_POST['location'] = $credit->location;
        }
        Inv_Location::row(_("Items Returned to Location"), 'location');
      } else {
        /* the goods are to be written off to somewhere */
        GL_UI::all_row(_("Write off the cost of the items to"), 'WriteOffGLCode', null);
      }
      Forms::textareaRow(_("Memo"), "CreditText", null, 51, 3);
      echo "</table>";
      Ajax::_end_div();
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     * @param bool $submit_on_change
     */
    public static function cells($label, $name, $selected = null, $submit_on_change = false) {
      if ($label != null) {
        Cell::label($label);
      }
      echo "<td>\n";
      echo Forms::arraySelect(
        $name, $selected, array(
                               'Return'   => _("Items Returned to Inventory Location"),
                               'WriteOff' => _("Items Written Off")
                          ), array('select_submit' => $submit_on_change)
      );
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     * @param bool $submit_on_change
     */
    public static function row($label, $name, $selected = null, $submit_on_change = false) {
      echo "<tr><td class='label'>$label</td>";
      Sales_Credit::cells(null, $name, $selected, $submit_on_change);
      echo "</tr>\n";
    }
  }
