  <?php
    use ADV\Core\Cell;
    use ADV\App\Ref;
    use ADV\Core\Config;
    use ADV\App\SysTypes;
    use ADV\Core\Session;
    use ADV\App\Orders;
    use ADV\Core\HTML;
    use ADV\App\Validation;
    use ADV\Core\Num;
    use ADV\App\User;
    use ADV\App\Display;
    use ADV\App\Dates;
    use ADV\Core\Event;
    use ADV\App\Forms;
    use ADV\App\Dimensions;
    use ADV\App\UI;
    use ADV\App\Reports\Email;
    use ADV\App\WO\WO;
    use ADV\App\Tax\Tax;
    use ADV\Core\DB\DB;
    use ADV\Core\JS;
    use ADV\App\Item\Item;
    use ADV\Core\Ajax;
    use ADV\Core\Input\Input;
    use ADV\App\Debtor\Debtor;
    use ADV\Core\Table;

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    /* Definition of the order class
    this class can hold all the information for:

    i) a sales order
    ii) an invoice
    iii) a credit note
    iv) a delivery note
    */
    /** **/
    class Sales_Order
    {
      /** @var int * */
      public $trans_type; // invoice, order, quotation, delivery note ...
      /** @var array * */
      public $trans_no = []; // array (num1=>ver1,..) or 0 for new
      /** @var int * */
      public $so_type = 0; // for sales order: simple=0 template=1
      /** @var */
      public $order_id; // used to detect multi-tab edition conflits
      /** @var Sales_Line[]* */
      public $line_items; //array of objects of class Sales_Line
      /** @var array * */
      public $src_docs = []; // array of arrays(num1=>ver1,...) or 0 for no src
      /** @var */
      public $src_date; // src document date (for info only)
      /** @var null * */
      public $source_no = null;
      /** @var */
      public $document_date;
      /** @var */
      public $due_date;
      /** @var */
      public $salesman;
      /** @var string * */
      public $sales_type; // set to the customer's sales type
      /** @var */
      public $sales_type_name; // set to customer's sales type name
      /** @var */
      public $tax_included;
      /** @var */
      public $customer_currency; // set to the customer's currency
      /** @var */
      public $default_discount; // set to the customer's discount %
      /** @var */
      public $customer_name;
      /** @var */
      public $debtor_id = 0;
      /** @var */
      public $Branch;
      /** @var */
      public $email;
      /** @var */
      public $deliver_to;
      /** @var */
      public $delivery_address;
      /** @var */
      public $name;
      /** @var */
      public $phone;
      /** @var */
      public $cust_ref;
      /** @var */
      public $reference;
      /** @var */
      public $Comments;
      /** @var */
      public $location;
      /** @var */
      public $location_name;
      /** @var */
      public $order_no; // the original order number
      /** @var int * */
      public $trans_link = 0;
      /** @var */
      public $ship_via;
      /** @var int * */
      public $freight_cost = 0;
      /** @var */
      public $tax_group_id;
      /** @var */
      public $tax_group_name;
      /** @var null * */
      public $tax_group_array = null; // saves db queries
      /** @var */
      public $price_factor; // ditto for price calculations
      /** @var */
      public $pos; // user assigned POS
      /** @var */
      public $cash; // cash transaction
      /** @var */
      public $cash_account;
      /** @var */
      public $account_name;
      /** @var int * */
      public $dimension_id;
      /** @var int * */
      public $dimension2_id;
      /** @var */
      public $payment;
      /** @var */
      public $payment_terms; // cached payment terms
      /** @var */
      public $credit;
      /** @var */
      protected $uniqueid;
      /** @var bool * */
      public $view_only = false;
      //
      // $trans_no==0 => open new/direct document
      // $trans_no!=0 && $this->view_only==false => read for view
      // $trans_no!=0 && $this->view_only==true => read for edit (qty update from parent doc)
      //
      /***
       * @param           $type
       * @param int|array $trans_no
       * @param bool      $view
       */
      public function __construct($type, $trans_no = 0, $view = false) {
        /*Constructor function initialises a new shopping order */
        $this->line_items = [];
        $this->sales_type = "";
        $this->view_only  = $view;
        $this->trans_type = $type;
        $this->read($type, $trans_no);
        $this->generateID();
      }
      /**

       */
      protected function generateID() {
        $this->uniqueid = uniqid();
        $this->order_id = $this->trans_type . '.' . sha1($this->trans_type . serialize($this->trans_no));
      }
      /**
       * @param     $type
       * @param int $trans_no
       */
      public function read($type, $trans_no = 0) {
        if (!is_array($trans_no)) {
          $trans_no = array($trans_no);
        }
        if ($trans_no[0]) // read old transaction
        {
          if ($type == ST_SALESORDER || $type == ST_SALESQUOTE) { // sales order || sales quotation
            $this->get($trans_no[0], $type);
            if ($this->view_only) { // prepare for DN/IV entry
              for ($line_no = 0; $line_no < count($this->line_items); $line_no++) {
                $line                 = & $this->line_items[$line_no];
                $line->src_id         = $line->id; // save src line ids for update
                $line->qty_dispatched = $line->quantity - $line->qty_done;
              }
            }
          } else { // other type of sales transaction
            Debtor_Trans::read($type, $trans_no, $this);
            if ($this->order_no) { // free hand credit notes have no order_no
              $sodata         = Sales_Order::get_header($this->order_no, ST_SALESORDER);
              $this->cust_ref = $sodata["customer_ref"];
              // currently currency is hard linked to debtor account
              //	$this->customer_currency = $sodata["curr_code"];
              $this->name             = $sodata["contact_name"];
              $this->deliver_to       = $sodata["deliver_to"];
              $this->delivery_address = $sodata["delivery_address"];
            }
            // old derivative transaction edit
            if (!$this->view_only && ($type != ST_CUSTCREDIT || $this->trans_link != 0)) {
              $src_type = Debtor_Trans::get_parent_type($type);
              if ($src_type == ST_SALESORDER && isset($sodata)) { // get src data from sales_orders
                $this->src_docs = array($sodata['order_no'] => $sodata['version']);
                $srcdetails     = Sales_Order::get_details($this->order_no, ST_SALESORDER);
              } else { // get src_data from debtor_trans
                $this->src_docs = Debtor_Trans::get_version($src_type, Debtor_Trans::get_parent($type, $trans_no[0]));
                $srcdetails     = Debtor_TransDetail::get($src_type, array_keys($this->src_docs));
              }
              // calculate & save: qtys on other docs and free qtys on src doc
              for ($line_no = 0; $srcline = DB::_fetch($srcdetails); $line_no++) {
                $sign          = 1; // $type==13 ? 1 : -1; // this is strange debtor_trans atavism
                $line          = & $this->line_items[$line_no];
                $line->src_id  = $srcline['id']; // save src line ids for update
                $line->qty_old = $line->qty_dispatched = $line->quantity;
                $line->quantity += $sign * ($srcline['quantity'] - $srcline['qty_done']); // add free qty on src doc
              }
            } else { // prepare qtys for derivative document entry (not used in display)
              for ($line_no = 0; $line_no < count($this->line_items); $line_no++) {
                $line                 = & $this->line_items[$line_no];
                $line->src_id         = $line->id; // save src line ids for update
                $line->qty_dispatched = $line->quantity - $line->qty_done;
              }
            }
          }
        } else { // new document
          $this->trans_type        = $type;
          $this->trans_no          = 0;
          $this->customer_currency = Bank_Currency::for_company();
          // set new sales document defaults here
          if (Session::_getGlobal('debtor_id')) {
            $this->debtor_id = Session::_getGlobal('debtor_id');
          } else {
            $this->debtor_id = '';
          }
          $this->document_date = Dates::_newDocDate();
          if (!Dates::_isDateInFiscalYear($this->document_date)) {
            $this->document_date = Dates::_endFiscalYear();
          }
          $this->reference = Ref::get_next($this->trans_type);
          $this->set_salesman();
          if ($type == ST_SALESINVOICE) {
            $this->due_date = Sales_Order::get_invoice_duedate($this->debtor_id, $this->document_date);
            $this->pos      = User::_pos();
            $pos            = Sales_Point::get($this->pos);
            $this->cash     = !$pos['credit_sale'];
            if (!$pos['cash_sale'] || !$pos['credit_sale'] || $this->due_date == Dates::_today()) {
              $this->pos = -1;
            } // mark not editable payment type
            else {
              $this->cash = Dates::_differenceBetween($this->due_date, Dates::_today(), 'd') < 2;
            }
            if ($this->cash) {
              $this->location      = $pos['pos_location'];
              $this->location_name = $pos['location_name'];
              $this->cash_account  = $pos['pos_account'];
              $this->account_name  = $pos['bank_account_name'];
            }
          } else {
            $this->due_date = Dates::_addDays($this->document_date, DB_Company::_get_pref('default_delivery_required'));
          }
        }
        if ($this->trans_type == ST_SALESORDER) {
          $this->order_no = $trans_no[0];
        }
        $this->credit = Debtor::get_credit($this->debtor_id);
      }
      /**
       * Writing new/modified sales document to database.
       * Makes parent documents for direct delivery/invoice by recurent call.
       * $policy - 0 or 1: writeoff/return for IV, back order/cancel for DN
       *
       * @param int $policy
       *
       * @return int|void
       */
      public function write($policy = 0) {
        if (count($this->src_docs) == 0 && ($this->trans_type == ST_SALESINVOICE || $this->trans_type == ST_CUSTDELIVERY)) {
          // this is direct document - first add parent
          $src             = (PHP_VERSION < 5) ? $this : clone($this); // make local copy of this order
          $src->trans_type = Debtor_Trans::get_parent_type($src->trans_type);
          $src->reference  = Ref::get_next($src->trans_type);
          if ($this->trans_type == ST_SALESINVOICE) {
            foreach ($src->line_items as $line) {
              $line->qty_dispatched = $line->quantity;
            }
          }
          $src->write(1);
          $type = $this->trans_type;
          $ref  = $this->reference;
          $date = $this->document_date;
          // re-read document
          $this->read($src->trans_type, key($src->trans_no), true);
          $this->document_date = $date;
          $this->reference     = $ref;
          $this->trans_type    = $type;
          $this->src_docs      = $this->trans_no;
          $this->trans_no      = 0;
          $this->order_no      = ($this->trans_type == ST_CUSTDELIVERY) ? key($src->trans_no) : $src->order_no;
        }
        $this->reference = @html_entity_decode($this->reference, ENT_QUOTES);
        $this->Comments  = @html_entity_decode($this->Comments, ENT_QUOTES);
        foreach ($this->line_items as $lineno => $line) {
          $this->line_items[$lineno]->stock_id    = @html_entity_decode($line->stock_id, ENT_QUOTES);
          $this->line_items[$lineno]->description = @html_entity_decode($line->description, ENT_QUOTES);
        }
        Orders::session_delete($this->order_id);
        switch ($this->trans_type) {
          case ST_SALESINVOICE:
            return Sales_Invoice::add($this);
          case ST_CUSTCREDIT:
            return Sales_Credit::add($this, $policy);
          case ST_CUSTDELIVERY:
            return Sales_Delivery::add($this, $policy);
          case ST_SALESORDER:
          case ST_SALESQUOTE:
          default:
            if ($this->trans_no == 0) // new document
            {
              return $this->add();
            } else {
              return $this->update();
            }
        }
      }
      /**
       * @param $cust_ref
       *
       * @return bool
       */
      public function check_cust_ref($cust_ref) {
        $sql    = "SELECT customer_ref,type FROM sales_orders WHERE debtor_id=" . DB::_escape($this->debtor_id) . " AND customer_ref=" . DB::_escape($cust_ref
          ) . " AND type != " . $this->trans_type;
        $result = DB::_query($sql);
        return (DB::_numRows($result) > 0) ? true : false;
      }
      /**
       * @param $debtor_id
       * @param $customer_name
       * @param $currency
       * @param $discount
       * @param $payment
       */
      public function set_customer($debtor_id, $customer_name, $currency, $discount, $payment) {
        $this->customer_name     = $customer_name;
        $this->debtor_id         = $debtor_id;
        $this->default_discount  = $discount;
        $this->customer_currency = $currency;
        $this->payment           = $payment;
        $this->payment_terms     = DB_Company::_get_payment_terms($payment);
        if ($this->payment_terms['cash_sale']) {
          $pos                 = Sales_Point::get($this->pos);
          $this->location      = $pos['pos_location'];
          $this->location_name = $pos['location_name'];
        }
        if ($debtor_id > 0) {
          $this->credit = Debtor::get_credit($debtor_id);
        }
      }
      /**
       * @param        $branch_id
       * @param        $tax_group_id
       * @param bool   $tax_group_name
       * @param string $phone
       * @param string $email
       * @param string $name
       */
      public function set_branch($branch_id, $tax_group_id, $tax_group_name = false, $phone = '', $email = '', $name = '') {
        $this->Branch          = $branch_id;
        $this->phone           = $phone;
        $this->email           = $email;
        $this->tax_group_id    = $tax_group_id;
        $this->tax_group_array = Tax_Groups::get_items_as_array($tax_group_id);
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
       * @param     $sales_type
       * @param     $sales_name
       * @param int $tax_included
       * @param int $factor
       */
      public function set_sales_type($sales_type, $sales_name, $tax_included = 0, $factor = 0) {
        $this->sales_type      = $sales_type;
        $this->sales_type_name = $sales_name;
        $this->tax_included    = $tax_included;
        $this->price_factor    = $factor;
      }
      /**
       * @param $id
       * @param $name
       */
      public function set_location($id, $name) {
        $this->location      = $id;
        $this->location_name = $name;
      }
      /**
       * @param      $shipper
       * @param      $destination
       * @param      $address
       * @param null $freight_cost
       */
      public function set_delivery($shipper, $destination, $address, $freight_cost = null) {
        $this->ship_via         = $shipper;
        $this->deliver_to       = $destination;
        $this->delivery_address = $address;
        if (isset($freight_cost)) {
          $this->freight_cost = $freight_cost;
        }
      }
      /**
       * @param      $line_no
       * @param      $stock_id
       * @param      $qty
       * @param      $price
       * @param      $disc
       * @param int  $qty_done
       * @param int  $standard_cost
       * @param null $description
       * @param int  $id
       * @param int  $src_no
       *
       * @return int
       */
      public function add_to_order($line_no, $stock_id, $qty, $price, $disc, $qty_done = 0, $standard_cost = 0, $description = null, $id = 0, $src_no = 0) {
        if (isset($stock_id) && $stock_id != "" && isset($qty) /* && $qty > 0*/) {
          $this->line_items[$line_no] = new Sales_Line($stock_id, $qty, $price, $disc, $qty_done, $standard_cost, $description, $id, $src_no);
          return 1;
        } else {
          // shouldn't come here under normal circumstances
          Event::error("unexpected - adding an invalid item or null quantity", "", true);
        }
        return 0;
      }
      /**
       * @param        $line_no
       * @param        $qty
       * @param        $price
       * @param        $disc
       * @param string $description
       */
      public function update_order_item($line_no, $qty, $price, $disc, $description = "") {
        if ($description != "") {
          $this->line_items[$line_no]->description = $description;
        }
        $this->line_items[$line_no]->quantity         = $qty;
        $this->line_items[$line_no]->qty_dispatched   = $qty;
        $this->line_items[$line_no]->price            = $price;
        $this->line_items[$line_no]->discount_percent = $disc;
      }
      /**
       * @param $discount
       */
      public function discount_all($discount) {
        foreach ($this->line_items as $line) {
          $line->discount_percent = $discount;
        }
      }
      /**
       * @param $line_no
       * @param $qty
       */
      public function update_add_order_item_qty($line_no, $qty) {
        $this->line_items[$line_no]->quantity += $qty;
      }
      /**
       * @param $line_no
       */
      public function remove_from_order($line_no) {
        array_splice($this->line_items, $line_no, 1);
      }
      /**

       */
      public function clear_items() {
        unset($this->line_items);
        $this->line_items = [];
        $this->sales_type = "";
        $this->trans_no   = 0;
        $this->debtor_id  = $this->order_no = 0;
      }
      /**
       * @return int
       */
      public function count_items() {
        $counter = 0;
        foreach ($this->line_items as $line) {
          if ($line->quantity != $line->qty_done) {
            $counter++;
          }
        }
        return $counter;
      }
      /**
       * @return float|int
       */
      public function get_items_total() {
        $total = 0;
        foreach ($this->line_items as $line) {
          /* @var Sales_Line $line */
          $price = $line->line_price();
          $total += round($line->quantity * $price * (1 - $line->discount_percent), User::_price_dec());
        }
        return $total;
      }
      /**
       * @return float|int
       */
      public function get_items_total_dispatch() {
        $total = 0;
        foreach ($this->line_items as $line) {
          /* @var Sales_Line $line */
          $price = $line->price;
          $total += round(($line->qty_dispatched * $price * (1 - $line->discount_percent)), User::_price_dec());
        }
        return $total;
      }
      /**
       * @return bool
       */
      public function has_items_dispatch() {
        foreach ($this->line_items as $line) {
          if ($line->qty_dispatched > 0) {
            return true;
          }
        }
        return false;
      }
      /**
       * @return int
       */
      public function any_already_delivered() {
        /* Checks if there have been any line item processed */
        foreach ($this->line_items as $stock_item) {
          if ($stock_item->qty_done != 0) {
            return 1;
          }
        }
        return 0;
      }
      /**
       * @param $line_no
       *
       * @return int
       */
      public function some_already_delivered($line_no) {
        /* Checks if there have been deliveries of a specific line item */
        if (isset($this->line_items[$line_no]) && $this->line_items[$line_no]->qty_done != 0) {
          return 1;
        }
        return 0;
      }
      /**
       * @param null $shipping_cost
       *
       * @return array|null
       */
      public function get_taxes_for_order($shipping_cost = null) {
        $items  = [];
        $prices = [];
        if ($shipping_cost == null) {
          $shipping_cost = $this->freight_cost;
        }
        foreach ($this->line_items as $ln_item) {
          /* @var Sales_Line $ln_item */
          $items[]  = $ln_item->stock_id;
          $prices[] = round(($ln_item->quantity * $ln_item->line_price() * (1 - $ln_item->discount_percent)), User::_price_dec());
        }
        $taxes = Tax::for_items($items, $prices, $shipping_cost, $this->tax_group_id, $this->tax_included, $this->tax_group_array);
        // Adjustment for swiss franken, we always have 5 rappen = 1/20 franken
        if ($this->customer_currency == 'CHF') {
          $val                 = $taxes['1']['Value'];
          $val1                = (floatval((intval(round(($val * 20), 0))) / 20));
          $taxes['1']['Value'] = $val1;
        }
        return $taxes;
      }
      /**
       * @param null $shipping_cost
       *
       * @return array|null
       */
      public function get_taxes($shipping_cost = null) {
        $items  = [];
        $prices = [];
        if ($shipping_cost == null) {
          $shipping_cost = $this->freight_cost;
        }
        foreach ($this->line_items as $line) {
          /* @var Sales_Line $line */
          $items[]  = $line->stock_id;
          $prices[] = round(($line->qty_dispatched * $line->line_price() * (1 - $line->discount_percent)), User::_price_dec());
        }
        $taxes = Tax::for_items($items, $prices, $shipping_cost, $this->tax_group_id, $this->tax_included, $this->tax_group_array);
        // Adjustment for swiss franken, we always have 5 rappen = 1/20 franken
        if ($this->customer_currency == 'CHF') {
          $val                 = $taxes['1']['Value'];
          $val1                = (floatval((intval(round(($val * 20), 0))) / 20));
          $taxes['1']['Value'] = $val1;
        }
        return $taxes;
      }
      /**
       * @return int
       */
      public function get_tax_free_shipping() {
        if ($this->tax_included == 0) {
          return $this->freight_cost;
        } else {
          return ($this->freight_cost - $this->get_shipping_tax());
        }
      }
      /**
       * @return float
       */
      public function get_shipping_tax() {
        $tax_items = Tax_Groups::for_shipping_as_array();
        $tax_rate  = 0;
        if ($tax_items != null) {
          foreach ($tax_items as $item_tax) {
            $index = $item_tax['tax_type_id'];
            if (isset($this->tax_group_array[$index])) {
              $tax_rate += $item_tax['rate'];
            }
          }
        }
        if ($this->tax_included) {
          return round($this->freight_cost * $tax_rate / ($tax_rate + 100), User::_price_dec());
        } else {
          return round($this->freight_cost * $tax_rate / 100, User::_price_dec());
        }
      }
      /**
       * @return int
       */
      public function add() {
        DB::_begin();
        $order_no   = SysTypes::get_next_trans_no($this->trans_type);
        $del_date   = Dates::_dateToSql($this->due_date);
        $order_type = 0; // this is default on new order
        $sql
                    = "INSERT INTO sales_orders (order_no, type, debtor_id, trans_type, branch_id, customer_ref, reference, salesman, comments, source_no, ord_date,
            order_type, ship_via, deliver_to, delivery_address, contact_name, contact_phone,
            contact_email, freight_cost, from_stk_loc, delivery_date)
            VALUES (" . DB::_escape($order_no) . "," . DB::_escape($order_type) . "," . DB::_escape($this->debtor_id) . ", " . DB::_escape($this->trans_type) . "," . DB::_escape($this->Branch
          ) . ", " . DB::_escape($this->cust_ref) . "," . DB::_escape($this->reference) . "," . DB::_escape($this->salesman) . "," . DB::_escape($this->Comments) . "," . DB::_escape($this->source_no
          ) . ",'" . Dates::_dateToSql($this->document_date) . "', " . DB::_escape($this->sales_type) . ", " . DB::_escape($this->ship_via) . "," . DB::_escape($this->deliver_to
          ) . "," . DB::_escape($this->delivery_address) . ", " . DB::_escape($this->name) . ", " . DB::_escape($this->phone) . ", " . DB::_escape($this->email) . ", " . DB::_escape($this->freight_cost
          ) . ", " . DB::_escape($this->location) . ", " . DB::_escape($del_date) . ")";
        DB::_query($sql, "order Cannot be Added");
        $this->trans_no = array($order_no => 0);
        $st_ids         = [];
        if (Config::_get('accounts.stock_emailnotify') == 1) {
          $st_names   = [];
          $st_num     = [];
          $st_reorder = [];
        }
        foreach ($this->line_items as $position => $line) {
          if (Config::_get('accounts.stock_emailnotify') == 1 && Item::is_inventory_item($line->stock_id)) {
            $sql
                 = "SELECT stock_location.*, locations.location_name, locations.email
                    FROM stock_location, locations
                    WHERE stock_location.loc_code=locations.loc_code
                    AND stock_location.stock_id = '" . $line->stock_id . "'
                    AND stock_location.loc_code = '" . $this->location . "'";
            $res = DB::_query($sql, "a location could not be retreived");
            $loc = DB::_fetch($res);
            if ($loc['email'] != "") {
              $qoh = Item::get_qoh_on_date($line->stock_id, $this->location);
              $qoh -= Item::get_demand($line->stock_id, $this->location);
              $qoh -= WO::get_demand_asm_qty($line->stock_id, $this->location);
              $qoh -= $line->quantity;
              if ($qoh < $loc['reorder_level']) {
                $st_ids[]     = $line->stock_id;
                $st_names[]   = $line->description;
                $st_num[]     = $qoh - $loc['reorder_level'];
                $st_reorder[] = $loc['reorder_level'];
              }
            }
          }
          $sql = "INSERT INTO sales_order_details (order_no, trans_type, stk_code, description, unit_price, quantity, discount_percent,sort_order) VALUES (";
          $sql .= $order_no . "," . $this->trans_type . "," . DB::_quote($line->stock_id) . ", " . DB::_quote($line->description
            ) . "," . $line->price . ", " . $line->quantity . ", " . $line->discount_percent . ", " . (int)$position . ")";
          DB::_query($sql, "order Details Cannot be Added");
        } /* inserted line items into sales order details */
        DB_AuditTrail::add($this->trans_type, $order_no, $this->document_date);
        Ref::save($this->trans_type, $this->reference);
        DB::_commit();
        if (isset($loc, $st_names, $st_reorder) && Config::_get('accounts.stock_emailnotify') == 1 && count($st_ids) > 0) {
          $this->email_notify($loc, $st_ids, $st_names, $st_reorder, $st_num);
        }
        Orders::session_delete($this->order_id);
        return $order_no;
      }
      /**
       * @param $order_no
       * @param $trans_type
       *
       * @return bool
       */
      public function get($order_no, $trans_type) {
        $myrow            = $this->get_header($order_no, $trans_type);
        $this->trans_type = $myrow['trans_type'];
        $this->so_type    = $myrow["type"];
        $this->trans_no   = array($order_no => $myrow["version"]);
        $this->set_customer($myrow["debtor_id"], $myrow["name"], $myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]);
        $this->set_branch($myrow["branch_id"], $myrow["tax_group_id"], $myrow["tax_group_name"], $myrow["contact_phone"], $myrow["contact_email"], $myrow["contact_name"]);
        $this->set_sales_type($myrow["sales_type_id"], $myrow["sales_type"], $myrow["tax_included"], 0); // no default price calculations on edit
        $this->set_location($myrow["from_stk_loc"], $myrow["location_name"]);
        $this->set_delivery($myrow["ship_via"], $myrow["deliver_to"], $myrow["delivery_address"], $myrow["freight_cost"]);
        $this->cust_ref      = $myrow["customer_ref"];
        $this->name          = $myrow["contact_name"];
        $this->sales_type    = $myrow["order_type"];
        $this->reference     = $myrow["reference"];
        $this->salesman      = $myrow["salesman"];
        $this->Comments      = $myrow["comments"];
        $this->source_no     = $myrow["source_no"];
        $this->due_date      = Dates::_sqlToDate($myrow["delivery_date"]);
        $this->document_date = Dates::_sqlToDate($myrow["ord_date"]);
        $result              = Sales_Order::get_details($order_no, $this->trans_type);
        if (DB::_numRows($result) > 0) {
          $line_no = 0;
          while ($myrow = DB::_fetch($result)) {
            $this->add_to_order($line_no, $myrow["stk_code"], $myrow["quantity"], $myrow["unit_price"], $myrow["discount_percent"], $myrow["qty_done"], $myrow["standard_cost"], $myrow["description"], $myrow["id"]
            );
            $line_no++;
          }
        }
        return true;
      }
      /**
       * @param      $new_item
       * @param      $new_item_qty
       * @param      $price
       * @param      $discount
       * @param null $description
       * @param bool $no_errors
       *
       * @return mixed
       */
      public function add_line($new_item, $new_item_qty, $price, $discount, $description = null, $no_errors = false) {
        // calculate item price to sum of kit element prices factor for
        // value distribution over all exploded kit items
        $item = Item_Code::is_kit($new_item);
        if (DB::_numRows($item) == 1) {
          $item = DB::_fetch($item);
          if (!$item['is_foreign'] && $item['item_code'] == $item['stock_id']) {
            foreach ($this->line_items as $order_item) {
              if (strcasecmp($order_item->stock_id, $item['stock_id']) == 0 && !$no_errors) {
                Event::warning(_("For Part: '") . $item['stock_id'] . "' " . _("This item is already on this document. You have been warned."));
                break;
              }
            }
            $this->add_to_order(count($this->line_items), $item['stock_id'], $new_item_qty * $item['quantity'], $price, $discount, 0, 0, $description);
            return;
          }
        }
        $std_price = Item_Price::get_kit($new_item, $this->customer_currency, $this->sales_type, $this->price_factor, Input::_post('OrderDate'), true);
        if ($std_price == 0) {
          $price_factor = 0;
        } else {
          $price_factor = $price / $std_price;
        }
        $kit      = Item_Code::get_kit($new_item);
        $item_num = DB::_numRows($kit);
        while ($item = DB::_fetch($kit)) {
          $std_price = Item_Price::get_kit($item['stock_id'], $this->customer_currency, $this->sales_type, $this->price_factor, Input::_post('OrderDate'), true);
          // rounding differences are included in last price item in kit
          $item_num--;
          if ($item_num) {
            $price -= $item['quantity'] * $std_price * $price_factor;
            $item_price = $std_price * $price_factor;
          } else {
            if ($item['quantity']) {
              $price = $price / $item['quantity'];
            }
            $item_price = $price;
          }
          $item_price = round($item_price, User::_price_dec());
          if (!$item['is_foreign'] && $item['item_code'] != $item['stock_id']) { // this is sales kit - recurse
            $this->add_line($item['stock_id'], $new_item_qty * $item['quantity'], $item_price, $discount, $description);
          } else { // stock item record eventually with foreign code
            // check duplicate stock item
            foreach ($this->line_items as $this_item) {
              if (strcasecmp($this_item->stock_id, $item['stock_id']) == 0) {
                Event::warning(_("For Part: '") . $item['stock_id'] . "' " . _("This item is already on this document. You have been warned."));
                break;
              }
            }
            $this->add_to_order(count($this->line_items), $item['stock_id'], $new_item_qty * $item['quantity'], $item_price, $discount);
          }
        }
      }
      /**

       */
      public function start() {
        Orders::session_start($this);
      }
      /**

       */
      public function finish() {
        if (is_object($this) && Orders::session_exists($this)) {
          Orders::session_delete($this->order_id);
        }
      }
      /**
       * @return bool
       */
      public static function active() {
        return Orders::session_get();
      }
      /**
       * @param $order
       */
      public static function update_version($order) {
        foreach ($order as $so_num => $so_ver) {
          $sql = 'UPDATE sales_orders SET version=version+1 WHERE order_no=' . $so_num . ' AND version=' . $so_ver . " AND trans_type=30";
          DB::_query($sql, 'Concurrent editing conflict while sales order update');
        }
      }
      /**
       * @return mixed
       */
      public function update() {
        $del_date = Dates::_dateToSql($this->due_date);
        $ord_date = Dates::_dateToSql($this->document_date);
        $order_no = key($this->trans_no);
        $version  = current($this->trans_no);
        DB::_begin();
        $sql = "UPDATE sales_orders SET type =" . DB::_escape($this->so_type) . " ,
                    debtor_id = " . DB::_escape($this->debtor_id) . ",
                    branch_id = " . DB::_escape($this->Branch) . ",
                    customer_ref = " . DB::_escape($this->cust_ref) . ",
                    source_no = " . DB::_escape($this->source_no) . ",
                    reference = " . DB::_escape($this->reference) . ",
                    salesman = " . DB::_escape($this->salesman) . ",
                    comments = " . DB::_escape($this->Comments) . ",
                    ord_date = " . DB::_escape($ord_date) . ",
                    order_type = " . DB::_escape($this->sales_type) . ",
                    ship_via = " . DB::_escape($this->ship_via) . ",
                    deliver_to = " . DB::_escape($this->deliver_to) . ",
                    delivery_address = " . DB::_escape($this->delivery_address) . ",
                    contact_name = " . DB::_escape($this->name) . ",
                    contact_phone = " . DB::_escape($this->phone) . ",
                    contact_email = " . DB::_escape($this->email) . ",
                    freight_cost = " . DB::_escape($this->freight_cost) . ",
                    from_stk_loc = " . DB::_escape($this->location) . ",
                    delivery_date = " . DB::_escape($del_date) . ",
                    version = " . ($version + 1) . "
                 WHERE order_no=" . $order_no . "
                 AND trans_type=" . $this->trans_type . " AND version=" . $version;
        DB::_query($sql, "order Cannot be Updated, this can be concurrent edition conflict");
        $sql = "DELETE FROM sales_order_details WHERE order_no =" . $order_no . " AND trans_type=" . $this->trans_type;
        DB::_query($sql, "Old order Cannot be Deleted");
        if (Config::_get('accounts.stock_emailnotify') == 1) {
          $st_ids     = [];
          $st_names   = [];
          $st_num     = [];
          $st_reorder = [];
        }
        foreach ($this->line_items as $position => $line) {
          if (Config::_get('accounts.stock_emailnotify') == 1 && Item::is_inventory_item($line->stock_id)) {
            $sql
                 = "SELECT stock_location.*, locations.location_name, locations.email
                            FROM stock_location, locations
                            WHERE stock_location.loc_code=locations.loc_code
                             AND stock_location.stock_id = " . DB::_escape($line->stock_id) . "
                             AND stock_location.loc_code = " . DB::_escape($this->location);
            $res = DB::_query($sql, "a location could not be retreived");
            $loc = DB::_fetch($res);
            if ($loc['email'] != "") {
              $qoh = Item::get_qoh_on_date($line->stock_id, $this->location);
              $qoh -= Item::get_demand($line->stock_id, $this->location);
              $qoh -= WO::get_demand_asm_qty($line->stock_id, $this->location);
              $qoh -= $line->quantity;
              if ($qoh < $loc['reorder_level']) {
                $st_ids[]     = $line->stock_id;
                $st_names[]   = $line->description;
                $st_num[]     = $qoh - $loc['reorder_level'];
                $st_reorder[] = $loc['reorder_level'];
              }
            }
          }
          $sql
            = "INSERT INTO sales_order_details
                     (id, order_no, trans_type, stk_code, description, unit_price, quantity,
                     discount_percent, qty_sent, sort_order)
                     VALUES (";
          $sql .= DB::_escape($line->id ? $line->id : 0
            ) . "," . $order_no . "," . $this->trans_type . "," . DB::_escape($line->stock_id) . ",
                        " . DB::_escape($line->description) . ", " . DB::_escape($line->price) . ", " . DB::_escape($line->quantity) . ", " . DB::_escape($line->discount_percent
            ) . ", " . DB::_escape($line->qty_done) . ", " . $position . " )";
          DB::_query($sql, "Old order Cannot be Inserted");
        } /* inserted line items into sales order details */
        DB_AuditTrail::add($this->trans_type, $order_no, $this->document_date, _("Updated."));
        Ref::delete($this->trans_type, $order_no);
        Ref::save($this->trans_type, $this->reference);
        DB::_commit();
        if (Config::_get('accounts.stock_emailnotify') == 1 && count($st_ids) > 0) {
          $this->email_notify($loc, $st_ids, $st_names, $st_reorder, $st_num);
        }
        return $order_no;
      }
      public function convertToOrder() {
        $this->trans_type    = ST_SALESORDER;
        $this->reference     = Ref::get_next($this->trans_type);
        $this->document_date = $this->due_date = Dates::_newDocDate();
        $this->Comments .= "\n\n" . _("Sales Quotation") . " #" . $this->source_no;
        $this->trans_no = 0;
        $this->order_no = 0;
        $this->generateID();
      }
      /**
       * @param $loc
       * @param $st_ids
       * @param $st_names
       * @param $st_reorder
       * @param $st_num
       */
      protected function email_notify($loc, $st_ids, $st_names, $st_reorder, $st_num) {
        $company = DB_Company::_get_prefs();
        $mail    = new Email($company['coy_name'], $company['email']);
        $to      = $loc['location_name'] . " <" . $loc['email'] . ">";
        $subject = _("Stocks below Re-Order Level at " . $loc['location_name']);
        $msg     = "\n";
        for ($i = 0; $i < count($st_ids); $i++) {
          $msg .= $st_ids[$i] . " " . $st_names[$i] . ", " . _("Re-Order Level") . ": " . $st_reorder[$i] . ", " . _("Below") . ": " . $st_num[$i] . "\n";
        }
        $msg .= "\n" . _("Please reorder") . "\n\n";
        $msg .= $company['coy_name'];
        $mail->to($to);
        $mail->subject($subject);
        $mail->text($msg);
        $mail->send();
      }
      /**
       * @param $debtor_id
       * @param $branch_id
       *
       * @return mixed|string
       */
      public function customer_to_order($debtor_id, $branch_id) {
        $ret_error = "";
        $myrow     = Sales_Order::get_customer($debtor_id);
        $name      = $myrow['name'];
        if ($myrow['dissallow_invoices'] == 1) {
          $ret_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
        }
        $this->set_customer($debtor_id, $name, $myrow['curr_code'], $myrow['discount'], $myrow['payment_terms'], $myrow['payment_discount']
        ); // the sales type determines the price list to be used by default
        $this->set_sales_type($myrow['salestype'], $myrow['sales_type'], $myrow['tax_included'], $myrow['factor']);
        if ($this->trans_type != ST_SALESORDER && $this->trans_type != ST_SALESQUOTE) {
          $this->dimension_id  = $myrow['dimension_id'];
          $this->dimension2_id = $myrow['dimension2_id'];
        }
        $result = Sales_Order::get_branch($debtor_id, $branch_id);
        if (DB::_numRows($result) == 0) {
          return _("The selected customer and branch are not valid, or the customer does not have any branches.");
        }
        $myrow = DB::_fetch($result);
        $this->set_branch($branch_id, $myrow["tax_group_id"], $myrow["tax_group_name"], $myrow["phone"], $myrow["email"]);
        //$address = trim($myrow["br_post_address"]) != '' ? $myrow["br_post_address"] : (trim($myrow["br_address"]) != '' ?		$myrow["br_address"] : $deliver);
        $address = $myrow['br_address'] . "\n";
        if ($myrow['city']) {
          $address .= $myrow['city'];
        }
        if ($myrow['state']) {
          $address .= ", " . strtoupper($myrow['state']);
        }
        if ($myrow['postcode']) {
          $address .= ", " . $myrow['postcode'];
        }
        $this->set_delivery($myrow["default_ship_via"], $name, $address);
        if ($this->trans_type == ST_SALESINVOICE) {
          $this->due_date = Sales_Order::get_invoice_duedate($debtor_id, $this->document_date);
          if ($this->pos != -1) {
            $this->cash = Dates::_differenceBetween($this->due_date, Dates::_today(), 'd') < 2;
          }
          if ($this->due_date == Dates::_today()) {
            $this->pos = -1;
          }
        }
        if ($this->cash) {
          if ($this->pos != -1) {
            $paym = Sales_Point::get($this->pos);
            $this->set_location($paym["pos_location"], $paym["location_name"]);
          }
        } else {
          $this->set_location($myrow["default_location"], $myrow["location_name"]);
        }
        return $ret_error;
      }
      /**
       * @param array $order_map
       *
       * @return bool
       */
      public function setLineOrder(array $order_map) {
        if (!$order_map || count($order_map) != count($this->line_items)) {
          return false;
        }
        $current_lines    = $this->line_items;
        $this->line_items = [];
        foreach ($current_lines as $line_no => $line) {
          $this->line_items[$order_map[$line_no]] = $line;
        }
        ksort($this->line_items);
        return true;
      }
      /**

       */
      public function display_delivery_details() {
        Ajax::_start_div('delivery');
        if (Input::_post('cash', null, 0)) { // Direct payment sale
          Ajax::_activate('items_table');
          Display::heading(_('Cash payment'));
          Table::start('standard width60');
          Table::label(_("Deliver from Location:"), $this->location_name);
          Forms::hidden('location', $this->location);
          Table::label(_("Cash account:"), $this->account_name);
          Forms::textareaRow(_("Comments:"), "Comments", $this->Comments, 31, 5);
          Table::end();
        } else {
          if ($this->trans_type == ST_SALESINVOICE) {
            $title   = _("Delivery Details");
            $delname = _("Due Date") . ':';
          } elseif ($this->trans_type == ST_CUSTDELIVERY) {
            $title   = _("Invoice Delivery Details");
            $delname = _("Invoice before") . ':';
          } elseif ($this->trans_type == ST_SALESQUOTE) {
            $title   = _("Quotation Delivery Details");
            $delname = _("Valid until") . ':';
          } else {
            $title   = _("Order Delivery Details");
            $delname = _("Required Delivery Date") . ':';
          }
          Display::heading($title);
          Table::startOuter('standard width90');
          Table::section(1);
          Inv_Location::row(_("Deliver from Location:"), 'location', null, false, true);
          if (Forms::isListUpdated('location')) {
            Ajax::_activate('items_table');
          }
          Forms::dateRow($delname, 'delivery_date', $this->trans_type == ST_SALESORDER ? _('Enter requested day of delivery') : $this->trans_type == ST_SALESQUOTE ? _('Enter Valid until Date') : ''
          );
          Forms::textRow(_("Deliver To:"), 'deliver_to', $this->deliver_to, 40, 40, _('Additional identifier for delivery e.g. name of receiving person'));
          Forms::textareaRow("<a href='#'>Address:</a>", 'delivery_address', $this->delivery_address, 35, 5, _('Delivery address. Default is address of customer branch'), null, 'id="address_map"'
          );
          if (strlen($this->delivery_address) > 10) {
            //JS::_gmap("#address_map", $this->delivery_address, $this->deliver_to);
          }
          Table::section(2);
          Forms::textRow(_("Person ordering:"), 'name', $this->name, 25, 25, 'Ordering person&#39;s name');
          Forms::textRow(_("Contact Phone Number:"), 'phone', $this->phone, 25, 25, _('Phone number of ordering person. Defaults to branch phone number'));
          Forms::textRow(_("Customer Purchase Order #:"), 'cust_ref', $this->cust_ref, 25, 25, _('Customer reference number for this order (if any)'));
          Forms::textareaRow(_("Comments:"), "Comments", $this->Comments, 31, 5);
          Sales_UI::shippers_row(_("Shipping Company:"), 'ship_via', $this->ship_via);
          Table::endOuter(1);
        }
        Ajax::_end_div();
      }
      /**
       * @static
       * @internal param $order_no
       * @internal param $trans_type
       */
      public function delete() {
        DB::_begin();
        $sql = "DELETE FROM sales_orders WHERE order_no=" . DB::_escape($this->order_no) . " AND trans_type=" . DB::_escape($this->trans_type);
        DB::_query($sql, "order Header Delete");
        $sql = "DELETE FROM sales_order_details WHERE order_no =" . DB::_escape($this->order_no) . " AND trans_type=" . DB::_escape($this->trans_type);
        DB::_query($sql, "order Detail Delete");
        Ref::delete($this->trans_type, $this->order_no);
        DB_AuditTrail::add($this->trans_type, key($this->trans_no), Dates::_today(), _("Deleted."));
        DB::_commit();
      }
      /**
       * @static
       *
       * @param $order_no
       * @param $trans_type
       *
       * @return \ADV\Core\DB\Query\Result|void
       * @throws DBException
       */
      public static function get_header($order_no, $trans_type) {
        $sql
                = "SELECT DISTINCT sales_orders.*,
         debtors.name,
         debtors.curr_code,
         debtors.email AS master_email,
         locations.location_name,
         debtors.payment_terms,
         debtors.discount,
         sales_types.sales_type,
         sales_types.id AS sales_type_id,
         sales_types.tax_included,
         shippers.shipper_name,
         tax_groups.name AS tax_group_name ,
         tax_groups.id AS tax_group_id
        FROM sales_orders,
        debtors,
        sales_types,
        tax_groups,
        branches,
        locations,
        shippers
        WHERE sales_orders.order_type=sales_types.id
            AND branches.branch_id = sales_orders.branch_id
            AND branches.tax_group_id = tax_groups.id
            AND sales_orders.debtor_id = debtors.debtor_id
            AND locations.loc_code = sales_orders.from_stk_loc
            AND shippers.shipper_id = sales_orders.ship_via
            AND sales_orders.trans_type = " . DB::_escape($trans_type) . "
            AND sales_orders.order_no = " . DB::_escape($order_no);
        $result = DB::_query($sql, "order Retreival");
        $num    = DB::_numRows($result);
        if ($num > 1) {
          throw new DBException("FATAL : sales order query returned a duplicate - " . DB::_numRows($result), E_ERROR);
        }
        if ($num == 1) {
          return DB::_fetch($result);
        }
        return Event::error("Order has been deleted or does not exist!", E_USER_ERROR);
      }
      /**
       * @static
       *
       * @param $order_no
       * @param $trans_type
       *
       * @return null|PDOStatement
       */
      public static function get_details($order_no, $trans_type) {
        $sql
          = "SELECT sales_order_details.id, stk_code, unit_price, sales_order_details.description,sales_order_details.quantity,
        discount_percent, qty_sent as qty_done, stock_master.units,stock_master.tax_type_id,stock_master.material_cost + stock_master.labour_cost + stock_master.overhead_cost AS standard_cost
        FROM sales_order_details, stock_master WHERE sales_order_details.stk_code = stock_master.stock_id AND order_no =" . DB::_escape($order_no
          ) . " AND trans_type = " . DB::_escape($trans_type) . " ORDER BY sort_order, id";
        return DB::_query($sql, "Retreive order Line Items");
      }
      /**
       * @static
       * @internal param $order_no
       * @return bool
       */
      public function has_deliveries() {
        $sql    = "SELECT SUM(qty_sent) FROM sales_order_details WHERE order_no=" . DB::_escape($this->order_no) . " AND trans_type=" . ST_SALESORDER . "";
        $result = DB::_query($sql, "could not query for sales order usage");
        $row    = DB::_fetchRow($result);
        return ($row[0] > 0);
      }
      /**
       * @static
       *
       * @param $order_no
       */
      public static function close($order_no) {
        $sql
          = "UPDATE sales_order_details
            SET quantity = qty_sent WHERE order_no = " . DB::_escape($order_no) . " AND trans_type=" . ST_SALESORDER . "";
        DB::_query($sql, "The sales order detail record could not be updated");
      }
      /**
       * @static
       *
       * @param $debtorno
       * @param $invdate
       *
       * @return string
       */
      public static function get_invoice_duedate($debtorno, $invdate) {
        if (!Dates::_isDate($invdate)) {
          return Dates::_newDocDate();
        }
        $sql
                = "SELECT debtors.debtor_id, debtors.payment_terms, payment_terms.* FROM debtors,
            payment_terms WHERE debtors.payment_terms = payment_terms.terms_indicator AND
            debtors.debtor_id = " . DB::_escape($debtorno);
        $result = DB::_query($sql, "The customer details could not be retrieved");
        $myrow  = DB::_fetch($result);
        if (DB::_numRows($result) == 0) {
          return $invdate;
        }
        if ($myrow['day_in_following_month'] > 0) {
          $duedate = Dates::_addDays(Dates::_endMonth($invdate), $myrow['day_in_following_month']);
        } else {
          $duedate = Dates::_addDays($invdate, $myrow['days_before_due']);
        }
        return $duedate;
      }
      /**
       * @static
       *
       * @param $debtor_id
       *
       * @return \ADV\Core\DB\Query\Result
       */
      public static function get_customer($debtor_id) {
        // Now check to ensure this account is not on hold */
        $sql
                = "SELECT debtors.name,
         debtors.address,
         credit_status.dissallow_invoices,
         debtors.sales_type AS salestype,
         debtors.dimension_id,
         debtors.dimension2_id,
         sales_types.sales_type,
         sales_types.tax_included,
         sales_types.factor,
         debtors.curr_code,
         debtors.discount,
         debtors.payment_discount,
         debtors.payment_terms
            FROM debtors, credit_status, sales_types
            WHERE debtors.sales_type=sales_types.id
            AND debtors.credit_status=credit_status.id
            AND debtors.debtor_id = " . DB::_escape($debtor_id);
        $result = DB::_query($sql, "Customer Record Retreive");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $debtor_id
       * @param $branch_id
       *
       * @return null|PDOStatement
       */
      public static function get_branch($debtor_id, $branch_id) {
        // the branch was also selected from the customer selection so default the delivery details from the customer branches table branches. The order process will ask for branch details later anyway
        $sql
          = "SELECT branches.br_name,
     branches.br_address,
     branches.city, branches.state, branches.postcode, branches.contact_name, branches.br_post_address, branches.phone, branches.email,
                 default_location, location_name, default_ship_via, tax_groups.name AS tax_group_name, tax_groups.id AS tax_group_id
                FROM branches, tax_groups, locations
                WHERE branches.tax_group_id = tax_groups.id
                    AND locations.loc_code=default_location
                    AND branches.branch_id=" . DB::_escape($branch_id) . "
                    AND branches.debtor_id = " . DB::_escape($debtor_id);
        return DB::_query($sql, "Customer Branch Record Retreive");
      }
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
        if (!$order->view_only && $session_order && $session_order->uniqueid != $order->uniqueid) {
          if (!$session_order->trans_no && count($session_order->line_items) > 0) {
            Event::warning(_('You were in the middle of creating a new order, this order has been continued. If you would like to start a completely new order, push the cancel changes button at the bottom of the page'
              )
            );
          } elseif ($session_order->trans_no) {
            Event::warning(_('You were previously editing this order in another tab, those changes have been applied to this tab'));
          }
          return $session_order;
        }
        return $order;
      }
      /**
       * @static
       *
       * @param $doc_type
       * @param $line_id
       * @param $qty_dispatched
       *
       * @return bool
       */
      public static function update_parent_line($doc_type, $line_id, $qty_dispatched) {
        $doc_type = Debtor_Trans::get_parent_type($doc_type);
        //	echo "update line: $line_id, $doc_type, $qty_dispatched";
        if ($doc_type == 0) {
          return false;
        } else {
          if ($doc_type == ST_SALESORDER) {
            $sql
              = "UPDATE sales_order_details
                                SET qty_sent = qty_sent + $qty_dispatched
                                WHERE id=" . DB::_escape($line_id);
          } else {
            $sql
              = "UPDATE debtor_trans_details
                                SET qty_done = qty_done + $qty_dispatched
                                WHERE id=" . DB::_escape($line_id);
          }
        }
        DB::_query($sql, "The parent document detail record could not be updated");
        return true;
      }
      /**
       * @param $order
       *
       * @return \Purch_Order|\Sales_Order
       */
      public static function copyToPost($order) {
        if (!Input::_get(Orders::QUOTE_TO_ORDER)) {
          $order = Sales_Order::check_edit_conflicts($order);
        }
        $_POST['ref']              = $order->reference;
        $_POST['Comments']         = $order->Comments;
        $_POST['OrderDate']        = $order->document_date;
        $_POST['delivery_date']    = $order->due_date;
        $_POST['cust_ref']         = $order->cust_ref;
        $_POST['freight_cost']     = Num::_priceFormat($order->freight_cost);
        $_POST['deliver_to']       = $order->deliver_to;
        $_POST['delivery_address'] = $order->delivery_address;
        $_POST['name']             = $order->name;
        $_POST['customer']         = $order->customer_name;
        $_POST['phone']            = $order->phone;
        $_POST['location']         = $order->location;
        $_POST['ship_via']         = $order->ship_via;
        $_POST['debtor_id']        = $order->debtor_id;
        $_POST['branch_id']        = $order->Branch;
        $_POST['sales_type']       = $order->sales_type;
        $_POST['salesman']         = $order->salesman;
        if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
          $_POST['dimension_id']  = $order->dimension_id;
          $_POST['dimension2_id'] = $order->dimension2_id;
        }
        $_POST['order_id'] = $order->order_id;
        return Orders::session_set($order);
      }
      /**
       * @param $order
       */
      public static function copyFromPost($order) {
        $order->reference        = $_POST['ref'];
        $order->Comments         = $_POST['Comments'];
        $order->document_date    = $_POST['OrderDate'];
        $order->due_date         = $_POST['delivery_date'];
        $order->cust_ref         = $_POST['cust_ref'];
        $order->freight_cost     = Validation::input_num('freight_cost');
        $order->deliver_to       = $_POST['deliver_to'];
        $order->delivery_address = $_POST['delivery_address'];
        $order->name             = $_POST['name'];
        $order->customer_name    = Input::_post('customer', Input::STRING);
        $order->phone            = $_POST['phone'];
        $order->location         = $_POST['location'];
        $order->ship_via         = $_POST['ship_via'];
        if (isset($_POST['email'])) {
          $order->email = $_POST['email'];
        } else {
          $order->email = '';
        }
        if (isset($_POST['salesman'])) {
          $order->salesman = $_POST['salesman'];
        }
        $order->debtor_id  = $_POST['debtor_id'];
        $order->Branch     = $_POST['branch_id'];
        $order->sales_type = $_POST['sales_type'];
        // POS
        if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
          $order->dimension_id  = $_POST['dimension_id'];
          $order->dimension2_id = $_POST['dimension2_id'];
        }
      }
    }

    /* end of class defintion */

