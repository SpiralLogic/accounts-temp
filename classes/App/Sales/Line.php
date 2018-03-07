<?php
  use ADV\App\Item\Item;
  use ADV\Core\Event;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Sales_Line extends \Sales_Order {
    /** @var int **/
    public $id;
    /** @var */
    public $stock_id;
    /** @var */
    public $description;
    /** @var */
    public $units;
    /** @var */
    public $mb_flag;
    /** @var */
    public $tax_type;
    /** @var */
    public $tax_type_name;
    /** @var int **/
    public $src_no; // number of src doc for this line
    /** @var */
    public $src_id;
    /** @var array|int **/
    public $quantity;
    /** @var bool **/
    public $price;
    /** @var */
    public $discount_percent;
    /** @var */
    public $qty_done; // quantity processed on child documents
    /** @var array|int **/
    public $qty_dispatched; // quantity selected to process
    /** @var int **/
    public $qty_old = 0; // quantity dispatched before edition
    /** @var */
    public $standard_cost;
    /**
     * @param           $stock_id
     * @param array|int $qty
     * @param bool      $prc
     * @param           $disc_percent
     * @param           $qty_done
     * @param           $standard_cost
     * @param           $description
     * @param int       $id
     * @param int       $src_no
     */
    function __construct($stock_id, $qty, $prc, $disc_percent, $qty_done, $standard_cost, $description, $id = 0, $src_no = 0) {
      /* Constructor function to add a new LineDetail object with passed params */
      $this->id     = $id;
      $this->src_no = $src_no;
      $item_row     = Item::get($stock_id);
      if ($item_row == null) {
        Event::error("invalid item added to order : $stock_id", "");
      }
      $this->mb_flag = $item_row["mb_flag"];
      $this->units   = $item_row["units"];
      if ($description == null) {
        $this->description = $item_row["long_description"];
      } else {
        $this->description = $description;
      }
      //$this->standard_cost = $item_row["material_cost"] + $item_row["labour_cost"] + $item_row["overhead_cost"];
      $this->tax_type         = $item_row["tax_type_id"];
      $this->tax_type_name    = $item_row["tax_type_name"];
      $this->stock_id         = $stock_id;
      $this->quantity         = $qty;
      $this->qty_dispatched   = $qty;
      $this->price            = $prc;
      $this->discount_percent = $disc_percent;
      $this->qty_done         = $qty_done;
      $this->standard_cost    = $standard_cost;
    }
    // get unit price as stated on document
    /**
     * @return bool
     */
    function line_price() {
      return $this->price;
    }
  }
