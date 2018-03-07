<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      5/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Controller;

  use ADV\Core\Status;
  use InvalidArgumentException;
  use ADV\App\Pager\Edit;

  /** **/
  abstract class InlinePager extends \ADV\App\Controller\Pager
  {
    public $editing;
    protected function index() {
      $this->beforeTable();
      $this->generateTable();
    }
    protected function beforeTable() {
    }
    /**
     * @return \ADV\App\Pager\Pager
     */
    protected function generateTable() {
      $cols       = $this->getPagerColumns();
      $pager_name = end(explode('\\', ltrim(get_called_class(), '\\'))) . '_table';
      //Edit::kill($pager_name);
      $table = Edit::newPager($pager_name, $cols);
      $table->setActionURI(strtolower(str_replace(['ADV\\Controllers', '\\'], ['', '/'], get_called_class())));
      $this->getEditing($table);
      $table->setData($this->getTableRows($pager_name));
      $table->width = $this->tableWidth;
      $table->display();
    }
    /**
     * @param \ADV\App\Pager\Edit|\ADV\App\Pager\Pager $table
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function getEditing(Edit $table) {
      if (!$table->editing instanceof \ADV\App\DB\Base) {
        throw new InvalidArgumentException('Editing must be of type DB\Base');
      }
    }
  }
