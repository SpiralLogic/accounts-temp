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

  use ADV\App\Item\Price;
  use Item_Price;
  use ADV\App\UI;
  use ADV\App\Validation;

  /**
   * @property \ADV\App\Item\Price $object
   */
  class Prices extends \ADV\App\Controller\InlinePager
  {
    protected $stock_id;
    protected $security = SA_SALESPRICE;
    protected function before() {
      $this->stock_id         = $this->Input->getPostGlobal('stock_id');
      $this->object           = new Price();
      $this->object->stock_id = $this->stock_id;
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
     * @return array
     */
    protected function getTableRows($pagername) {
      return Item_Price::getAll($this->stock_id);
    }
    /**
     * @param \ADV\App\Pager\Edit $pager
     */
    protected function getEditing(\ADV\App\Pager\Edit $pager) {
      $pager->setObject($this->object);
      $this->object->stock_id = $this->stock_id;
    }
    protected function generateTable() {
      $this->Ajax->start_div('prices_table');
      parent::generateTable();
      if ($this->Input->post(FORM_CONTROL) == 'stock_id') {
        $this->Ajax->activate('prices_table');
      }
      $this->Ajax->end_div();
    }
    protected function runValidation() {
      Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
      Validation::check(Validation::SALES_TYPES, _("There are no sales types in the system. Please set up sales types befor entering pricing."));
    }
  }

