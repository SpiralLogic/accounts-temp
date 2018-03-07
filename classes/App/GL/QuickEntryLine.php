<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      17/10/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\GL;

  use ADV\Core\DB\DB;
  use ADV\App\Validation;

  /** **/
  class QuickEntryLine extends \ADV\App\DB\Base
  {
    protected $_table = 'quick_entry_lines';
    protected $_classname = 'Quick Entry Line';
    protected $_id_column = 'id';
    public $id = 0;
    public $qid;
    public $amount = 0.0000;
    public $action;
    public $dest_id;
    public $dimension_id;
    public $dimension2_id;
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function canProcess() {
      if (!Validation::is_num($this->amount, null)) {
        return $this->status(false, 'Amount must be a number', 'amount');
      }
      if (strlen($this->action) > 2) {
        return $this->status(false, 'Action must be not be longer than 2 characters!', 'action');
      }
      if (strlen($this->dest_id) > 11) {
        return $this->status(false, 'Dest Id must be not be longer than 11 characters!', 'dest_id');
      }
      return true;
    }
    /**
     * @return array
     */
    public static function getAll() {
      $q = DB::_select('id', 'qid', 'action', 'account_name', 'dest_id', 'amount')->from('quick_entry_lines', 'chart_master')->where('account_code=dest_id');
      return $q->fetch()->all();
    }
  }
