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
  use ADV\App\Pager\Pager;
  use ADV\App\Form\Form;
  use ADV\App\Item\Reorder;

  /**
   * @property \ADV\App\Item\Reorder $object
   */
  class Reorders extends \ADV\App\Controller\Pager
  {
    protected $stockid = null;
    protected $security = SA_REORDER;
    protected $frame = false;
    protected $tableWidth = '90';
    /** @var Form */
    protected $form;
    protected function before() {
      $this->object  = new \ADV\App\Item\Reorder();
      $this->stockid = $this->Input->getPostGlobal('stockid');
      if ($this->stockid) {
        $this->object->stockid = $this->stockid;
      }
      $this->runPost();
    }
    protected function runPost() {
      if (REQUEST_POST && $this->Input->post('_form_id') == 'reorder_levels_form') {
        $locations = $this->Input->post('location');
        foreach ($locations as $loc) {
          $loc['stockid']=$this->stockid;
          $location = new Reorder($loc);
            $location->save();
        }
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
                      'value'   => '',
                      'focus'   => true,
                      ]
        );
        $this->Session->setGlobal('stockid', $this->stockid);
        echo "</div>";
      }
    }
    /**
     * @param $pagername
     *
     * @return mixed
     */
    protected function getTableRows($pagername) {
      return $this->object->getAll($this->stockid);
    }
    /**
     * @param \ADV\App\Pager\Edit $pager
     */
    protected function getEditing(\ADV\App\Pager\Edit $pager) {
      $pager->setObject($this->object);
      $this->object->stockid = $this->stockid;
    }
    /**
     * @return array
     */
    public function getPagerColumns() {
      return [
        ['type' => 'skip'],
        ['type' => 'skip'],
        _("Location")     => ['fun' => [$this, 'formatLocation']],
        ['type' => 'skip'],
        ['type' => 'skip'],
        "Primary Shelf"   => ['fun' => [$this, 'formatPrimaryShelf']],
        "Secondary Shelf" => ['fun' => [$this, 'formatSecondaryShelf']],
        "Reorder Level"   => ['fun' => [$this, 'formatReorderLevel']],
      ];
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Field
     */
    public function formatLocation($row) {
      return $row['id'] . $this->form->hidden('location[' . $row['id'] . '][loc_code]')->initial($row['loc_code']) ;
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Field
     */
    public function formatPrimaryShelf($row) {
      return $this->form->text('location[' . $row['id'] . '][shelf_primary]')->initial($row['shelf_primary']);
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Field
     */
    public function formatSecondaryShelf($row) {
      return $this->form->text('location[' . $row['id'] . '][shelf_secondary]')->initial($row['shelf_secondary']);
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Field
     */
    public function formatReorderLevel($row) {
      return $this->form->number('location[' . $row['id'] . '][reorder_level]', 0)->initial($row['reorder_level']);
    }
    protected function runValidation() {
      Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);
    }
    protected function index() {
      $this->beforeTable();
      $this->Ajax->start_div('reorder_table');
      $this->form = new Form();
      echo         $this->form->start('reorder_levels', strtolower(str_replace(['ADV\\Controllers', '\\'], ['', '/'], get_called_class())));
      echo  $this->form->hidden('stockid')->value($this->stockid);
      $this->generateTable();
      echo '<div class="pad10 center">';
      echo $this->form->submit(UPDATED, 'Update')->type(\ADV\App\Form\Button::PRIMARY);
      echo '</div>';
      echo $this->form->end();
      $this->Ajax->end_div();
    }
    /**
     * @return \ADV\App\Pager\Pager
     */
    protected function generateTable() {
      $cols       = $this->getPagerColumns();
      $pager_name = end(explode('\\', ltrim(get_called_class(), '\\'))) . '_table';
      Pager::kill($pager_name);
      $table = Pager::newPager($pager_name, $cols);
      $table->setData($this->getTableRows($pager_name));
      $table->width = $this->tableWidth;
      $table->display();
    }
  }
