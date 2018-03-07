<?php
  namespace ADV\Controllers\Gl\Manage;

  use ADV\App\Inv\Location;
  use ADV\App\Pager\Edit;
  use ADV\App\GL\ChartClass;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Classes extends \ADV\App\Controller\InlinePager
  {
    protected $tableWidth = '90';
    protected $security = SA_GLACCOUNTCLASS;
    protected function before() {
      $this->object = new ChartClass();
      $this->runPost();
    }
    /**
     * @param $pager_name
     *
     * @return mixed
     */
    protected function getTableRows($pager_name) {
      $inactive = $this->getShowInactive($pager_name);
      return $this->object->getAll($inactive);
    }
    /**
     * @param \ADV\App\Pager\Edit $pager
     */
    public function getEditing(Edit $pager) {
      $pager->setObject($this->object);
    }
  }
