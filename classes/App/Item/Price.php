<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Item {
    use Bank_Currency;
    use ADV\App\Sales\Type;
    use GL_Currency;
    use ADV\App\Form\Form;
    use ADV\App\Item\Item;
    use ADV\App\Validation;
    use ADV\App\User;

    /**
     *
     */
    class Price extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {
      const PURCHASE    = 1;
      const SALE        = 2;
      const SORT_UPDATE = 'last_update';
      const SORT_PRICE  = 'price';
      const SORT_CODE   = 'stock_id';
      /**
       * @var
       */
      public $stockid;
      /**
       * @var
       */
      protected $_type;
      protected $_table = 'prices';
      protected $_classname = 'Price';
      protected $_id_column = 'id';
      public $id = 0;
      public $item_code_id = 0;
      public $stock_id;
      public $sales_type_id = 1;
      public $curr_abrev;
      public $price = 0.0000;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (!Validation::is_num($this->item_code_id, 0)) {
          $this->item_code_id = Item::getStockID($this->stock_id);
          if (!$this->item_code_id) {
            return $this->status(false, 'Item_code_id must be a number', 'item_code_id');
          }
        }
        if (strlen($this->stock_id) > 20) {
          return $this->status(false, 'Stock_id must be not be longer than 20 characters!', 'stock_id');
        }
        if (strlen($this->curr_abrev) > 3) {
          return $this->status(false, 'Curr_abrev must be not be longer than 3 characters!', 'curr_abrev');
        }
        $this->price = User::_numeric($this->price);
        if (!Validation::is_num($this->price, 0)) {
          return $this->status(false, 'Price must be a number', 'price');
        }
        return true;
      }
      protected function defaults() {
        parent::defaults();
        $this->curr_abrev = Bank_Currency::for_company();
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        $cols = [
          ['type' => 'hidden'],
          ['type' => 'skip'],
          'Type'     => ['fun' => [$this, 'formatType'], 'type' => 'select', 'items' => Type::selectBoxItems()],
          ['type' => 'hidden'],
          ['type' => 'hidden'],
          'Currency' => ['edit' => [$this, 'formatCurrencyEdit']],
          'Price'    => ['type' => 'amount'],
        ];
        return $cols;
      }
      /**
       * @param $row
       *
       * @return mixed
       */
      public function formatType($row) {
        return $row['sales_type'];
      }
      /**
       * @param \ADV\App\Form\Form $form
       *
       * @return \ADV\App\Form\Field
       */
      public function formatCurrencyEdit(Form $form) {
        return $form->custom(GL_Currency::select('curr_abrev'));
      }
    }
  }
  namespace {
    use ADV\Core\DB\DB;
    use ADV\App\SysTypes;
    use ADV\App\Item\Item;
    use ADV\Core\Input\Input;
    use ADV\App\User;
    use ADV\Core\Num;
    use ADV\App\Dates;
    use ADV\Core\Event;

    /**

     */
    class Item_Price
    {
      const PURCHASE    = 1;
      const SALE        = 2;
      const SORT_UPDATE = 'last_update';
      const SORT_PRICE  = 'price';
      const SORT_CODE   = 'stock_id';
      /**
       * @static
       *
       * @param        $stockid
       * @param int    $type
       * @param string $sort
       *
       * @return array
       */
      public static function getPrices($stockid, $type = self::SALE, $sort = self::SORT_PRICE) {
        switch ($type) {
          case self::PURCHASE:
            $result = DB::_select()->from('purch_data')->where('stockid=', $stockid)->orderby($sort)->fetch()->asClassLate('Item_Price', array(self::PURCHASE))->all();
            break;
          case self::SALE:
            $result = DB::_select()->from('prices')->where('stockid=', $stockid)->orderby($sort)->fetch()->asClassLate('Item_Price', array(self::SALE))->all();
            break;
          default:
            $result = [];
            Event::error('Could not retrieve prices for item');
        }
        if ($sort != self::SORT_CODE) {
          $result = array_reverse($result);
        }
        return $result;
      }
      /**
       * @static
       *
       * @param $stockid
       * @param $supplierid
       *
       * @return mixed
       */
      public static function getPriceBySupplier($stockid, $supplierid) {
        $result = DB::_select()->from('purch_data')->where('stockid=', $stockid)->andWhere('creditor_id=', $supplierid)->fetch()->one();
        return $result;
      }
      /**
       * @static
       *
       * @param      $stock_id
       * @param      $sales_type_id
       * @param      $curr_abrev
       * @param      $price
       * @param null $item_code_id
       *
       * @return bool
       */
      public static function add($stock_id, $sales_type_id, $curr_abrev, $price, $item_code_id = null) {
        if ($item_code_id == null) {
          $item_code_id = Item_Code::get_id($stock_id);
        }
        $sql
          = "INSERT INTO prices (item_code_id, stock_id, sales_type_id, curr_abrev, price)
            VALUES (" . DB::_escape($item_code_id) . ", " . DB::_escape($stock_id) . ", " . DB::_escape($sales_type_id) . ", " . DB::_escape($curr_abrev) . ", " . DB::_escape(
                                                                                                                                                                     $price
          ) . ")";
        try {
          DB::_query($sql, "an item price could not be added");
          return true;
        } catch (\ADV\Core\DB\DBDuplicateException $e) {
          Event::error('A price already exists for this sales type.');
          return false;
        }
      }
      /**
       * @static
       *
       * @param $price_id
       * @param $sales_type_id
       * @param $curr_abrev
       * @param $price
       */
      public static function update($price_id, $sales_type_id, $curr_abrev, $price) {
        $sql = "UPDATE prices SET sales_type_id=" . DB::_escape($sales_type_id) . ",
            curr_abrev=" . DB::_escape($curr_abrev) . ",
            price=" . DB::_escape($price) . " WHERE id=" . DB::_escape($price_id);
        DB::_query($sql, "an item price could not be updated");
      }
      /**
       * @static
       *
       * @param $stock_id
       *
       * @return null|PDOStatement
       */
      public static function getAll($stock_id) {
        $sql
          = "SELECT prices.id, sales_types.sales_type, prices.sales_type_id,prices.stock_id,
				 prices.item_code_id, prices.item_code_id, prices.curr_abrev,prices.price FROM
				 prices,
				 sales_types
            WHERE prices.sales_type_id = sales_types.id
            AND stock_id=" . DB::_quote($stock_id) . " ORDER BY curr_abrev, sales_type_id";
        return DB::_query($sql, "item prices could not be retreived")->fetchAll(PDO::FETCH_ASSOC);
      }
      /**
       * @static
       *
       * @param $price_id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($price_id) {
        $sql    = "SELECT * FROM prices WHERE id=" . DB::_escape($price_id);
        $result = DB::_query($sql, "price could not be retreived");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $stock_id
       *
       * @return mixed
       */
      public static function get_standard_cost($stock_id) {
        $sql    = "SELECT IF(s.mb_flag='" . STOCK_SERVICE . "', 0, material_cost + labour_cost + overhead_cost) AS std_cost
                FROM stock_master s WHERE stock_id=" . DB::_escape($stock_id);
        $result = DB::_query($sql, "The standard cost cannot be retrieved");
        $myrow  = DB::_fetchRow($result);
        return $myrow[0];
      }
      /**
       * @static
       *
       * @param $stock_id
       * @param $add_pct
       *
       * @return float|int
       */
      public static function get_percent($stock_id, $add_pct) {
        $avg = static::get_standard_cost($stock_id);
        if ($avg == 0) {
          return 0;
        }
        return Num::_round($avg * (1 + $add_pct / 100), User::_price_dec());
      }
      /**
       * @static
       *
       * @param      $stock_id
       * @param      $currency
       * @param      $sales_type_id
       * @param null $factor
       * @param null $date
       *
       * @return float|int
       */
      public static function get_calculated_price($stock_id, $currency, $sales_type_id, $factor = null, $date = null) {
        if ($date == null) {
          $date = Dates::_newDocDate();
        }
        if ($factor === null) {
          $myrow  = Sales_Type::get($sales_type_id);
          $factor = $myrow['factor'];
        }
        $add_pct   = DB_Company::_get_pref('add_pct');
        $base_id   = DB_Company::_get_base_sales_type();
        $home_curr = Bank_Currency::for_company();


        //	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
        $sql
                  = "SELECT price, curr_abrev, sales_type_id
            FROM prices
            WHERE stock_id = " . DB::_escape($stock_id) . "
                AND (curr_abrev = " . DB::_escape($currency) . " OR curr_abrev = " . DB::_escape($home_curr) . ")";
        $result   = DB::_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
        $num_rows = DB::_numRows($result);
        $rate     = Num::_round(Bank_Currency::exchange_rate_from_home($currency, $date), User::_exrate_dec());
        $round_to = DB_Company::_get_pref('round_to');
        $prices   = [];
        while ($myrow = DB::_fetch($result)) {
          var_dump($myrow);
          $prices[$myrow['sales_type_id']][$myrow['curr_abrev']] = $myrow['price'];
        }
        $price = false;
        if (isset($prices[$sales_type_id][$currency])) {
          $price = $prices[$sales_type_id][$currency];
        } elseif (isset($prices[$base_id][$currency])) {
          $price = $prices[$base_id][$currency] * $factor;
        } elseif (isset($prices[$sales_type_id][$home_curr])) {
          $price = $prices[$sales_type_id][$home_curr] / $rate;
        } elseif (isset($prices[$base_id][$home_curr])) {
          $price = $prices[$base_id][$home_curr] * $factor / $rate;
        } /*
                                 if (isset($prices[$sales_type_id][$home_curr])) {
                                     $price = $prices[$sales_type_id][$home_curr] / $rate;
                                 } elseif (isset($prices[$base_id][$currency]))
                                 {
                                     $price = $prices[$base_id][$currency] * $factor;
                                 } elseif (isset($prices[$base_id][$home_curr]))
                                 {
                                     $price = $prices[$base_id][$home_curr] * $factor / $rate;
                                 }
                             */ elseif ($num_rows == 0 && $add_pct != -1) {
          $price = static::get_percent($stock_id, $add_pct);
          if ($currency != $home_curr) {
            $price /= $rate;
          }
          if ($factor != 0) {
            $price *= $factor;
          }
        }
        if ($price === false) {
          return 0;
        } elseif ($round_to != 1) {
          return Num::_toNearestCents($price, $round_to);
        } else {
          return Num::_round($price, User::_price_dec());
        }
      }
      /***
       *  Get price for given item or kit.
       * When $std==true price is calculated as a sum of all included stock items,
       *  otherwise all prices set for kits and items are accepted.
       *
       * @param      $item_code
       * @param      $currency
       * @param      $sales_type_id
       * @param null $factor
       * @param null $date
       * @param bool $std
       *
       * @return float|int
       */
      public static function get_kit($item_code, $currency, $sales_type_id, $factor = null, $date = null, $std = false) {
        $kit_price = 0.00;
        if (!$std) {
          $kit_price = static::get_calculated_price($item_code, $currency, $sales_type_id, $factor, $date);

          if ($kit_price !== false) {
            return $kit_price;
          }
        }
        // no price for kit found, get total value of all items
        $kit = Item_Code::get_kit($item_code);
        while ($item = DB::_fetch($kit)) {
          if ($item['item_code'] != $item['stock_id']) {
            // foreign/kit code
            $kit_price += $item['quantity'] * static::get_kit($item['stock_id'], $currency, $sales_type_id, $factor, $date, $std);
          } else {
            // stock item
            $kit_price += $item['quantity'] * static::get_calculated_price($item['stock_id'], $currency, $sales_type_id, $factor, $date);
          }
        }
        return $kit_price;
      }
      /**
       * @static
       *
       * @param $creditor_id
       * @param $stock_id
       *
       * @return float|int
       */
      public static function get_purchase($creditor_id, $stock_id) {
        $sql
                = "SELECT price, conversion_factor FROM purch_data
                WHERE creditor_id = " . DB::_escape($creditor_id) . "
                AND stock_id = " . DB::_escape($stock_id);
        $result = DB::_query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");
        if (DB::_numRows($result) == 1) {
          $myrow = DB::_fetch($result);
          return $myrow["price"] / $myrow['conversion_factor'];
        } else {
          return 0;
        }
      }
      /**
       * @static
       *
       * @param $stock_id
       * @param $material_cost
       * @param $labour_cost
       * @param $overhead_cost
       * @param $last_cost
       *
       * @return int
       */
      public static function update_cost($stock_id, $material_cost, $labour_cost, $overhead_cost, $last_cost) {
        if (Input::_post('mb_flag') == STOCK_SERVICE) {
          Event::error("Cannot do cost update for Service item : $stock_id", "");
        }
        $update_no = -1;
        DB::_begin();
        $sql = "UPDATE stock_master SET material_cost=" . DB::_escape($material_cost) . ",
                        labour_cost=" . DB::_escape($labour_cost) . ",
                        overhead_cost=" . DB::_escape($overhead_cost) . ",
                        last_cost=" . DB::_escape($last_cost) . "
                        WHERE stock_id=" . DB::_escape($stock_id);
        DB::_query($sql, "The cost details for the inventory item could not be updated");
        $qoh   = Item::get_qoh_on_date($_POST['stock_id']);
        $date_ = Dates::_today();
        if ($qoh > 0) {
          $update_no = SysTypes::get_next_trans_no(ST_COSTUPDATE);
          if (!Dates::_isDateInFiscalYear($date_)) {
            $date_ = Dates::_endFiscalYear();
          }
          $stock_gl_code   = Item::get_gl_code($stock_id);
          $new_cost        = $material_cost + $labour_cost + $overhead_cost;
          $value_of_change = $qoh * ($new_cost - $last_cost);
          $memo_           = "Cost was " . $last_cost . " changed to " . $new_cost . " x quantity on hand of $qoh";
          GL_Trans::add_std_cost(
                  ST_COSTUPDATE, $update_no, $date_, $stock_gl_code["adjustment_account"], $stock_gl_code["dimension_id"], $stock_gl_code["dimension2_id"], $memo_, (-$value_of_change)
          );
          GL_Trans::add_std_cost(ST_COSTUPDATE, $update_no, $date_, $stock_gl_code["inventory_account"], 0, 0, $memo_, $value_of_change);
        }
        DB_AuditTrail::add(ST_COSTUPDATE, $update_no, $date_);
        DB::_commit();
        return $update_no;
      }
      /**
       * @static
       *
       * @param     $amount
       * @param int $document
       *
       * @return string
       */
      public static function toWords($amount, $document = 0) {
        // Only usefor Remittance and Receipts as default
        if (!($document == ST_SUPPAYMENT || $document == ST_CUSTPAYMENT || $document == ST_CUSTREFUND || $document == ST_CHEQUE)) {
          return "";
        }
        if ($amount < 0 || $amount > 999999999999) {
          return "";
        }
        $dec = User::_price_dec();
        if ($dec > 0) {
          $divisor = pow(10, $dec);
          $frac    = Num::_round($amount - floor($amount), $dec) * $divisor;
          $frac    = sprintf("%0{$dec}d", $frac);
          $and     = _("and");
          $frac    = " $and $frac/$divisor";
        } else {
          $frac = "";
        }
        return Num::_toWords(intval($amount)) . $frac;
      }
    }
  }
