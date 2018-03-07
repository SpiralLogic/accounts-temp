<?php
  namespace ADV\App\Sales;

  use ADV\Core\DB\DB;

  /** **/
  class Area extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
  {
    protected $_table = 'areas';
    protected $_classname = 'Area';
    protected $_id_column = 'area_code';
    public $area_code;
    public $description;
    public $inactive = 0;
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function canProcess() {
      if (empty($this->description)) {
        return $this->status(false, 'Description must be not be empty', 'description');
      }
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
      $q = DB::_select()->from('areas');
      if (!$inactive) {
        $q->andWhere('inactive=', 0);
      }
      return $q->fetch()->all();
    }
    /**
     * @return array
     */
    public function getPagerColumns() {
      $cols = [
        ['type' => 'skip'],
        'Area Name',
      ];
      return $cols;
    }
  }
