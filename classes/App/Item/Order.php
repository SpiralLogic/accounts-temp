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
  use ADV\Core\Event;

  /**
   *
   */
  class   Item_Order
  {
    /** @var */
    public $trans_type;
    /** @var Item_Line[] */
    public $line_items;
    /** @var */
    public $gl_items;
    /** @var */
    public $order_id;
    /** @var */
    /** @var */
    public $editing_item, $deleting_item;
    /** @var */
    public $from_loc;
    /** @var */
    public $to_loc;
    /** @var */
    public $tran_date;
    /** @var */
    public $transfer_type;
    /** @var */
    public $increase;
    /** @var */
    public $memo_;
    /** @var */
    public $person_id;
    /** @var */
    public $branch_id;
    /** @var */
    public $reference;
    /**
     * @param $type
     */
    public function __construct($type) {
      $this->trans_type = $type;
      $this->clear_items();
    }
    /**
     * @param      $line_no
     * @param      $stock_id
     * @param      $qty
     * @param      $standard_cost
     * @param null $description
     *
     * @return bool
     */
    public function add_to_order($line_no, $stock_id, $qty, $standard_cost, $description = null) {
      if (isset($stock_id) && $stock_id != "" && isset($qty)) {
        $this->line_items[$line_no] = new Item_Line($stock_id, $qty, $standard_cost, $description);
        return true;
      } else {
        // shouldn't come here under normal circumstances
        Event::error("unexpected - adding an invalid item or null quantity", "", true);
      }
      return false;
    }
    /**
     * @param $stock_id
     *
     * @return null
     */
    public function find_order_item($stock_id) {
      foreach ($this->line_items as $line_no => $line) {
        if ($line->stock_id == $stock_id) {
          return $this->line_items[$line_no];
        }
      }
      return null;
    }
    /**
     * @param $line_no
     * @param $qty
     * @param $standard_cost
     */
    public function update_order_item($line_no, $qty, $standard_cost) {
      $this->line_items[$line_no]->quantity      = $qty;
      $this->line_items[$line_no]->standard_cost = $standard_cost;
    }
    /**
     * @param $line_no
     */
    public function remove_from_order($line_no) {
      array_splice($this->line_items, $line_no, 1);
    }
    /**
     * @return int|void
     */
    public function count_items() {
      return count($this->line_items);
    }
    /**
     * @param      $location
     * @param      $date_
     * @param bool $reverse
     *
     * @return int|string
     */
    public function check_qoh($location, $date_, $reverse = false) {
      foreach ($this->line_items as $line_no => $line_item) {
        $item_ret = $line_item->check_qoh($location, $date_, $reverse);
        if ($item_ret != null) {
          return $line_no;
        }
      }
      return -1;
    }
    /**
     * @param      $code_id
     * @param      $dimension_id
     * @param      $dimension2_id
     * @param      $amount
     * @param      $reference
     * @param null $description
     *
     * @return bool
     */
    public function add_gl_item($code_id, $dimension_id, $dimension2_id, $amount, $reference, $description = null) {
      if (isset($code_id) && $code_id != "" && isset($amount)) {
        $this->gl_items[] = new Item_GL($code_id, $dimension_id, $dimension2_id, $amount, $reference, $description);
        return true;
      } else {
        // shouldn't come here under normal circumstances
        Event::error("unexpected - invalid parameters in add_gl_item($code_id, $dimension_id, $dimension2_id, $amount,...)", "", true);
      }
      return false;
    }
    /**
     * @param      $index
     * @param      $code_id
     * @param      $dimension_id
     * @param      $dimension2_id
     * @param      $amount
     * @param      $reference
     * @param null $description
     */
    public function update_gl_item($index, $code_id, $dimension_id, $dimension2_id, $amount, $reference, $description = null) {
      $this->gl_items[$index]->code_id       = $code_id;
      $this->gl_items[$index]->dimension_id  = $dimension_id;
      $this->gl_items[$index]->dimension2_id = $dimension2_id;
      $this->gl_items[$index]->amount        = $amount;
      $this->gl_items[$index]->reference     = $reference;
      if ($description == null) {
        $this->gl_items[$index]->description = GL_Account::get_name($code_id);
      } else {
        $this->gl_items[$index]->description = $description;
      }
    }
    /**
     * @param $index
     */
    public function remove_gl_item($index) {
      array_splice($this->gl_items, $index, 1);
    }
    /**
     * @return int|void
     */
    public function count_gl_items() {
      return count($this->gl_items);
    }
    /**
     * @return int
     */
    public function gl_items_total() {
      $total = 0;
      foreach ($this->gl_items as $gl_item) {
        $total += $gl_item->amount;
      }
      return $total;
    }
    /**
     * @return int
     */
    public function gl_items_total_debit() {
      $total = 0;
      foreach ($this->gl_items as $gl_item) {
        if ($gl_item->amount > 0) {
          $total += $gl_item->amount;
        }
      }
      return $total;
    }
    /**
     * @return int
     */
    public function gl_items_total_credit() {
      $total = 0;
      foreach ($this->gl_items as $gl_item) {
        if ($gl_item->amount < 0) {
          $total += $gl_item->amount;
        }
      }
      return $total;
    }
    public function clear_items() {
      unset($this->line_items);
      $this->line_items = [];
      unset($this->gl_items);
      $this->gl_items = [];
    }
    /**
     * @static
     *
     * @param Item_Order $order
     * @param $new_item
     * @param $new_item_qty
     * @param $standard_cost
     */
    public static function add_line($order, $new_item, $new_item_qty, $standard_cost) {
      if ($order->find_order_item($new_item)) {
        Event::error(_("For Part: '") . $new_item . "' This item is already on this order. You can change the quantity ordered of the existing line if necessary.");
      } else {
        $order->add_to_order(count($order->line_items), $new_item, $new_item_qty, $standard_cost);
      }
    }
  }

