<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: ozwide
   * Date: 28/10/12
   * Time: 4:31 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Item;

  use ADV\Core\DB\DB;

  /** **/
  class Reorder extends \ADV\App\DB\Base
  {
    protected $_table = 'stock_location';
    protected $_classname = 'Stock Location';
    protected $_id_column = ['loc_code', 'stockid'];
    public $loc_code = 0;
    public $stockid = 0;
    public $stock_id = null;
    public $shelf_primary = '';
    public $shelf_secondary = '';
    public $reorder_level = 0;
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function canProcess() {
      if (strlen($this->stock_id) > 20) {
        return $this->status(false, 'Stock Id must be not be longer than 20 characters!', 'stock_id');
      }
      if (strlen($this->shelf_primary) > 8) {
        return $this->status(false, 'Shelf Primary must be not be longer than 8 characters!', 'shelf_primary');
      }
      if (strlen($this->shelf_secondary) > 8) {
        return $this->status(false, 'Shelf Secondary must be not be longer than 8 characters!', 'shelf_secondary');
      }
      return true;
    }
    /**
     * @param null $stockid
     * @param bool $inactive
     *
     * @return array
     */
    public static function getAll($stockid = null, $inactive = false) {
      $sql
        = "SELECT sl.loc_code as id,sl.loc_code, l.location_name , sl.stockid, sl.stock_id,sl.shelf_primary,sl.shelf_secondary,sl.reorder_level
        FROM stock_location sl,  locations l
        WHERE sl.loc_code=l.loc_code AND sl.stockid = " . DB::_quote($stockid) . "
        AND sl.loc_code <> " . DB::_quote(LOC_DROP_SHIP) . "
        AND sl.loc_code <> " . DB::_quote(LOC_NOT_FAXED_YET) . "
        ORDER BY sl.loc_code";
      DB::_query($sql, "an item reorder could not be retreived");
      return DB::_fetchAll(\PDO::FETCH_ASSOC);
    }
  }
