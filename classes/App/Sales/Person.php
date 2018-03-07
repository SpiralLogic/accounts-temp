<?php

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      1/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Sales;

  use ADV\App\Validation;
  use ADV\Core\DB\DB;
  use ADV\Core\Num;

  /** **/
  class Person extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
  {
    protected $_id_column = 'salesman_code';
    protected $_table = 'salesman';
    protected $_classname = 'Sales Person';
    public $salesman_code;
    public $salesman_name;
    public $user_id;
    public $salesman_phone;
    public $salesman_fax;
    public $salesman_email;
    public $inactive = 0;
    public $provision = 0;
    public $break_pt = 0;
    public $provision2 = 0;
    /**
     * @param int   $id
     * @param array $extra
     */
    public function __construct($id = 0, $extra = []) {
      parent::__construct($id, $extra);
    }
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    public function delete() {
      $result = $this->DB->select("COUNT(*) as count")->from('branches')->where('salesman=', $this->id)->fetch()->one('count');
      if ($result > 0) {
        return $this->status(false, "Cannot delete this sales-person because branches are set up referring to this sales-person - first alter the branches concerned.");
      }
      return parent::delete();
    }
    /**
     * @return bool
     */
    protected function canProcess() {
      if ($this->user_id == -1) {
        $this->user_id = null;
      }
      if (strlen($this->salesman_name) == 0) {
        return $this->status(false, "The sales person name *cannot be empty.", 'salesman_name');
      }
      if (!Validation::is_num($this->provision, 0, 100)) {
        return $this->status(false, 'Provisions needs to be a number and not less than 0', 'provision');
      }
      if (!Validation::is_num($this->break_pt, 0, $this->provision)) {
        return $this->status(false, 'Break point needs to be a number and not less than 0 and no greater than inital provision', 'break_pt');
      }
      if (!Validation::is_num($this->provision2, 0, $this->break_pt)) {
        return $this->status(false, 'Provisions 2 needs to be a number and not less than 0 and greater than break point', 'provision2');
      }
      return true;
    }
    /**
     * @param int|null $id
     * @param array    $extra
     *
     * @return bool|void
     */
    protected function read($id, $extra) {
      parent::read($id, $extra);
      $this->provision  = Num::_percentFormat($this->provision);
      $this->break_pt   = Num::_priceFormat($this->break_pt);
      $this->provision2 = Num::_percentFormat($this->provision2);
    }
    /**
     * @param bool $inactive
     *
     * @return mixed
     */
    public static function getAll($inactive = false) {
      $sql = "SELECT s.salesman_code as id,s.*,u.user_id FROM salesman s, users u WHERE s.user_id=u.id";
      if (!$inactive) {
        $sql .= " AND !s.inactive";
      }
      DB::_query($sql, 'Could not fetch sales people');
      return DB::_fetchAll();
    }
    /**
     * @return array
     */
    public function getPagerColumns() {
      $cols = array(
        _("ID"),
        ['type' => "skip"],
        _("Name"),
        _("User"),
        _("Phone"),
        _("Fax"),
        _("Email"),
        _("Provision"),
        _("Break Pt."),
        _("Provision") . " 2",
        _('Inactive') => ['type' => 'inactive'],
      );
      return $cols;
    }
  }
