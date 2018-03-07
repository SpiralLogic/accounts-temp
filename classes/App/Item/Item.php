<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Item;

  use ADV\App\DB\Collection;
  use Item_Unit;
  use DB_AuditTrail;
  use GL_Trans;
  use Item_Code;
  use DB_Company;
  use ADV\App\Forms;
  use ADV\App\User;
  use ADV\App\UI;
  use ADV\App\Dates;
  use ADV\App\DB\Base;
  use ADV\App\SysTypes;
  use ADV\Core\Num;
  use ADV\Core\JS;
  use ADV\Core\Input\Input;
  use ADV\Core\DB\DB;

  /** **/
  class Item extends Base
  {
    public static $types
      = [
        STOCK_MANUFACTURE => "Manufactured", //
        STOCK_PURCHASED   => "Purchased", //
        STOCK_SERVICE     => "Service", //
        STOCK_INFO        => "Information"
      ];
    /** @var */
    public static $qoh_stock;
    /**
     * @var int
     */
    public $id = 0;
    /** @var */
    public $stock_id;
    /**
     * @var int
     */
    public $tax_type_id = 1;
    /**
     * @var string
     */
    public $mb_flag = STOCK_PURCHASED;
    /**
     * @var null
     */
    public $sales_account;
    /**
     * @var null
     */
    public $inventory_account;
    /**
     * @var null
     */
    public $cogs_account;
    /**
     * @var null
     */
    public $adjustment_account;
    /**
     * @var null
     */
    public $assembly_account;
    /**
     * @var int
     */
    public $dimension_id = 0;
    /**
     * @var int
     */
    public $dimension2_id = 0;
    /**
     * @var int
     */
    public $actual_cost = 0;
    /**
     * @var int
     */
    public $last_cost = 0;
    /**
     * @var int
     */
    public $material_cost = 0;
    /**
     * @var int
     */
    public $labour_cost = 0;
    /**
     * @var int
     */
    public $overhead_cost = 0;
    /**
     * @var bool
     */
    public $inactive = false;
    /**
     * @var bool
     */
    public $no_sale = false;
    /**
     * @var int
     */
    public $editable = 0;
    /**
     * @var string
     */
    public $tax_type_name = 'GST';
    /** @var */
    public $name;
    /**
     * @var int
     */
    public $category_id = 6;
    /** @var */
    public $description;
    /** @var */
    public $long_description;
    /**
     * @var string
     */
    public $units = 'ea';
    /**
     * @var array
     */
    public $salePrices = [];
    /**
     * @var array
     */
    public $purchPrices = [];
    /**
     * @var array
     */
    protected $stockLevels = [];
    protected $_table = 'stock_master';
    protected $_id_column = 'id';
    protected $_classname = 'Item';
    /***
     * @param int|Array $id
     */
    public function __construct($id = 0) {
      parent::__construct($id);
      $this->uom  = & $this->units;
      $this->name = & $this->description;
    }
    /**
     * @return void
     */
    public function delete() {
      // TODO: Implement delete() method.
    }
    /**
     * @param array $changes
     *
     * @return array|bool|int|null
     */
    public function save($changes = null) {
      $this->setDefaults();
      if (!parent::save($changes)) {
        return false;
      }
      return true;
    }
    /**
     * @return void
     */
    public function  getSalePrices() {
      $this->salePrices = new Collection(new Price(), 'stock_id');
      $this->salePrices->getAll($this->stock_id);
    }
    /**
     * @internal param array $option
     * @return array|mixed
     */
    public function  getPurchPrices() {
      $this->purchPrices = new Collection(new Purchase(), 'stockid');
      $this->purchPrices->getAll($this->id);
    }
    /**
     * @param null $location
     *
     * @return array|bool|mixed
     */
    public function  getStockLevels($location = null) {
      if (!$this->id > 0) {
        return false;
      }
      $id  = $this->id;
      $sql = "
SELECT l.loc_code, l.location_name, r.shelf_primary, r.shelf_secondary, i.stock_id AS id, r.reorder_level, o.demand, (qty-o.demand) AS available, p.onorder, qty
            FROM locations l
            LEFT JOIN (SELECT stock_id, loc_code, SUM(qty) AS qty FROM stock_moves WHERE stockid=$id AND tran_date <= now() GROUP BY loc_code, stock_id) i ON l.loc_code = i.loc_code
            LEFT JOIN stock_location r ON r.loc_code = l.loc_code AND r.stockid = $id
            LEFT JOIN (SELECT SUM(sales_order_details.quantity - sales_order_details.qty_sent) AS demand , sales_orders.from_stk_loc AS loc_code FROM sales_order_details, sales_orders
                WHERE sales_order_details.order_no= sales_orders.order_no AND sales_orders.trans_type=30 AND sales_orders.trans_type=sales_order_details.trans_type
                AND sales_order_details.stockid = $id GROUP BY sales_orders.from_stk_loc) o ON o.loc_code=l.loc_code
            LEFT JOIN (SELECT SUM(purch_order_details.quantity_ordered - purch_order_details.quantity_received) AS onorder , purch_orders.into_stock_location AS loc_code
                FROM purch_order_details, purch_orders	WHERE purch_order_details.order_no= purch_orders.order_no AND purch_order_details.stockid = $id
                GROUP BY purch_orders.into_stock_location) p ON p.loc_code=l.loc_code";
      if ($location !== null) {
        $sql .= " WHERE l.loc_code=" . DB::_escape($location);
      }
      $result = DB::_query($sql, 'Could not get item stock levels');
      if ($location !== null) {
        return DB::_fetchAssoc($result);
      }
      while ($row = DB::_fetchAssoc($result)) {
        $row['demand']       = ($row['demand']) ? : 0;
        $row['qty']          = ($row['qty']) ? : 0;
        $row['available']    = ($row['available']) ? : 0;
        $row['onorder']      = ($row['onorder']) ? : 0;
        $this->stockLevels[] = $row;
      }
      return $this->stockLevels;
    }
    /**
     * @return int
     */
    public function getStockOnOrder() {
      $sql    = "SELECT SUM(sales_order_details.quantity - sales_order_details.qty_sent) AS demand , sales_orders.from_stk_loc AS loc_code FROM sales_order_details, sales_orders WHERE sales_order_details.order_no= sales_orders.order_no AND sales_orders.trans_type=30 AND sales_orders.trans_type=sales_order_details.trans_type AND sales_order_details.stockid = " . DB::_escape(
        $this->id
      ) . "' GROUP BY sales_orders.from_stk_loc";
      $result = DB::_query($sql, "No transactions were returned");
      $row    = DB::_fetch($result);
      if ($row === false) {
        return 0;
      }
      return $row['QtyDemand'];
    }
    /**
     * @return null|\PDOStatement
     */
    public static function getAll() {
      $sql = "SELECT * FROM stock_master";
      return DB::_query($sql, "items could not be retreived");
    }
    /**
     * @param      $stock_id
     * @param null $location
     * @param null $date_
     * @param int  $exclude
     *
     * @return mixed
     */
    public static function get_qoh_on_date($stock_id, $location = null, $date_ = null, $exclude = 0) {
      if ($date_ == null) {
        $date_ = Dates::_today();
      }
      $date = Dates::_dateToSql($date_);
      $sql
            = "SELECT SUM(qty) FROM stock_moves
             WHERE stock_id=" . DB::_escape($stock_id) . "
             AND tran_date <= '$date'";
      if ($location != null) {
        $sql .= " AND loc_code = " . DB::_escape($location);
      }
      $result = DB::_query($sql, "QOH calulcation failed");
      $myrow  = DB::_fetchRow($result);
      if ($exclude > 0) {
        $sql
                = "SELECT SUM(qty) FROM stock_moves
                 WHERE stock_id=" . DB::_escape($stock_id) . " AND type=" . DB::_escape($exclude) . " AND tran_date = '$date'";
        $result = DB::_query($sql, "QOH calulcation failed");
        $myrow2 = DB::_fetchRow($result);
        if ($myrow2 !== false) {
          $myrow[0] -= $myrow2[0];
        }
      }
      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get_edit_info($stock_id) {
      $sql
              = "SELECT material_cost + labour_cost + overhead_cost AS standard_cost, units, decimals
             FROM stock_master,item_units
             WHERE stock_id=" . DB::_escape($stock_id) . " AND stock_master.units=item_units.abbr";
      $query  = DB::_query($sql, "The standard cost cannot be retrieved");
      $result = [
        'standard_cost' => 0,
        'units'         => 'ea',
        'decimals'      => User::_price_dec()
      ];
      if (DB::_numRows($query) == 0) {
        $result = DB::_fetch($query);
      }
      return $result;
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $material_cost
     * @param $to
     *
     * @return mixed
     */
    public static function adjust_deliveries($stock_id, $material_cost, $to) {
      if (!Item::is_inventory_item($stock_id)) {
        return;
      }
      $from = Item::last_negative_stock_begin_date($stock_id, $to);
      if ($from == false || $from == "") {
        return;
      }
      $from = Dates::_sqlToDate($from);
      $row  = Item::get_deliveries_between($stock_id, $from, $to);
      if ($row == false) {
        return;
      }
      $old_cost = $row[1];
      $new_cost = $row[0] * $material_cost;
      $diff     = $new_cost - $old_cost;
      if ($diff != 0) {
        $update_no = SysTypes::get_next_trans_no(ST_COSTUPDATE);
        if (!Dates::_isDateInFiscalYear($to)) {
          $to = Dates::_endFiscalYear();
        }
        $stock_gl_code = Item::get_gl_code($stock_id);
        $memo_         = _("Cost was ") . $old_cost . _(" changed to ") . $new_cost . _(" for item ") . "'$stock_id'";
        GL_Trans::add_std_cost(ST_COSTUPDATE, $update_no, $to, $stock_gl_code["cogs_account"], $stock_gl_code["dimension_id"], $stock_gl_code["dimension2_id"], $memo_, $diff);
        GL_Trans::add_std_cost(ST_COSTUPDATE, $update_no, $to, $stock_gl_code["inventory_account"], 0, 0, $memo_, -$diff);
        DB_AuditTrail::add(ST_COSTUPDATE, $update_no, $to);
      }
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return bool
     */
    public static function is_inventory_item($stock_id) {
      $sql
              = "SELECT stock_id FROM stock_master
             WHERE stock_id=" . DB::_escape($stock_id) . " AND mb_flag <> 'D'";
      $result = DB::_query($sql, "Cannot query is inventory item or not");
      return DB::_numRows($result) > 0;
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $to
     *
     * @return mixed
     */
    public static function last_negative_stock_begin_date($stock_id, $to) {
      $to  = Dates::_dateToSql($to);
      $sql = "SET @q = 0";
      DB::_query($sql);
      $sql = "SET @flag = 0";
      DB::_query($sql);
      $sql
              = "SELECT SUM(qty), @q:= @q + qty, IF(@q < 0 AND @flag=0, @flag:=1,@flag:=0), IF(@q < 0 AND @flag=1, tran_date,'') AS begin_date
             FROM stock_moves
             WHERE stock_id=" . DB::_escape($stock_id) . " AND tran_date<='$to'
             AND qty <> 0
             GROUP BY stock_id ORDER BY tran_date";
      $result = DB::_query($sql, "The dstock moves could not be retrieved");
      $row    = DB::_fetchRow($result);
      return $row[3];
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $from
     * @param $to
     *
     * @return mixed
     */
    public static function get_deliveries_between($stock_id, $from, $to) {
      $from = Dates::_dateToSql($from);
      $to   = Dates::_dateToSql($to);
      $sql
              = "SELECT SUM(-qty), SUM(-qty*standard_cost) FROM stock_moves
             WHERE type=" . ST_CUSTDELIVERY . " AND stock_id=" . DB::_escape($stock_id) . " AND
                 tran_date>='$from' AND tran_date<='$to' GROUP BY stock_id";
      $result = DB::_query($sql, "The deliveries could not be updated");
      return DB::_fetchRow($result);
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get_gl_code($stock_id) {
      /*Gets the GL Codes relevant to the item account */
      $sql
           = "SELECT inventory_account, cogs_account,
             adjustment_account, sales_account, assembly_account, dimension_id, dimension2_id FROM
             stock_master WHERE stock_id = " . DB::_escape($stock_id);
      $get = DB::_query($sql, "retreive stock gl code");
      return DB::_fetch($get);
    }
    /***
     * @static
     *
     * @param $stock_id
     *
     * @return string
     */
    public static function img_name($stock_id) {
      return strtr($stock_id, "><\\/:|*?", '________');
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return mixed
     */
    public static function get_stockid($stock_id) {
      $result = current(DB::_select('id')->from('stock_master')->where('stock_id LIKE ', $stock_id)->fetch()->all());
      return $result['id'];
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $location
     *
     * @return int
     */
    public static function get_demand($stock_id, $location) {
      $sql
        = "SELECT SUM(sales_order_details.quantity - sales_order_details.qty_sent) AS QtyDemand FROM sales_order_details, sales_orders
                            WHERE sales_order_details.order_no=" . "sales_orders.order_no AND sales_orders.trans_type=" . ST_SALESORDER . " AND
                            sales_orders.trans_type=sales_order_details.trans_type AND ";
      if ($location != "") {
        $sql .= "sales_orders.from_stk_loc =" . DB::_escape($location) . " AND ";
      }
      $sql .= "sales_order_details.stk_code = " . DB::_escape($stock_id);
      $result = DB::_query($sql, "No transactions were returned");
      $row    = DB::_fetch($result);
      if ($row === false) {
        return 0;
      }
      return $row['QtyDemand'];
    }
    /**
     * @static
     *
     * @param $term
     *
     * @return array
     */
    public static function search($term) {
      $term       = explode(' ', trim($term));
      $item_code  = trim(array_shift($term));
      $terms      = [$item_code, '%' . $item_code . '%'];
      $terms      = [$item_code, $item_code . '%', $terms[1], $terms[1], $terms[1]];
      $termswhere = ' OR i.long_description LIKE ? ';
      $qwhere     = '';
      foreach ($term as $t) {
        $qwhere .= ' AND i.long_description LIKE ? ';
        $terms[] = '%' . trim($t) . '%';
      }
      $stock_code = " s.stockid as id,s.item_code as value,";
      $where2     = ' AND i.id = s.stockid ';
      $weight     = 'IF(s.item_code LIKE ?, 0,20) + IF(s.item_code LIKE ?,0,5) + IF(s.item_code LIKE ?,0,5) as weight';
      $sql        = "SELECT  " . $stock_code . " CONCAT(s.item_code,' - ',i.description) AS label, C.description AS category,
                                $weight FROM stock_category C, item_codes s, stock_master i
                                WHERE (s.item_code LIKE ? $termswhere) $qwhere
                                AND s.category_id = C.category_id $where2 GROUP BY s.item_code
                                ORDER BY weight, s.category_id, s.item_code LIMIT 30";

      DB::_prepare($sql);
      $result = DB::_execute($terms);
      return $result;
    }
    /**
     * @static
     *
     * @param $term
     * @param $UniqueID
     *
     * @return array|bool
     */
    public static function searchOrder($term, $UniqueID) {
      $url        = false;
      $nodiv      = false;
      $label      = false;
      $size       = 30;
      $name       = false;
      $set        = false;
      $sale       = false;
      $purchase   = false;
      $inactive   = false;
      $no_sale    = false;
      $kitsonly   = false;
      $select     = false;
      $type       = false;
      $sales_type = '';
      $value      = false;
      $focus      = false;
      $callback   = false;
      if (isset($_SESSION['search'])) {
        extract($_SESSION['search'][$UniqueID], EXTR_IF_EXISTS);
      }
      $term         = explode(' ', trim($term));
      $item_code    = trim(array_shift($term));
      $terms        = [$item_code, '%' . $item_code . '%'];
      $terms        = [$item_code, $item_code . '%', $terms[1], $terms[1], $terms[1]];
      $termswhere   = ' OR i.long_description LIKE ? ';
      $qconstraints = '';
      foreach ($term as $t) {
        $qconstraints .= ' AND i.long_description LIKE ? ';
        $terms[] = '%' . trim($t) . '%';
      }
      $qconstraints .= ($inactive ? '' : ' AND s.inactive = 0 ') . ($no_sale ? '' : ' AND i.no_sale =0 ');
      $qconstraints2 = (!empty($where) ? ' AND ' . $where : ' ');
      if ($type == 'local') {
        $qconstraints2 .= " AND !s.is_foreign ";
      }
      $stock_code = " s.item_code as stock_id,";
      $qconstraints2 .= ' AND i.id = s.stockid ';
      $prices = '';
      $weight = 'IF(s.item_code LIKE ?, 0,20) + IF(s.item_code LIKE ?,0,5) + IF(s.item_code LIKE ?,0,5) as weight';
      if ($purchase) {
        array_unshift($terms, $item_code);
        $weight = 'IF(s.item_code LIKE ?, 0,20) + IF(p.supplier_description LIKE ?, 0,15) + IF(s.item_code LIKE ?,0,5) as weight';
        $termswhere .= ' OR p.supplier_description LIKE ? ';
        if (Input::_session('creditor_id', Input::NUMERIC)) {
          array_unshift($terms, $_SESSION['creditor_id']);
          $weight = ' IF(p.creditor_id = ?,0,20) + ' . $weight;
        }
        $stock_code = ' s.item_code as stock_id, p.supplier_description, MIN(p.price) as price, ';
        $prices     = " LEFT OUTER JOIN purch_data p ON i.id = p.stockid ";
        $sales_type = '';
      } elseif ($sale) {
        $weight     = 'IF(s.item_code LIKE ?, 0,20) + IF(s.item_code LIKE ?,0,5) + IF(s.item_code LIKE ?,0,5) as weight';
        $stock_code = " s.item_code as stock_id, MIN(p.price) as price,";
        $prices     = ", prices p";
        $qconstraints .= " AND s.id = p.item_code_id ";
        if ($sales_type) {
          $sales_type = ' AND (p.sales_type_id = ' . $sales_type . ' OR p.sales_type_id = 1 )';
          $weight .= ', p.sales_type_id';
        }
      } elseif ($kitsonly) {
        $qconstraints .= " AND s.stock_id!=i.stock_id ";
      }
      $qselect = ($select) ? $select : ' ';
      $sql     = "SELECT ".$qselect." $stock_code i.description as item_name, c.description as category, i.long_description as description , editable,
                            $weight FROM stock_category c, item_codes s, stock_master i  $prices
                            WHERE (s.item_code LIKE ? $termswhere) $qconstraints
                            AND s.category_id = c.category_id $qconstraints2 $sales_type GROUP BY s.item_code
                            ORDER BY weight, s.category_id, s.item_code LIMIT 30";
      DB::_prepare($sql);
      $result = DB::_execute($terms);
      return $result;
    }
    /**
     * @static
     *
     * @param array $options
     *
     * @return void
     */
    public static function addEditDialog($options = []) {
      $action
        = <<<JS
            //noinspection ThisExpressionReferencesGlobalObjectJS
Adv.dialogWindow.open("/items/manage/items?stock_id="+$(this).data('stock_id'));
JS;
      //      JS::_addLiveEvent('.stock', 'click', $action, "wrapper", true);
      JS::_addLiveEvent('label.stock', 'click', $action, "wrapper", true);
    }
    /**
     * @static
     *
     * @param        $stock_id
     * @param        $description
     * @param        $long_description
     * @param        $category_id
     * @param        $tax_type_id
     * @param string $units
     * @param string $mb_flag
     * @param        $sales_account
     * @param        $inventory_account
     * @param        $cogs_account
     * @param        $adjustment_account
     * @param        $assembly_account
     * @param        $dimension_id
     * @param        $dimension2_id
     * @param        $no_sale
     *
     * @return void
     */
    public static function update($stock_id, $description, $long_description, $category_id, $tax_type_id, $units = '', $mb_flag = '', $sales_account, $inventory_account, $cogs_account, $adjustment_account, $assembly_account, $dimension_id, $dimension2_id, $no_sale) {
      $sql = "UPDATE stock_master SET long_description=" . DB::_escape($long_description) . ",
                 description=" . DB::_escape($description) . ",
                 category_id=" . DB::_escape($category_id) . ",
                 sales_account=" . DB::_escape($sales_account) . ",
                 inventory_account=" . DB::_escape($inventory_account) . ",
                 cogs_account=" . DB::_escape($cogs_account) . ",
                 adjustment_account=" . DB::_escape($adjustment_account) . ",
                 assembly_account=" . DB::_escape($assembly_account) . ",
                 dimension_id=" . DB::_escape($dimension_id) . ",
                 dimension2_id=" . DB::_escape($dimension2_id) . ",
                 tax_type_id=" . DB::_escape($tax_type_id) . ",
                 no_sale=" . DB::_escape($no_sale);
      if ($units != '') {
        $sql .= ", units='$units'";
      }
      if ($mb_flag != '') {
        $sql .= ", mb_flag='$mb_flag'";
      }
      $sql .= " WHERE stock_id=" . DB::_escape($stock_id);
      DB::_query($sql, "The item could not be updated");
      Item_Code::update(-1, $stock_id, $stock_id, $description, $category_id, 1, 0);
    }
    /**
     * @static
     *
     * @param $stock_id
     * @param $description
     * @param $long_description
     * @param $category_id
     * @param $tax_type_id
     * @param $units
     * @param $mb_flag
     * @param $sales_account
     * @param $inventory_account
     * @param $cogs_account
     * @param $adjustment_account
     * @param $assembly_account
     * @param $dimension_id
     * @param $dimension2_id
     * @param $no_sale
     *
     * @return void
     */
    public static function add($stock_id, $description, $long_description, $category_id, $tax_type_id, $units, $mb_flag, $sales_account, $inventory_account, $cogs_account, $adjustment_account, $assembly_account, $dimension_id, $dimension2_id, $no_sale) {
      $sql
        = "INSERT INTO stock_master (stock_id, description, long_description, category_id,
                 tax_type_id, units, mb_flag, sales_account, inventory_account, cogs_account,
                 adjustment_account, assembly_account, dimension_id, dimension2_id, no_sale)
                 VALUES (" . DB::_escape($stock_id) . ", " . DB::_escape($description) . ", " . DB::_escape($long_description) . ",
                 " . DB::_escape($category_id) . ", " . DB::_escape($tax_type_id) . ", " . DB::_escape($units) . ", " . DB::_escape($mb_flag) . ",
                 " . DB::_escape($sales_account) . ", " . DB::_escape($inventory_account) . ", " . DB::_escape($cogs_account) . "," . DB::_escape(
        $adjustment_account
      ) . ", " . DB::_escape($assembly_account) . ", " . DB::_escape($dimension_id) . ", " . DB::_escape($dimension2_id) . "," . DB::_escape(
        $no_sale
      ) . ")";
      DB::_query($sql, "The item could not be added");
      $sql
        = "INSERT INTO stock_location (loc_code, stock_id)
                 SELECT locations.loc_code, " . DB::_escape($stock_id) . " FROM locations";
      DB::_query($sql, "The item locstock could not be added");
      Item_Code::add($stock_id, $stock_id, $description, $category_id, 1, 0);
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return void
     */
    public static function del($stock_id) {
      $sql = "DELETE FROM stock_master WHERE stock_id=" . DB::_escape($stock_id);
      DB::_query($sql, "could not delete stock item");
      /*and cascade deletes in stock_location */
      $sql = "DELETE FROM stock_location WHERE stock_id=" . DB::_escape($stock_id);
      DB::_query($sql, "could not delete stock item loc stock");
      /*and cascade deletes in purch_data */
      $sql = "DELETE FROM purch_data WHERE stock_id=" . DB::_escape($stock_id);
      DB::_query($sql, "could not delete stock item purch data");
      /*and cascade deletes in prices */
      $sql = "DELETE FROM prices WHERE stock_id=" . DB::_escape($stock_id);
      DB::_query($sql, "could not delete stock item prices");
      /*and cascade delete the bill of material if any */
      $sql = "DELETE FROM bom WHERE parent=" . DB::_escape($stock_id);
      DB::_query($sql, "could not delete stock item bom");
      Item_Code::delete_kit($stock_id);
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get($stock_id) {
      $sql
              = "SELECT stock_master.*,item_tax_types.name AS tax_type_name
                 FROM stock_master,item_tax_types
                 WHERE item_tax_types.id=stock_master.tax_type_id
                 AND stock_id=" . DB::_escape($stock_id);
      $result = DB::_query($sql, "an item could not be retreived");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param      $number
     * @param null $stock_id
     * @param      $dec
     *
     * @return int|string
     */
    public static function qty_format($number, $stock_id = null, &$dec) {
      $dec = Item::qty_dec($stock_id);
      return Num::_format($number, $dec);
    }
    /**
     * @static
     *
     * @param null $stock_id
     *
     * @return mixed
     */
    public static function qty_dec($stock_id = null) {
      if (is_null($stock_id)) {
        $dec = User::_qty_dec();
      } else {
        $dec = Item_Unit::get_decimal($stock_id);
        if ($dec == -1) {
          $dec = User::_qty_dec();
        }
      }
      return $dec;
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $all
     * @param bool $editkey
     * @param bool $legacy
     *
     * @return void
     */
    public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $legacy = false) {
      echo Item::select(
        $name, $selected_id, $all_option, $submit_on_change, [
                                                                  'submitonselect' => $submit_on_change,
                                                                  'label'          => $label,
                                                                  'cells'          => true,
                                                                  'size'           => 10,
                                                                  'purchase'       => false,
                                                                  'show_inactive'  => $all,
                                                                  'editable'       => $editkey
                                                             ], $editkey, $legacy
      );
    }
    /**
     * @static
     *
     * @param       $name
     * @param null  $selected_id
     * @param bool  $all_option
     * @param bool  $submit_on_change
     * @param array $opts
     * @param bool  $editkey
     * @param bool  $legacy
     *
     * @return string
     */
    public static function select($name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts = [], $editkey = false, $legacy = false) {
      if (!$legacy) {
        Item::addSearchBox(
          $name, array_merge(
            [
                 'submitonselect' => $submit_on_change,
                 'selected'       => $selected_id,
                 'purchase'       => true,
                 'cells'          => true
            ], $opts
          )
        );
        return '';
      }
      $sql
        = "SELECT stock_id, s.description, C.description, s.inactive, s.editable, s.long_description
                    FROM stock_master s,stock_category C WHERE s.category_id=C.category_id";
      return Forms::selectBox(
        $name, $selected_id, $sql, 'stock_id', 's.description', array_merge(
          [
               'format'        => 'Forms::stockItemsFormat',
               'spec_option'   => $all_option === true ? _("All Items") : $all_option,
               'spec_id'       => ALL_TEXT,
               'search_box'    => false,
               'search'        => [
                 "stock_id",
                 "c.description",
                 "s.description"
               ],
               'search_submit' => DB_Company::_get_pref('no_item_list') != 0,
               'size'          => 10,
               'select_submit' => $submit_on_change,
               'category'      => 2,
               'order'         => [
                 'c.description',
                 'stock_id'
               ],
               'editable'      => 30,
               'max'           => 50
          ], $opts
        )
      );
    }
    /**
     * @static
     *
     * @param       $id
     * @param array $options 'description' => false,<br>
    'disabled' => false,<br>
    'editable' => true,<br>
    'selected' => '',<br>
    'label' => false,<br>
    'cells' => false,<br>
    'inactive' => false,<br>
    'purchase' => false,<br>
    'sale' => false,<br>
    'js' => '',<br>
    'selectjs' => '',<br>
    'submitonselect' => '',<br>
    'sales_type' => 1,<br>
    'no_sale' => false,<br>
    'select' => false,<br>
    'type' => 'local',<br>
    'kits'=>true,<br>
    'where' => '',<br>
    'size'=>'20px'<br>
     *
     * @return void
     */
    public static function addSearchBox($id, $options = []) {
      echo UI::searchLine($id, '/search', $options);
    }
    /**
     * @static
     *
     * @param $stock_code
     *
     * @return mixed
     */
    public static function getStockID($stock_code) {
      return $stock_code ? DB::_select('id')->from('stock_master')->where('stock_id LIKE', $stock_code)->fetch()->one('id') : 0;
    }
    protected function setDefaults() {
      if ($this->mb_flag == STOCK_MANUFACTURE || $this->mb_flag == STOCK_PURCHASED) {
        $this->inventory_account = DB_Company::i()->default_inventory_act;
      } else {
        $this->inventory_account = '';
      }
      if ($this->mb_flag == STOCK_MANUFACTURE) {
        $this->assembly_account = DB_Company::i()->default_assembly_act;
      } else {
        $this->assembly_account = '';
      }
    }
    /**
     * @param int|null $id
     * @param array    $extra
     *
     * @return bool|void
     */
    protected function read($id = null, $extra = []) {
      $id = $id ? : 0;
      if (!is_numeric($id)) {
        $stockid = static::getStockID((string) $id);
        if ($stockid) {
          $id = $stockid;
        }
      }
      if (!parent::read($id)) {
        return $this->status->get();
      }
      return true;
    }
    /**
     * @return bool
     */
    protected function canProcess() {
      if (!$this->stock_id) {
        return $this->status(false, 'Item must have a stock_id ' . $this->stock_id, 'stock_id');
      }
      if (!$this->name) {
        return $this->status(false, 'Item must have a name', 'name');
      }
      return true;
    }
    /**
     * @return void
     */
    protected function countTransactions() {
      // TODO: Implement countTransactions() method.
    }
    /**
     * @return array|null
     */
    protected function init() {
      $this->defaults();
      return $this->status(true, 'Now working with a new Item');
    }
    /**
     * @return void
     */
    protected function defaults() {
      $this->sales_account      = DB_Company::i()->default_inv_sales_act;
      $this->inventory_account  = DB_Company::i()->default_inventory_act;
      $this->cogs_account       = DB_Company::i()->default_cogs_act;
      $this->assembly_account   = DB_Company::i()->default_assembly_act;
      $this->adjustment_account = DB_Company::i()->default_adj_act;
    }
    /**
     * @return array|bool|int|null
     */
    protected function saveNew() {
      DB::_begin();
      $data = (array) $this;
      unset($data['id']);
      if (!parent::saveNew()) {
        DB::_cancel();
        return false;
      }
      $sql    = "INSERT INTO stock_location (loc_code, stockid, stock_id) SELECT locations.loc_code, " . DB::_quote($this->id) . ", " . DB::_quote(
        $this->stock_id
      ) . " FROM locations";
      $result = DB::_query($sql, "The item locstock could not be added");
      if (!$result) {
        DB::_cancel();
        return $this->status(false, "Could not add item location information.");
      }
      $sql    = "INSERT INTO item_codes (stockid, item_code, stock_id, description, category_id, quantity, is_foreign) VALUES(" . DB::_quote($this->id) . "," . DB::_quote($this->stock_id) . "," . DB::_quote($this->stock_id) . "," . DB::_quote($this->description) . "," . DB::_quote($this->category_id) . ",1,0)";
      $result = DB::_query($sql, "The item locstock could not be added");
      if (!$result) {
        DB::_cancel();
        return $this->status(false, "Could not add item code information.");
      }
      DB::_commit();
      return $this->status(\ADV\Core\Status::SUCCESS, "Item has been added.");
    }
    /**
     * @static
     *
     * @param string $location
     *
     * @return void
     */
    protected static function load_stock_levels($location = '') {
      $date = Dates::_today(true);
      $sql  = "SELECT stock_id, SUM(qty) FROM stock_moves WHERE tran_date <= '$date'";
      if ($location != '') {
        $sql .= " AND loc_code = " . DB::_escape($location);
      }
      $sql .= " GROUP BY stock_id";
      $result = DB::_query($sql, "QOH calulcation failed");
      while ($row = DB::_fetch($result)) {
        static::$qoh_stock[$row[0]] = $row[1];
      }
    }
  }
