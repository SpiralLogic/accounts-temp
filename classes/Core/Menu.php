<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /** **/
  class menu_item
  {
    /** @var */
    public $label;
    /** @var */
    public $link;
    /**
     * @param $label
     * @param $link
     */
    public function __construct($label, $link) {
      $this->label = $label;
      $this->link  = $link;
    }
  }

  /** **/
  class Menu
  {
    /** @var */
    public $title;
    /** @var array * */
    public $items = [];
    /**
     * @param $title
     */
    public function __construct($title) {
      $this->title = $title;
      $this->items;
    }
    /**
     * @param $label
     * @param $link
     *
     * @return menu_item|mixed
     */
    public function addItem($label, $link) {
      $item = new menu_item($label, $link);
      array_push($this->items, $item);
      return $item;
    }
  }
