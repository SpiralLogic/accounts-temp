<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Item {
    use ADV\Core\DB\DB;
    use ADV\Core\Event;
    use DB_Company;
    use ADV\App\Validation;

    /**

     */
    class Category extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {
      protected $_table = 'stock_category';
      protected $_classname = 'Stock Category';
      protected $_id_column = 'category_id';
      public $category_id = 0;
      public $description = '';
      public $inactive = 0;
      public $dflt_tax_type = 1;
      public $dflt_units = 'each';
      public $dflt_mb_flag = 'B';
      public $dflt_sales_act;
      public $dflt_cogs_act;
      public $dflt_inventory_act;
      public $dflt_adjustment_act;
      public $dflt_assembly_act;
      public $dflt_dim1;
      public $dflt_dim2;
      public $dflt_no_sale = 0;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $sql    = "SELECT  COUNT(*) FROM stock_master WHERE category_id=" . DB::_escape($this->id);
        $result = DB::_query($sql, "could not query stock master");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          Event::error(_("Cannot delete this item category because items have been created using this item category."));
        }
        return parent::delete();
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->description) > 60) {
          return $this->status(false, 'Description must be not be longer than 60 characters!', 'description');
        }
        if (!Validation::is_num($this->dflt_tax_type, 0)) {
          return $this->status(false, 'Dflt_tax_type must be a number', 'dflt_tax_type');
        }
        if (strlen($this->dflt_units) > 20) {
          return $this->status(false, 'Dflt_units must be not be longer than 20 characters!', 'dflt_units');
        }
        if (strlen($this->dflt_mb_flag) > 1) {
          return $this->status(false, 'Dflt_mb_flag must be not be longer than 1 characters!', 'dflt_mb_flag');
        }
        if (!Validation::is_num($this->dflt_sales_act, 0)) {
          return $this->status(false, 'Dflt_sales_act must be a number', 'dflt_sales_act');
        }
        if (!Validation::is_num($this->dflt_cogs_act, 0)) {
          return $this->status(false, 'Dflt_cogs_act must be a number', 'dflt_cogs_act');
        }
        if (!Validation::is_num($this->dflt_inventory_act, 0)) {
          return $this->status(false, 'Dflt_inventory_act must be a number', 'dflt_inventory_act');
        }
        if (!Validation::is_num($this->dflt_adjustment_act, 0)) {
          return $this->status(false, 'Dflt_adjustment_act must be a number', 'dflt_adjustment_act');
        }
        if (!Validation::is_num($this->dflt_assembly_act, 0)) {
          return $this->status(false, 'Dflt_assembly_act must be a number', 'dflt_assembly_act');
        }
        return true;
      }
      public function defaults() {
        parent::defaults();
        $company                   = DB_Company::_i();
        $this->dflt_sales_act      = $company->default_sales_act;
        $this->dflt_inventory_act  = $company->default_inventory_act;
        $this->dflt_cogs_act       = $company->default_cogs_act;
        $this->dflt_adjustment_act = $company->default_adj_act;
        $this->dflt_assembly_act   = $company->default_inv_sales_act;
        $this->dflt_assembly_act   = $company->default_assembly_act;
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $sql = "SELECT c.category_id as id, c.*, t.name as tax_name FROM stock_category c, item_tax_types t WHERE c.dflt_tax_type=t.id";
        if (!$inactive) {
          $sql .= " AND !c.inactive";
        }
        //DB::_query($sql);
        return $sql;
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        $cols = [
          ['type' => 'skip'],
          ['type' => 'skip'],
          'Name'        => ['ord' => 'asc'],
          'inactive'    => ['type' => 'inactive'],
          ['type' => 'skip'],
          'Units',
          ['type' => 'skip'],
          'Sales'       => ['fun' => [$this, 'formatAccounts'], 'useName' => true],
          'COGS'        => ['fun' => [$this, 'formatAccounts'], 'useName' => true],
          'Inventory'   => ['fun' => [$this, 'formatAccounts'], 'useName' => true],
          'Adjustments' => ['fun' => [$this, 'formatAccounts'], 'useName' => true],
          'Assemnbly'   => ['fun' => [$this, 'formatAccounts'], 'useName' => true],
          ['type' => 'skip'],
          ['type' => 'skip'],
          ['type' => 'skip'],
          'Tax'         => ['ord' => 'asc'],
        ];
        return $cols;
      }
      /**
       * @param $row
       * @param $cellname
       *
       * @return string
       */
      public function formatAccounts($row, $cellname) {
        $unsed = [];
        if ($row['dflt_mb_flag'] == STOCK_SERVICE || $row['dflt_mb_flag'] == STOCK_INFO) {
          $unsed = [
            'dflt_cogs_act',
            'dflt_inventory_act',
            'dflt_adjustment_act',
            'dflt_assembly_act',
          ];
        } elseif ($row['dflt_mb_flag'] == STOCK_PURCHASED) {
          $unsed = ['dflt_assembly_act'];
        }
        if ($row['dflt_mb_flag'] == STOCK_INFO) {
          $unsed += ['dflt_sales_act'];
        }
        if (in_array($cellname, $unsed)) {
          return '-';
        }
        return $row[$cellname];
      }
    }
  }
  namespace {
    /**
     *
     */
    class Item_Category
    {
      /**
       * @static
       *
       * @param $description
       * @param $tax_type_id
       * @param $sales_account
       * @param $cogs_account
       * @param $inventory_account
       * @param $adjustment_account
       * @param $assembly_account
       * @param $units
       * @param $mb_flag
       * @param $dim1
       * @param $dim2
       * @param $no_sale
       */
      public static function add(
        $description,
        $tax_type_id,
        $sales_account,
        $cogs_account,
        $inventory_account,
        $adjustment_account,
        $assembly_account,
        $units,
        $mb_flag,
        $dim1,
        $dim2,
        $no_sale
      ) {
        $sql
          = "INSERT INTO stock_category (description, dflt_tax_type,
			dflt_units, dflt_mb_flag, dflt_sales_act, dflt_cogs_act,
			dflt_inventory_act, dflt_adjustment_act, dflt_assembly_act,
			dflt_dim1, dflt_dim2, dflt_no_sale)
		VALUES (" . DB::_escape($description) . "," . DB::_escape($tax_type_id) . "," . DB::_escape($units) . "," . DB::_escape($mb_flag) . "," . DB::_escape(
          $sales_account
        ) . "," . DB::_escape($cogs_account) . "," . DB::_escape($inventory_account) . "," . DB::_escape($adjustment_account) . "," . DB::_escape(
          $assembly_account
        ) . "," . DB::_escape($dim1) . "," . DB::_escape($dim2) . "," . DB::_escape($no_sale) . ")";
        DB::_query($sql, "an item category could not be added");
      }
      /**
       * @static
       *
       * @param $id
       * @param $description
       * @param $tax_type_id
       * @param $sales_account
       * @param $cogs_account
       * @param $inventory_account
       * @param $adjustment_account
       * @param $assembly_account
       * @param $units
       * @param $mb_flag
       * @param $dim1
       * @param $dim2
       * @param $no_sale
       */
      public static function update(
        $id,
        $description,
        $tax_type_id,
        $sales_account,
        $cogs_account,
        $inventory_account,
        $adjustment_account,
        $assembly_account,
        $units,
        $mb_flag,
        $dim1,
        $dim2,
        $no_sale
      ) {
        $sql = "UPDATE stock_category SET " . "description = " . DB::_escape($description) . "," . "dflt_tax_type = " . DB::_escape(
          $tax_type_id
        ) . "," . "dflt_units = " . DB::_escape($units) . "," . "dflt_mb_flag = " . DB::_escape($mb_flag) . "," . "dflt_sales_act = " . DB::_escape(
          $sales_account
        ) . "," . "dflt_cogs_act = " . DB::_escape($cogs_account) . "," . "dflt_inventory_act = " . DB::_escape($inventory_account) . "," . "dflt_adjustment_act = " . DB::_escape(
          $adjustment_account
        ) . "," . "dflt_assembly_act = " . DB::_escape($assembly_account) . "," . "dflt_dim1 = " . DB::_escape($dim1) . "," . "dflt_dim2 = " . DB::_escape(
          $dim2
        ) . "," . "dflt_no_sale = " . DB::_escape($no_sale) . "WHERE category_id = " . DB::_escape($id);
        DB::_query($sql, "an item category could not be updated");
      }
      /**
       * @static
       *
       * @param $id
       */
      public static function delete($id) {
        $sql = "DELETE FROM stock_category WHERE category_id=" . DB::_escape($id);
        DB::_query($sql, "an item category could not be deleted");
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($id) {
        $sql    = "SELECT * FROM stock_category WHERE category_id=" . DB::_escape($id);
        $result = DB::_query($sql, "an item category could not be retrieved");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return mixed
       */
      public static function get_name($id) {
        $sql    = "SELECT description FROM stock_category WHERE category_id=" . DB::_escape($id);
        $result = DB::_query($sql, "could not get sales type");
        $row    = DB::_fetchRow($result);
        return $row[0];
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       * @param bool $spec_opt
       * @param bool $submit_on_change
       *
       * @return string
       */
      public static function select($name, $selected_id = null, $spec_opt = false, $submit_on_change = false) {
        $sql = "SELECT category_id, description, inactive FROM stock_category";
        return Forms::selectBox(
          $name,
          $selected_id,
          $sql,
          'category_id',
          'description',
          array(
               'order'         => 'category_id',
               'spec_option'   => $spec_opt,
               'spec_id'       => -1,
               'select_submit' => $submit_on_change,
               'async'         => true
          )
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $spec_opt
       * @param bool $submit_on_change
       */
      public static function cells($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false) {
        if ($label != null) {
          echo "<td>$label</td>\n";
        }
        echo "<td>";
        echo Item_Category::select($name, $selected_id, $spec_opt, $submit_on_change);
        echo "</td>\n";
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $spec_opt
       * @param bool $submit_on_change
       */
      public static function row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false) {
        echo "<tr><td class='label'>$label</td>";
        Item_Category::cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
        echo "</tr>\n";
      }
    }
  }
