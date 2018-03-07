<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Validation;
  use ADV\App\UI;
  use ADV\App\Item\Purchase;

  /**
   * @property \ADV\App\Item\Purchase $object
   */
  class Purchasing extends \ADV\App\Controller\InlinePager
  {
    protected $stock_id = null;
    protected $security = SA_PURCHASEPRICING;
    protected $tableWidth = '90';
    protected function before() {
      $this->stock_id = $this->Input->getPostGlobal('stock_id');
      $this->object   = new Purchase();
      if ($this->stock_id) {
        $this->object->stock_id = $this->stock_id;
        $this->object->stockid  = \ADV\App\Item\Item::getStockID($this->stock_id);
      }
    }
    protected function beforeTable() {
      if (!$this->embedded) {
        echo "<div class='bold center pad10 margin20 font13'>";
        UI::search(
          'stock_id', [
                      'label'   => 'Item:',
                      'url'     => 'Item',
                      'idField' => 'stock_id',
                      'name'    => 'stock_id', //
                      'value'   => $this->stock_id,
                      'focus'   => true,
                      ]
        );
        $this->Session->setGlobal('stock_id', $this->stock_id);
        echo "</div>";
      }
    }
    /**
     * @param $pagername
     *
     * @return mixed
     */
    protected function getTableRows($pagername) {
      return $this->object->getAll($this->stock_id);
    }
    /**
     * @param \ADV\App\Pager\Edit $pager
     */
    protected function getEditing(\ADV\App\Pager\Edit $pager) {
      $pager->setObject($this->object);
      if (!$this->object->id) {
        $this->object->supplier_description = $this->stock_id;
      }
      if ($this->stock_id) {
        $this->object->stock_id = $this->stock_id;
        $this->object->stockid  = \ADV\App\Item\Item::getStockID($this->stock_id);
      }
    }
    protected function generateTable() {
      $this->Ajax->start_div('purchasing_table');
      parent::generateTable();
      if ($this->Input->post(FORM_CONTROL) == 'stock_id') {
        $this->Ajax->activate('purchasing_table');
      }
      $this->Ajax->end_div();
    }
    protected function runValidation() {
      Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
    }
  }
