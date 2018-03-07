<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: ozwide
   * Date: 3/11/12
   * Time: 7:45 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\GL {
    use ADV\Core\DB\DB;
    use ADV\App\Validation;

    /**

     */
    class ChartClass extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {
      protected $_table = 'chart_class';
      protected $_classname = 'Chart Class';
      protected $_id_column = 'cid';
      public $cid = 0;
      public $class_name;
      public $ctype;
      public $sign_convert = 0;
      public $inactive = 0;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $result = $this->DB->select("COUNT(*) as count")->from('chart_types')->where('class_id=', $this->id)->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, 'Cannot delete this account class because GL account types have been created referring to it.');
        }
        return parent::delete();
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->class_name) > 60) {
          return $this->status(false, 'Class Name must be not be longer than 60 characters!', 'class_name');
        }
        if (!Validation::is_num($this->ctype, 0)) {
          return $this->status(false, 'Ctype must be a number', 'ctype');
        }
        return true;
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $q = DB::_select()->from('chart_class');
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
          'Class Name', //
          'Class Type', //
          'Sign Convert' => ['type' => Edit::TYPE_BOOL], //
          'Inactive'     => ['type' => 'inactive'],
        ];
        return $cols;
      }
    }
  }
