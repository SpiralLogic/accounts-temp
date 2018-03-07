<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      8/04/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Sales;

  use ADV\Core\DB\DB;

  /** **/
  class Group extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
  {
    protected $_table = 'groups';
    protected $_classname = 'Group';
    protected $_id_column = 'id';
    public $id;
    public $description;
    public $inactive = 0;
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function canProcess() {
      if (strlen($this->description) > 60) {
        return $this->status(false, 'Description must be not be longer than 60 characters!', 'description');
      }
      return true;
    }
    /**
     * @param bool $inactive
     *
     * @return array
     */
    public static function getAll($inactive = false) {
      $q = DB::_select()->from('groups');
      if ($inactive) {
        $q->andWhere('inactive=', 1);
      }
      return $q->fetch()->all();
    }
    /**
     * @static
     *
     * @param $group_no
     *
     * @return mixed
     */
    public static function get_name($group_no) {
      $sql    = "SELECT description FROM groups WHERE id = " . DB::_escape($group_no);
      $result = DB::_query($sql, "could not get group");
      $row    = DB::_fetch($result);
      return $row[0];
    }
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    public function delete() {
      $sql    = "SELECT COUNT(*) FROM branches WHERE group_no=" . DB::_escape($this->id);
      $result = DB::_query($sql, "check failed");
      $myrow  = DB::_fetchRow($result);
      if ($myrow[0] > 0) {
        return $this->status(false, _("Cannot delete this group because customers have been created using this group."));
      }
      return parent::delete();
    }
    /**
     * @return array
     */
    public function getPagerColumns() {
      $cols = [
        ['type' => 'skip'],
        'Group Name',
      ];
      return $cols;
    }
  }
