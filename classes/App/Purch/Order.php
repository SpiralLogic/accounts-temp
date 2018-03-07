<?php
  use ADV\Core\DB\DBDuplicateException;
  use ADV\Core\Num;
  use ADV\App\Forms;
  use ADV\Core\Session;
  use ADV\App\Ref;
  use ADV\App\Validation;
  use ADV\App\Page;
  use ADV\App\Display;
  use ADV\Core\Event;
  use ADV\App\Orders;
  use ADV\App\Dates;
  use ADV\Core\Config;
  use ADV\App\User;
  use ADV\App\Item\Item;
  use ADV\Core\Ajax;
  use ADV\App\Creditor\Creditor;
  use ADV\Core\Table;
  use ADV\Core\Input\Input;
  use ADV\Core\DB\DB;
  use ADV\Core\JS;
  use ADV\Core\Cell;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /* Definition of the purch_order class to hold all the information for a purchase order and delivery
  */
  /** **/
  class Purch_Order
  {
    /** @var */
    public $creditor_id;
    /** @var */
    public $supplier_details;
    /** @var array * */
    public $line_items; /*array of objects of class Sales_Line using the product id as the pointer */
    /** @var */
    public $curr_code;
    /** @var */
    public $requisition_no;
    /** @var */
    public $delivery_address;
    /** @var */
    public $Comments;
    /** @var */
    public $location;
    /** @var */
    public $supplier_name;
    /** @var */
    public $orig_order_date;
    /** @var */
    public $trans_no;
    /** @var int * */
    public $order_no; /*Only used for modification of existing orders otherwise only established when order committed */
    /** @var string * */
    protected $uniqueid;
    /** @var int * */
    public $lines_on_order;
    /** @var string * */
    public $order_id;
    /** @var */
    public $freight;
    /** @var */
    public $salesman;
    /** @var */
    public $reference;
    /** @var int * */
    public $trans_type = ST_PURCHORDER;
    /**
     * @param int  $order_no
     * @param bool $view
     */
    public function __construct($order_no = 0, $view = false) {
      /*Constructor function initialises a new purchase order object */
      $this->line_items     = [];
      $this->lines_on_order = $this->creditor_id = 0;
      $this->set_salesman();
      $this->location        = Config::_get('default.location');
      $this->order_no        = $order_no;
      $this->orig_order_date = Input::_post('OrderDate', null, Dates::_newDocDate());
      if (!Dates::_isDateInFiscalYear($this->orig_order_date)) {
        $this->orig_order_date = Dates::_endFiscalYear();
      }
      $this->read($order_no, $view);
      $this->generateID();
    }
    protected function generateID() {
      $this->uniqueid = uniqid();
      $this->order_id = $this->trans_type . '.' . sha1($this->trans_type . serialize($this->order_no));
    }
    /**
     * @param $line_no
     * @param $stock_id
     * @param $qty
     * @param $item_descr
     * @param $price
     * @param $uom
     * @param $req_del_date
     * @param $qty_inv
     * @param $qty_recd
     * @param $discount
     *
     * @return int
     */
    public function add_to_order($line_no, $stock_id, $qty, $item_descr, $price, $uom, $req_del_date, $qty_inv, $qty_recd, $discount) {
      if ($qty != 0 && isset($qty)) {
        $this->line_items[$line_no] = new Purch_Line($line_no, $stock_id, $item_descr, $qty, $price, $uom, $req_del_date, $qty_inv, $qty_recd, $discount);
        $this->lines_on_order++;
        Return 1;
      }
      Return 0;
    }
    /**
     * @param null $salesman_code
     */
    public function set_salesman($salesman_code = null) {
      if ($salesman_code == null) {
        $salesman_name = User::_i()->name;
        $sql           = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DB::_escape($salesman_name);
        $query         = DB::_query($sql, 'Couldn\'t find current salesman');
        $result        = DB::_fetchAssoc($query);
        if (!empty($result['salesman_code'])) {
          $salesman_code = $result['salesman_code'];
        }
      }
      if ($salesman_code != null) {
        $this->salesman = $salesman_code;
      }
    }
    /**
     * @param        $line_no
     * @param        $qty
     * @param        $price
     * @param        $req_del_date
     * @param string $item_descr
     * @param int    $discount
     */
    public function update_order_item($line_no, $qty, $price, $req_del_date, $item_descr = '', $discount = 0) {
      $this->line_items[$line_no]->quantity = $qty;
      $this->line_items[$line_no]->price    = $price;
      $this->line_items[$line_no]->discount = $discount;
      if (!empty($item_descr)) {
        $this->line_items[$line_no]->description = $item_descr;
      }
      $this->line_items[$line_no]->req_del_date = $req_del_date;
      $this->line_items[$line_no]->price        = $price;
    }
    /**
     * @param $line_no
     */
    public function remove_from_order($line_no) {
      $this->line_items[$line_no]->Deleted = true;
    }
    /**
     * @return bool
     */
    public function order_has_items() {
      if (count($this->line_items) > 0) {
        foreach ($this->line_items as $ordered_items) {
          if ($ordered_items->Deleted == false) {
            return true;
          }
        }
      }
      return false;
    }
    public function clear_items() {
      unset($this->line_items);
      $this->line_items     = [];
      $this->lines_on_order = 0;
      $this->order_no       = 0;
    }
    /**
     * @return int
     */
    public function any_already_received() {
      /* Checks if there have been deliveries or invoiced entered against any of the line items */
      if (count($this->line_items) > 0) {
        foreach ($this->line_items as $ordered_items) {
          if ($ordered_items->qty_received != 0 || $ordered_items->qty_inv != 0) {
            return 1;
          }
        }
      }
      return 0;
    }
    /**
     * @param $line_no
     *
     * @return int
     */
    public function some_already_received($line_no) {
      /* Checks if there have been deliveries or amounts invoiced against a specific line item */
      if (count($this->line_items) > 0) {
        if ($this->line_items[$line_no]->qty_received != 0 || $this->line_items[$line_no]->qty_inv != 0
        ) {
          return 1;
        }
      }
      return 0;
    }
    public function delete() {
      $sql = "DELETE FROM purch_orders WHERE order_no=" . DB::_escape($this->order_no);
      DB::_query($sql, "The order header could not be deleted");
      $sql = "DELETE FROM purch_order_details WHERE order_no =" . DB::_quote($this->order_no);
      DB::_query($sql, "The order detail lines could not be deleted");
      Orders::session_delete($this->order_id);
    }
    /**
     * @return bool
     */
    public function can_receive() {
      if (count($this->line_items) <= 0) {
        Event::error(_("You are not currenty receiving an order."));
        Display::link_params("/purchases/search/orders", _("Select a purchase order to receive goods."));
        Page::footer_exit();
      }
      if (!Dates::_isDate($_POST['DefaultReceivedDate'])) {
        Event::error(_("The entered date is invalid."));
        JS::_setFocus('DefaultReceivedDate');
        return false;
      }
      if (!Validation::post_num('freight', 0)) {
        Event::error(_("The freight entered must be numeric and not less than zero."));
        JS::_setFocus('freight');
        return false;
      }
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        JS::_setFocus('ref');
        return false;
      }
      if (!Ref::is_new($_POST['ref'], ST_SUPPRECEIVE)) {
        $_POST['ref'] = Ref::get_next(ST_SUPPRECEIVE);
      }
      $something_received = 0;
      foreach ($this->line_items as $order_line) {
        if ($order_line->receive_qty > 0) {
          $something_received = 1;
          break;
        }
      }
      // Check whether trying to deliver more items than are recorded on the actual purchase order (+ overreceive allowance)
      $delivery_qty_too_large = 0;
      foreach ($this->line_items as $order_line) {
        if ($order_line->receive_qty + $order_line->qty_received > $order_line->quantity * (1 + (DB_Company::_get_pref('po_over_receive') / 100))) {
          $delivery_qty_too_large = 1;
          break;
        }
      }
      if ($something_received == 0) { /*Then dont bother proceeding cos nothing to do ! */
        Event::error(_("There is nothing to process. Please enter valid quantities greater than zero."));
        return false;
      } elseif ($delivery_qty_too_large == 1) {
        Event::error(
          _(
            "Entered quantities cannot be greater than the quantity entered on the purchase order including the allowed over-receive percentage"
          ) . " (" . DB_Company::_get_pref('po_over_receive') . "%).<br>" . _("Modify the ordered items on the purchase order if you wish to increase the quantities.")
        );
        return false;
      }
      return true;
    }
    /**
     * @return bool
     */
    public function has_changed() {
      /*Now need to check that the order details are the same as they were when they were read into the Items array. If they've changed then someone else must have altered them */
      // Sherifoz 22.06.03 Compare against COMPLETED items only !!
      // Otherwise if you try to fullfill item quantities separately will give error.
      $sql
               = "SELECT item_code, quantity_ordered, quantity_received, qty_invoiced
              FROM purch_order_details
              WHERE order_no=" . DB::_escape($this->order_no) . " ORDER BY po_detail_item";
      $result  = DB::_query($sql, "could not query purch order details");
      $line_no = 1;
      while ($myrow = DB::_fetch($result)) {
        $ln_item = $this->line_items[$line_no];
        // only compare against items that are outstanding
        $qty_outstanding = $ln_item->quantity - $ln_item->qty_received;
        if ($qty_outstanding > 0) {
          if ($ln_item->qty_inv != $myrow["qty_invoiced"] || $ln_item->stock_id != $myrow["item_code"] || $ln_item->quantity != $myrow["quantity_ordered"] || $ln_item->qty_received != $myrow["quantity_received"]
          ) {
            return true;
          }
        }
        $line_no++;
      } /*loop through all line items of the order to ensure none have been invoiced */
      return false;
    }
    /**
     * @return int
     */
    public function add() {
      DB::_begin();
      /*Insert to purchase order header record */
      if (!$this->order_no) {
        if (!Ref::is_new($this->reference, ST_PURCHORDER)) {
          $this->reference = Ref::get_next(ST_PURCHORDER);
        }
      }
      try {
        $sql = "INSERT INTO purch_orders (creditor_id, Comments, ord_date, reference, requisition_no, into_stock_location, delivery_address, freight, salesman) VALUES(";
        $sql .= DB::_escape($this->creditor_id) . "," . DB::_escape($this->Comments) . ",'" . Dates::_dateToSql($this->orig_order_date) . "', " . DB::_escape(
          $this->reference
        ) . ", " . DB::_escape($this->requisition_no) . ", " . DB::_escape($this->location) . ", " . DB::_escape($this->delivery_address) . ", " . DB::_escape(
          $this->freight
        ) . ", " . DB::_escape($this->salesman) . ")";
        DB::_query($sql, "The purchase order header record could not be inserted.");
      } catch (DBDuplicateException $e) {
        Event::error('Purchase order could not be added: A purchase order already exists with this number!');
        return false;
      }
      /*Get the auto increment value of the order number created from the sql above */
      $this->order_no = DB::_insertId();
      /*Insert the purchase order detail records */
      foreach ($this->line_items as $po_line) {
        if ($po_line->Deleted == false) {
          $sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
          $sql .= $this->order_no . ", " . DB::_escape($po_line->stock_id) . "," . DB::_escape($po_line->description) . ",'" . Dates::_dateToSql(
            $po_line->req_del_date
          ) . "'," . DB::_escape($po_line->price) . ", " . DB::_escape($po_line->quantity) . ", " . DB::_escape($po_line->discount) . ")";
          DB::_query($sql, "One of the purchase order detail records could not be inserted");
        }
      }
      Ref::save(ST_PURCHORDER, $this->reference);
      DB_Comments::add(ST_PURCHORDER, $this->order_no, $this->orig_order_date, $this->Comments);
      DB_AuditTrail::add(ST_PURCHORDER, $this->order_no, $this->orig_order_date);
      DB::_commit();
      Orders::session_delete($this->order_id);
      return $this->order_no;
    }
    /**
     * @return int
     */
    public function update() {
      DB::_begin();
      /*Update the purchase order header with any changes */
      $sql = "UPDATE purch_orders SET Comments=" . DB::_escape($this->Comments) . ",
            requisition_no= " . DB::_escape($this->requisition_no) . ",
            into_stock_location=" . DB::_escape($this->location) . ",
            ord_date='" . Dates::_dateToSql($this->orig_order_date) . "',
            delivery_address=" . DB::_escape($this->delivery_address) . ",
            freight=" . DB::_escape($this->freight) . ",
            salesman=" . DB::_escape($this->salesman);
      $sql .= " WHERE order_no = " . $this->order_no;
      DB::_query($sql, "The purchase order could not be updated");
      /*Now Update the purchase order detail records */
      foreach ($this->line_items as $po_line) {
        if ($po_line->Deleted == true) {
          // Sherifoz 21.06.03 Handle deleting existing lines
          if (!empty($po_line->po_detail_rec)) {
            $sql = "DELETE FROM purch_order_details WHERE po_detail_item=" . DB::_quote($po_line->po_detail_rec);
          }
        } else {
          if (!$po_line->po_detail_rec) {
            // Sherifoz 21.06.03 Handle adding new lines vs. updating. if no key(po_detail_rec) then it's a new line
            $sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
            $sql .= $this->order_no . "," . DB::_quote($po_line->stock_id) . "," . DB::_quote($po_line->description) . ",'" . Dates::_dateToSql(
              $po_line->req_del_date
            ) . "'," . DB::_quote($po_line->price) . ", " . DB::_quote($po_line->quantity) . ", " . DB::_quote($po_line->discount) . ")";
          } else {
            $sql = "UPDATE purch_order_details SET item_code=" . DB::_quote($po_line->stock_id) . ",
                    description =" . DB::_quote($po_line->description) . ",
                    delivery_date ='" . Dates::_dateToSql($po_line->req_del_date) . "',
                    unit_price=" . DB::_quote($po_line->price) . ",
                    quantity_ordered=" . DB::_quote($po_line->quantity) . ",
                    discount=" . DB::_quote($po_line->discount) . "
                    WHERE po_detail_item=" . DB::_quote($po_line->po_detail_rec);
          }
        }
        DB::_query($sql, "One of the purchase order detail records could not be updated");
      }
      DB_Comments::update(ST_PURCHORDER, $this->order_no, $this->orig_order_date, $this->Comments);
      DB::_commit();
      Orders::session_delete($this->order_id);
      return $this->order_no;
    }
    /**
     * @param $order_no
     *
     * @return bool
     */
    public function get_header($order_no) {
      $sql
              = "SELECT purch_orders.*, suppliers.name,
             suppliers.curr_code, locations.location_name
            FROM purch_orders, suppliers, locations
            WHERE purch_orders.creditor_id = suppliers.creditor_id
            AND locations.loc_code = into_stock_location
            AND purch_orders.order_no = " . DB::_quote($order_no);
      $result = DB::_query($sql, "The order cannot be retrieved");
      if (DB::_numRows($result) == 1) {
        $myrow                  = DB::_fetch($result);
        $this->order_no         = $order_no;
        $this->creditor_id      = $myrow["creditor_id"];
        $this->supplier_name    = $myrow["name"];
        $this->curr_code        = $myrow["curr_code"];
        $this->orig_order_date  = Dates::_sqlToDate($myrow["ord_date"]);
        $this->Comments         = $myrow["comments"];
        $this->location         = $myrow["into_stock_location"];
        $this->requisition_no   = $myrow["requisition_no"];
        $this->reference        = $myrow["reference"];
        $this->delivery_address = $myrow["delivery_address"];
        $this->freight          = $myrow["freight"];
        $this->salesman         = $myrow['salesman'];
        return true;
      } elseif (DB::_numRows($result) > 1) {
        Event::error("FATAL : duplicate purchase order found", "", true);
      }
      return false;
    }
    /**
     * @param      $order_no
     * @param bool $view
     */
    public function get_items($order_no, $view = false) {
      /*now populate the line po array with the purchase order details records */
      $sql
        = "SELECT purch_order_details.*, units
            FROM purch_order_details
            LEFT JOIN stock_master
            ON purch_order_details.item_code=stock_master.stock_id
            WHERE order_no =" . DB::_quote($order_no);
      if ($view) {
        $sql .= " AND (purch_order_details.quantity_ordered > purch_order_details.quantity_received) ";
      }
      $sql .= " ORDER BY po_detail_item";
      $result = DB::_query($sql, "The lines on the purchase order cannot be retrieved");
      if (DB::_numRows($result) > 0) {
        while ($myrow = DB::_fetch($result)) {
          $data = static::get_data($this->creditor_id, $myrow['item_code']);
          if ($data !== false) {
            if ($data['supplier_description'] != "") {
              $myrow['supplier_description'] = $data['supplier_description'];
            }
            if ($data['suppliers_uom'] != "") {
              $myrow['units'] = $data['suppliers_uom'];
            }
          }
          if (is_null($myrow["units"])) {
            $units = "";
          } else {
            $units = $myrow["units"];
          }
          if ($this->add_to_order(
            $this->lines_on_order + 1, $myrow["item_code"], $myrow["quantity_ordered"], $myrow["description"], $myrow["unit_price"], $units, Dates::_sqlToDate($myrow["delivery_date"]), $myrow["qty_invoiced"], $myrow["quantity_received"], $myrow["discount"]
          )
          ) {
            $this->line_items[$this->lines_on_order]->po_detail_rec = $myrow["po_detail_item"];
            $this->line_items[$this->lines_on_order]->standard_cost = $myrow["std_cost_unit"]; /*Needed for receiving goods and GL interface */
          }
        } /* line po from purchase order details */
      } //end of checks on returned data set
    }
    /**
     * @param      $order_no
     * @param bool $view
     */
    public function read($order_no, $view = false) {
      $result = $this->get_header($order_no);
      if ($result) {
        $this->get_items($order_no, $view);
      }
    }
    /**
     * @param $date_
     *
     * @return string
     */
    public function add_freight($date_) {
      $sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
      $sql .= $this->order_no . "," . DB::_escape('freight') . "," . DB::_escape('Freight Charges') . ",'" . Dates::_dateToSql($date_) . "'," . DB::_escape(
        $this->freight
      ) . ", " . DB::_escape(1) . ", " . DB::_escape(0) . ")";
      DB::_query($sql, "One of the purchase order detail records could not be updated");
      return DB::_insertId();
    }
    /**
     * @param $creditor_id
     */
    public function supplier_to_order($creditor_id) {
      $sql
                              = "SELECT * FROM suppliers
            WHERE creditor_id = '$creditor_id'";
      $result                 = DB::_query($sql, "The supplier details could not be retreived");
      $myrow                  = DB::_fetchAssoc($result);
      $this->supplier_details = $myrow;
      $this->curr_code        = $_POST['curr_code'] = $myrow["curr_code"];
      $this->supplier_name    = $_POST['supplier_name'] = $myrow["name"];
      $this->creditor_id      = $_POST['creditor_id'] = $creditor_id;
    }
    /*
    Check if the order was not destroyed during opening the edition page in
    another browser tab.
    */
    /**
     * @static
     *
     * @param $order
     *
     * @return bool|Purch_Order|Sales_Order
     */
    public static function check_edit_conflicts($order) {
      if (!isset($_POST['order_id'])) {
        $_POST['order_id'] = $order->order_id;
      }
      $session_order = Orders::session_get();
      if ($session_order && $session_order->uniqueid != $order->uniqueid) {
        if (!$session_order->trans_no && count($session_order->line_items) > 0) {
          Event::warning(
            _(
              'You were in the middle of creating a new order, this order has been continued. If you would like to start a completely new order, push the cancel changes button at the bottom of the page'
            )
          );
        } elseif ($session_order->trans_no) {
          Event::warning(_('You were previously editing this order in another tab, those changes have been applied to this tab'));
        }
        return $session_order;
      }
      return $order ? : false;
    }
    public function finish() {
      if (is_object($this) && Orders::session_exists($this)) {
        Orders::session_delete($this->order_id);
      }
    }
    public function header() {
      $editable = ($this->order_no == 0);
      Table::start('standard width90');
      echo '<tr>';
      $show_currencies = ($this->curr_code == Bank_Currency::for_company() && !Bank_Currency::is_company($this->curr_code)) ? 1 : 2;
      if ($editable) {
        if (!Input::_post('creditor_id')) {
          $_POST['creditor_id'] = Session::_getGlobal('creditor_id');
        }
        Creditor::newselect(
          null, [
                'cell_params' => ['colspan' => ($show_currencies + 1), 'rowspan' => $show_currencies],
                'rowspan'     => $show_currencies,
                'row'         => false,
                'cell_class'  => 'label'
                ]
        );
        if (Input::_post(FORM_CONTROL) == 'customer') {
          $this->supplier_to_order($_POST['creditor_id']);
          // customer has changed
          Ajax::_activate('_page_body');
        }
      } else {
        if (isset($_POST['creditor_id'])) {
          $this->supplier_to_order($_POST['creditor_id']);
        }
        Forms::hidden('creditor_id', $this->creditor_id);
        Cell::labelled(_("Supplier:"), $this->supplier_name, 'rowspan=2 ', ' colspan=' . ($show_currencies + 2) . ' rowspan=2');
      }
      if ($this->creditor_id != Input::_post('creditor_id', null, -1)) {
        $old_supp = $this->creditor_id;
        $this->supplier_to_order($_POST['creditor_id']);
        // supplier default price update
        foreach ($this->line_items as $line) {
          $line->price    = Item_Price::get_purchase($this->creditor_id, $line->stock_id);
          $line->quantity = $line->quantity / Creditor_Trans::get_conversion_factor($old_supp, $line->stock_id) * Creditor_Trans::get_conversion_factor(
            $this->creditor_id, $line->stock_id
          );
        }
        Ajax::_activate('items_table');
      }
      Session::_setGlobal('creditor_id', $_POST['creditor_id']);
      echo "<td class='label'><label for=\"location\">" . _('Receive Into:') . "</label></td>";
      echo "<td colspan=2>";
      echo Inv_Location::select('location', null, false, true);
      echo "</td>\n";
      echo '</tr>';
      echo '<tr>';
      if ($show_currencies == 1) {
        Cell::labelled(_("Supplier Currency:"), $this->curr_code);
        GL_ExchangeRate::display($this->curr_code, Bank_Currency::for_company(), $_POST['OrderDate']);
      }
      if (!isset($_POST['location']) || $_POST['location'] == "" || isset($_POST['_location_update']) || !isset($_POST['delivery_address']) || $_POST['delivery_address'] == "") {
        $sql    = "SELECT delivery_address, phone FROM locations WHERE loc_code='" . $_POST['location'] . "'";
        $result = DB::_query($sql, "could not get location info");
        if (DB::_numRows($result) == 1) {
          $loc_row                   = DB::_fetch($result);
          $_POST['delivery_address'] = $loc_row["delivery_address"];
          Ajax::_activate('delivery_address');
          $this->location         = $_POST['location'];
          $this->delivery_address = $_POST['delivery_address'];
        } else { /* The default location of the user is crook */
          Event::error(
            _("The default stock location set up for this user is not a currently defined stock location. Your system administrator needs to amend your user record.")
          );
        }
      }
      Forms::textareaCells(
        null, 'delivery_address', $_POST['delivery_address'], 'width95', 4, null, 'colspan=' . (5 - $show_currencies) . ' rowspan=' . (5 - $show_currencies)
      );
      echo '</tr>';
      echo '<tr>';
      if ($editable) {
        Forms::refCells(_("Purchase Order #:"), 'ref', '', Ref::get_next(ST_PURCHORDER));
      } else {
        Forms::hidden('ref', $this->reference);
        Cell::labelled(_("Purchase Order #:"), $this->reference);
      }
      Sales_UI::persons_cells(_("Sales Person:"), 'salesman', $this->salesman);
      if (isset($_POST['_OrderDate_changed'])) {
        Ajax::_activate('_ex_rate');
      }
      echo '</tr>';
      echo '<tr>';
      Forms::textCells(_("Supplier's Order #:"), 'Requisition', null, 'small', 15);
      Forms::dateCells(_("Order Date:"), 'OrderDate', '', true, 0, 0, 0, null, true);
      echo '</tr>';
      Table::end(); // outer table
    }
    /**
     * @param bool $editable
     */
    public function display_items($editable = true) {
      Display::heading(_("Order Items"));
      Ajax::_start_div('items_table');
      Table::start('padded grid width90');
      $th = array(
        _("Item Code"),
        _("Description"),
        _("Quantity"),
        _("Received"),
        _("Unit"),
        _("Required Date"),
        _("Price"),
        _('Discount %'),
        _("Total"),
        ""
      );
      if (count($this->line_items)) {
        $th[] = '';
      }
      Table::header($th);
      $id    = Forms::findPostPrefix(MODE_EDIT);
      $total = 0;
      if (!$this->line_items) {
        //	Event::warning('There are no line items on this Purchase Order');
      } else {
        foreach ($this->line_items as $line_no => $po_line) {
          if ($po_line->Deleted == false) {
            $line_total = Num::_round($po_line->quantity * $po_line->price * (1 - $po_line->discount), User::_price_dec());
            if (!$editable || ($id != $line_no)) {
              Cell::label($po_line->stock_id, " class='stock' data-stock_id='{$po_line->stock_id}'");
              Cell::label($po_line->description);
              Cell::qty($po_line->quantity, false, Item::qty_dec($po_line->stock_id));
              Cell::qty($po_line->qty_received, false, Item::qty_dec($po_line->stock_id));
              Cell::label($po_line->units);
              Cell::label($po_line->req_del_date);
              Cell::amount($po_line->price);
              Cell::percent($po_line->discount * 100);
              Cell::amount($line_total);
              if ($editable) {
                Forms::buttonEditCell("Edit$line_no", _("Edit"), _('Edit document line'));
                Forms::buttonDeleteCell("Delete$line_no", _("Delete"), _('Remove line from document'));
              }
              echo '</tr>';
            } else {
              $this->item_controls($po_line->stock_id);
            }
            $total += $line_total;
          }
        }
      }
      if ($id == -1 && $editable) {
        $this->item_controls();
      }
      Table::foot();
      Forms::SmallAmountRow(
        _("Freight"), 'freight', Num::_priceFormat(Input::_post('freight', null, 0)), "colspan=8 class='bold alignright'", ['$'], null, 3, " class='small alignright'"
      );
      $display_total = Num::_priceFormat($total + Validation::input_num('freight'));
      Table::label(_("Total Excluding Shipping/Tax"), $display_total, "colspan=8 class='bold alignright'", "nowrap class='alignright' _nofreight='$total'", 2);
      Table::footEnd();
      Table::end(1);
      Creditor::addEditDialog();
      Ajax::_end_div();
    }
    /**
     * @param bool $is_self
     * @param bool $editable
     */
    public function summary($is_self = false, $editable = false) {
      Table::start('standard width90');
      echo "<tr class='tablerowhead top'><th colspan=4>";
      Display::heading(_("Purchase Order") . " #" . $_GET['trans_no']);
      echo "</td></tr>";
      echo '<tr>';
      Cell::labelled(_("Supplier"), $this->supplier_name, "class='label'");
      Cell::labelled(_("Reference"), $this->reference, "class='label'");
      if (!Bank_Currency::is_company($this->curr_code)) {
        Cell::labelled(_("Order Currency"), $this->curr_code, "class='label'");
      }
      if (!$is_self) {
        Cell::labelled(_("Purchase Order"), GL_UI::viewTrans(ST_PURCHORDER, $this->order_no), "class='label'");
      }
      echo '</tr>';
      echo '<tr>';
      Cell::labelled(_("Date"), $this->orig_order_date, "class='label'");
      if ($editable) {
        if (!isset($_POST['location'])) {
          $_POST['location'] = $this->location;
        }
        Cell::label(_("Deliver Into Location"), "class='label'");
        Inv_Location::cells(null, 'location', null);
      } else {
        Cell::labelled(_("Deliver Into Location"), Inv_Location::get_name($this->location), "class='label'");
      }
      echo '</tr>';
      if (!$editable) {
        Table::label(_("Delivery Address"), $this->delivery_address, "class='label'", "colspan=9");
      }
      if ($this->Comments != "") {
        Table::label(_("Order Comments"), $this->Comments, "class='label'", "colspan=9");
      }
      Table::end(1);
    }
    /**
     * @param null $stock_id
     */
    public function item_controls($stock_id = null) {
      echo '<tr>';
      $dec2 = null;
      $id   = Forms::findPostPrefix(MODE_EDIT);
      if (($id != -1) && $stock_id != null) {
        Forms::hidden('line_no', $id);
        $_POST['stock_id'] = $this->line_items[$id]->stock_id;
        $dec               = Item::qty_dec($_POST['stock_id']);
        $_POST['qty']      = Item::qty_format($this->line_items[$id]->quantity, $_POST['stock_id'], $dec);
        //$_POST['price'] = Num::_priceFormat($this->line_items[$id]->price);
        $_POST['price']        = Num::_priceDecimal($this->line_items[$id]->price, $dec2);
        $_POST['discount']     = Num::_percentFormat($this->line_items[$id]->discount * 100);
        $_POST['req_del_date'] = $this->line_items[$id]->req_del_date;
        $_POST['description']  = $this->line_items[$id]->description;
        $_POST['units']        = $this->line_items[$id]->units;
        Forms::hidden('stock_id', $_POST['stock_id']);
        Cell::label($_POST['stock_id'], " class='stock' data-stock_id='{$_POST['stock_id']}'");
        Forms::textareaCells(null, 'description', null, 50, 5);
        Ajax::_activate('items_table');
        $qty_rcvd = $this->line_items[$id]->qty_received;
      } else {
        Forms::hidden('line_no', ($this->lines_on_order + 1));
        Item_Purchase::cells(null, 'stock_id', null, false, true, true);
        if (Forms::isListUpdated('stock_id')) {
          Ajax::_activate('price');
          Ajax::_activate('units');
          Ajax::_activate('description');
          Ajax::_activate('qty');
          Ajax::_activate('discount');
          Ajax::_activate('req_del_date');
          Ajax::_activate('line_total');
        }
        $item_info             = Item::get_edit_info(Input::_post('stock_id'));
        $_POST['units']        = $item_info["units"];
        $_POST['description']  = '';
        $dec                   = $item_info["decimals"];
        $_POST['qty']          = Num::_format(Creditor_Trans::get_conversion_factor($this->creditor_id, Input::_post('stock_id')), $dec);
        $_POST['price']        = Num::_priceDecimal(Item_Price::get_purchase($this->creditor_id, Input::_post('stock_id')), $dec2);
        $_POST['req_del_date'] = Dates::_addDays(Dates::_today(), 10);
        $_POST['discount']     = Num::_percentFormat(0);
        $qty_rcvd              = '';
      }
      Forms::qtyCells(null, 'qty', null, null, null, $dec);
      Cell::qty($qty_rcvd, false, $dec);
      Cell::label($_POST['units'], '', 'units');
      Forms::dateCells(null, 'req_del_date', '', null, 0, 0, 0);
      Forms::amountCells(null, 'price', null, null, ['$'], $dec2);
      Forms::percentCells(null, 'discount', $_POST['discount']);
      $line_total = Validation::input_num('qty') * Validation::input_num('price') * (1 - Validation::input_num('discount') / 100);
      Cell::amount($line_total, false, '', 'line_total');
      if ($id != -1) {
        Forms::buttonCell(UPDATE_ITEM, _("Update"), _('Confirm changes'), ICON_UPDATE);
        Forms::buttonCell(CANCEL, _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        JS::_setFocus('qty');
      } else {
        Forms::submitCells(ADD_ITEM, _("Add Item"), "colspan=2", _('Add new item to document'), true);
      }
      echo '</tr>';
    }
    /**
     * @static
     *
     * @param $creditor_id
     * @param $stock_id
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get_data($creditor_id, $stock_id) {
      $sql
              = "SELECT * FROM purch_data
                WHERE creditor_id = " . DB::_escape($creditor_id) . "
                AND stock_id = " . DB::_escape($stock_id);
      $result = DB::_query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param        $creditor_id
     * @param        $stock_id
     * @param        $price
     * @param string $supplier_code
     * @param string $uom
     *
     * @return bool
     */
    public static function add_or_update_data($creditor_id, $stock_id, $price, $supplier_code = "", $uom = "") {
      $data = static::get_data($creditor_id, $stock_id);
      if ($data === false) {
        $supplier_code = $stock_id;
        try {
          $sql
            = "INSERT INTO purch_data (creditor_id, stock_id, price, suppliers_uom,
                    conversion_factor, supplier_description) VALUES (" . DB::_escape($creditor_id) . ", " . DB::_escape($stock_id) . ", " . DB::_escape(
            $price
          ) . ", " . DB::_escape(
            $uom
          ) . ", 1, " . DB::_escape($supplier_code) . ")";
          DB::_query($sql, "The supplier purchasing details could not be added");
          return false;
        } catch (\ADV\Core\DB\DBDuplicateException $e) {
          return true;
        }
      }
      $price = round($price * $data['conversion_factor'], User::_price_dec());
      $sql   = "UPDATE purch_data SET price=" . DB::_escape($price);
      if ($uom != "") {
        $sql .= ",suppliers_uom=" . DB::_escape($uom);
      }
      if ($supplier_code != "") {
        $sql .= ",supplier_description=" . DB::_escape($supplier_code);
      }
      $sql .= " WHERE stock_id=" . DB::_escape($stock_id) . " AND creditor_id=" . DB::_escape($creditor_id);
      DB::_query($sql, "The supplier purchasing details could not be updated");
      return true;
    }
    /**
     * @param $order
     */
    public static function copyFromPost($order) {
      $order->creditor_id      = Input::_post('creditor_id', Input::NUMERIC, null);
      $order->orig_order_date  = $_POST['OrderDate'];
      $order->reference        = $_POST['ref'];
      $order->requisition_no   = $_POST['Requisition'];
      $order->Comments         = $_POST['Comments'];
      $order->location         = $_POST['location'];
      $order->delivery_address = $_POST['delivery_address'];
      $order->freight          = $_POST['freight'];
      $order->salesman         = $_POST['salesman'];
    }
    /**
     * @param $order
     *
     * @return \Purch_Order|\Sales_Order
     */
    public static function copyToPost($order) {
      if (!Input::_get('UseOrder')) {
        $order = Purch_Order::check_edit_conflicts($order);
      }
      $_POST['creditor_id']      = $order->creditor_id;
      $_POST['OrderDate']        = $order->orig_order_date;
      $_POST['Requisition']      = $order->requisition_no;
      $_POST['ref']              = $order->reference;
      $_POST['Comments']         = $order->Comments;
      $_POST['location']         = $order->location;
      $_POST['delivery_address'] = $order->delivery_address;
      $_POST['freight']          = $order->freight;
      $_POST['salesman']         = $order->salesman;
      $_POST['order_id']         = $order->order_id;
      return Orders::session_set($order);
    }
  } /* end of class defintion */

