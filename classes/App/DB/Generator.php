<?php
  namespace ADV\App\DB;

  use ADV\Core\DIC;

  /** **/
  class Generator
  {
    protected $vars = [];
    protected $valids = [];
    protected $name;
    protected $inactive = '';
    protected $id_column;
    public $table;
    /**

     */
    public function __construct() {
      //$this->DB = new DB('jobsboard');
      $this->DB = DIC::get('DB');
    }
    /**
     * @param $table
     */
    public function getTableDetails($table) {
      $this->table  = $table;
      $this->vars[] = 'protected $_table = \'' . $table . '\';';
      $this->name   = $this->varToName($table);
      $this->vars[] = 'protected $_classname = \'' . rtrim($this->name, 's') . '\';';
      $table        = $this->DB->select()->from('INFORMATION_SCHEMA.COLUMNS')->where('TABLE_NAME=', $table)->fetch();
      foreach ($table as $row) {
        $this->findVars($row);
        $this->findValids($row);
        if ($row['COLUMN_NAME'] == 'inactive') {
          $this->inactive
            = 'if (!$inactive) {
                  $q->andWhere(\'inactive=\',0);
                }';
        }
      }
      echo nl2br($this->generate());
    }
    /**
     * @param $var
     *
     * @return string
     */
    protected function varToName($var) {
      $return = str_replace('_', ' ', $var);
      $return = implode(
        ' ',
        array_map(
          function ($v) {
            return ucfirst($v);
          },
          explode(' ', $return)
        )
      );
      return $return;
    }
    /**
     * @param $row
     */
    protected function findVars($row) {
      $name = $row['COLUMN_NAME'];
      if ($row['COLUMN_KEY'] === 'PRI') {
        $this->vars[]    = 'protected $_id_column = \'' . $name . '\';' . PHP_EOL;
        $this->id_column = $name;
      }
      $var = 'public $' . $name;
      if ($row['COLUMN_DEFAULT'] !== '') {
        $var .= ' = ' . $row['COLUMN_DEFAULT'];
      } elseif ($row['COLUMN_KEY'] === 'PRI' || $row['COLUMN_DEFAULT'] !== '') {
        $var .= ' = 0';
      }
      $var .= ';';
      $this->vars[] = $var;
    }
    /**
     * @param $row
     */
    protected function findValids($row) {
      if ($row['COLUMN_NAME'] == $this->id_column) {
        return;
      }
      $name = $row['COLUMN_NAME'];
      if (in_array($row['DATA_TYPE'], ['int', 'float', 'double', 'decimal'])) {
        $min            = (strpos($row['COLUMN_TYPE'], 'unsigned')) ? '0' : 'null';
        $this->valids[] = 'if (!Validation::is_num($this->' . $name . ', ' . $min . ')){
            return $this->status(false,\'' . $this->varToName($name) . ' must be a number\',\'' . $name . '\');' . PHP_EOL . '}' . PHP_EOL;
      }
      if (in_array($row['DATA_TYPE'], ['varchar', 'text', 'char'])) {
        if ($row['NULLABLE'] === 'NO' && $row['COLUMN_DEFAULT'] === 'NONE') {
          $this->valids[] = 'if (empty($this->' . $name . ')){
            return $this->status(false,\'' . $this->varToName($name) . ' must be not be empty\',\'' . $name . '\');' . PHP_EOL . '}' . PHP_EOL;
        }
        if ($row['CHARACTER_MAXIMUM_LENGTH'] !== '') {
          $len            = $row['CHARACTER_MAXIMUM_LENGTH'];
          $this->valids[] = 'if (strlen($this->' . $name . ')>' . $len . '){
                  return $this->status(false,\'' . ucfirst(
            $this->varToName($name)
          ) . ' must be not be longer than ' . $len . ' characters!\',\'' . $name . '\');' . PHP_EOL . '}' . PHP_EOL;
        }
      }
    }
    /**
     * @return string
     */
    public function generate() {
      $this->name = explode(' ', $this->name);
      $name       = array_pop($this->name);
      $this->name = implode(' ', $this->name);
      $table      = $this->table;
      $namespace  = "ADV\\App\\" . str_replace(' ', '\\', $this->name);
      $vars       = implode("\n", $this->vars) . PHP_EOL;
      $valids     = implode("", $this->valids) . PHP_EOL;
      $inactive   = $this->inactive . PHP_EOL;
      return <<<CLASS

      namespace $namespace {
      use ADV\Core\DB\DB;
  use ADV\App\Validation;
/**

   */
class $name extends \ADV\App\DB\Base {

  $vars
  /**
       * @return \ADV\Core\Traits\Status|bool
       */
  protected function canProcess() {
    $valids
    return true;
  }

  /**
  * @param bool \$inactive
  *
  * @return array
  */
      public static function getAll(\$inactive = false) {
        \$q = DB::_select()->from('$table');
        $inactive
        return \$q->fetch()->all();
      }
}
}
namespace {

}
CLASS;
    }
  }
