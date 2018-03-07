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
  use ADV\Core\JS;
  use ADV\Core\Ajax;
  use ADV\App\WO\WO;
  use ADV\Core\Event;

  /**
   *
   */
  class Item_Line
  {
    /** @var */
    public $stock_id;
    /**
     * @var null
     */
    public $description;
    /** @var */
    public $units;
    /** @var */
    public $mb_flag;
    /** @var */
    public $quantity;
    /**
     * @var int
     */
    public $price;
    /**
     * @var null
     */
    public $standard_cost;
    /**
     * @param      $stock_id
     * @param      $qty
     * @param null $standard_cost
     * @param null $description
     */
    public function __construct($stock_id, $qty, $standard_cost = null, $description = null) {
      $item_row = Item::get($stock_id);
      if ($item_row == null) {
        Event::error("invalid item added to order : $stock_id", "");
      }
      $this->mb_flag = $item_row["mb_flag"];
      $this->units   = $item_row["units"];
      if ($description == null) {
        $this->description = $item_row["description"];
      } else {
        $this->description = $description;
      }
      if ($standard_cost == null) {
        $this->standard_cost = $item_row["actual_cost"];
      } else {
        $this->standard_cost = $standard_cost;
      }
      $this->stock_id = $stock_id;
      $this->quantity = $qty;
      //$this->price = $price;
      $this->price = 0;
    }
    /**
     * @param $location
     * @param $date_
     * @param $reverse
     *
     * @return Item_Line|null
     */
    public function check_qoh($location, $date_, $reverse) {
      if (!DB_Company::_get_pref('allow_negative_stock')) {
        if (WO::has_stock_holding($this->mb_flag)) {
          $quantity = $this->quantity;
          if ($reverse) {
            $quantity = -$this->quantity;
          }
          if ($quantity >= 0) {
            return null;
          }
          $qoh = Item::get_qoh_on_date($this->stock_id, $location, $date_);
          if ($quantity + $qoh < 0) {
            return $this;
          }
        }
      }
      return null;
    }
    /**
     * @param $field
     */
    public static function start_focus($field) {
      Ajax::_activate('items_table');
      JS::_setFocus($field);
    }
  }

