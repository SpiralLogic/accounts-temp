<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Inv {
    use ADV\Core\DB\DB;

    /**

     */
    class Location extends \ADV\App\DB\Base  implements \ADV\App\Pager\Pageable
    {

      const OUTWARD = 'OUTWARD';
      const INWARD  = 'INWARD';
      const BOTH    = 'BOTH';
      const SPECIAL = 'SPECIAL';
      protected $_table = 'locations';
      protected $_classname = 'Location';
      protected $_id_column = 'id';
      public $id = 0;
      public $loc_code;
      public $location_name;
      public $delivery_address;
      public $phone;
      public $phone2;
      public $fax;
      public $email;
      public $contact;
      public $inactive = 0;
      public $type = self::BOTH;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        $this->loc_code = strtoupper($this->loc_code);
        if (strlen($this->loc_code) > 5) {
          return $this->status(false, 'Loc_code must be not be longer than 5 characters!', 'loc_code');
        }
        if (strlen($this->location_name) > 60) {
          return $this->status(false, 'Location_name must be not be longer than 60 characters!', 'location_name');
        }
        if (strlen($this->delivery_address) > 255) {
          return $this->status(false, 'Delivery_address must be not be longer than 255 characters!', 'delivery_address');
        }
        if (strlen($this->phone) > 30) {
          return $this->status(false, 'Phone must be not be longer than 30 characters!', 'phone');
        }
        if (strlen($this->phone2) > 30) {
          return $this->status(false, 'Phone2 must be not be longer than 30 characters!', 'phone2');
        }
        if (strlen($this->fax) > 30) {
          return $this->status(false, 'Fax must be not be longer than 30 characters!', 'fax');
        }
        if (strlen($this->email) > 100) {
          return $this->status(false, 'Email must be not be longer than 100 characters!', 'email');
        }
        if (strlen($this->contact) > 30) {
          return $this->status(false, 'Contact must be not be longer than 30 characters!', 'contact');
        }
        if (!in_array($this->type, (new \ReflectionClass($this))->getConstants())) {
          return $this->status(false, 'Please choose valid type!', 'type');
        }
        return true;
      }
      /**
       * @param bool        $inactive
       * @param bool|string $type
       *
       * @internal param bool $special
       * @return array
       */
      public static function getAll($inactive = false, $type = self::BOTH) {
        $q = DB::_select()->from('locations');
        if (!$inactive) {
          $q->andWhere('inactive=', 0);
        }
        switch ($type) {
          case self::SPECIAL:
            $q->andWhere('type=', self::SPECIAL);
            break;
          case self::INWARD:
            $q->andWhere('type=', self::BOTH);
            $q->orWhere('type=', self::INWARD);
            break;
          case self::OUTWARD:
            $q->andWhere('type=', self::BOTH);
            $q->orWhere('type=', self::OUTWARD);
            break;
          default:
            $q->andWhere('type!=', self::SPECIAL);
        }
        return $q->fetch()->all();
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $sql    = "SELECT COUNT(*) FROM stock_moves WHERE loc_code=" . DB::_escape($this->loc_code);
        $result = DB::_query($sql, "could not query stock moves");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          return $this->status(false, _("Cannot delete this location because item movements have been created using this location."));
        }
        $sql    = "SELECT COUNT(*) FROM workorders WHERE loc_code=" . DB::_escape($this->loc_code);
        $result = DB::_query($sql, "could not query work orders");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          return $this->status(false, _("Cannot delete this location because it is used by some work orders records."));
        }
        $sql    = "SELECT COUNT(*) FROM branches WHERE default_location=" . DB::_escape($this->loc_code);
        $result = DB::_query($sql, "could not query customer branches");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          return $this->status(false, _("Cannot delete this location because it is used by some branch records as the default location to deliver from."));
        }
        $sql    = "SELECT COUNT(*) FROM bom WHERE loc_code=" . DB::_escape($this->loc_code);
        $result = DB::_query($sql, "could not query bom");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          return $this->status(false, _("Cannot delete this location because it is used by some related records in other tables."));
        }
        $sql    = "SELECT COUNT(*) FROM grn_batch WHERE loc_code=" . DB::_escape($this->loc_code);
        $result = DB::_query($sql, "could not query grn batch");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          return $this->status(false, _("Cannot delete this location because it is used by some related records in other tables."));
        }
        $sql    = "SELECT COUNT(*) FROM purch_orders WHERE into_stock_location=" . DB::_escape($this->loc_code);
        $result = DB::_query($sql, "could not query purch orders");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          return $this->status(false, _("Cannot delete this location because it is used by some related records in other tables."));
        }
        $sql    = "SELECT COUNT(*) FROM sales_orders WHERE from_stk_loc=" . DB::_escape($this->loc_code);
        $result = DB::_query($sql, "could not query sales orders");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          return $this->status(false, _("Cannot delete this location because it is used by some related records in other tables."));
        }
        $sql    = "SELECT COUNT(*) FROM sales_pos WHERE pos_location=" . DB::_escape($this->loc_code);
        $result = DB::_query($sql, "could not query sales pos");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          return $this->status(false, _("Cannot delete this location because it is used by some related records in other tables."));
        }
        return parent::delete();
      }
      /**
       * @return array
       */
      public static function selectBoxItems() {
        $self  = new static();
        $q     = DB::_select($self->_id_column, 'location_name')->from($self->_table)->andWhere('inactive=', 0)->fetch();
        $items = [];
        foreach ($q as $row) {
          $items[$row[$self->_id_column]] = $row['location_name'];
        }
        return $items;
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        $cols = [
          ['type' => 'skip'],
          _("Location Code"), //
          _("Location Name"), //
          _("Address"), //
          _("Phone"), //
          ['type' => 'skip'],
          _("Fax"),
          _("Email"),
          ['type' => 'skip'],
          _("Inactive") => ['type' => 'inactive'],
          _("type"),
        ];
        return $cols;
      }
    }
  }
  namespace {
    use ADV\Core\DB\DB;
    use ADV\App\Forms;

    /**

     */
    class Inv_Location
    {

      /**
       * @static
       *
       * @param $loc_code
       * @param $location_name
       * @param $delivery_address
       * @param $phone
       * @param $phone2
       * @param $fax
       * @param $email
       * @param $contact
       */
      public static function add($loc_code, $location_name, $delivery_address, $phone, $phone2, $fax, $email, $contact) {
        $sql
          = "INSERT INTO locations (loc_code, location_name, delivery_address, phone, phone2, fax, email, contact)
        VALUES (" . DB::_escape($loc_code) . ", " . DB::_escape($location_name) . ", " . DB::_escape($delivery_address) . ", " . DB::_escape($phone) . ", " . DB::_escape(
          $phone2
        ) . ", " . DB::_escape($fax) . ", " . DB::_escape($email) . ", " . DB::_escape($contact) . ")";
        DB::_query($sql, "a location could not be added");
        /* Also need to add stock_location records for all existing items */
        $sql
          = "INSERT INTO stock_location (loc_code, stock_id, reorder_level)
        SELECT " . DB::_escape($loc_code) . ", stock_master.stock_id, 0 FROM stock_master";
        DB::_query($sql, "a location could not be added");
      }
      /**
       * @static
       *
       * @param $loc_code
       * @param $location_name
       * @param $delivery_address
       * @param $phone
       * @param $phone2
       * @param $fax
       * @param $email
       * @param $contact
       */
      public static function update($loc_code, $location_name, $delivery_address, $phone, $phone2, $fax, $email, $contact) {
        $sql = "UPDATE locations SET location_name=" . DB::_escape($location_name) . ", delivery_address=" . DB::_escape($delivery_address) . ", phone=" . DB::_escape(
          $phone
        ) . ", phone2=" . DB::_escape($phone2) . ", fax=" . DB::_escape($fax) . ", email=" . DB::_escape($email) . ", contact=" . DB::_escape(
          $contact
        ) . " WHERE loc_code = " . DB::_escape($loc_code);
        DB::_query($sql, "a location could not be updated");
      }
      /**
       * @static
       *
       * @param $item_location
       */
      public static function delete($item_location) {
        $sql = "DELETE FROM locations WHERE loc_code=" . DB::_escape($item_location);
        DB::_query($sql, "a location could not be deleted");
        $sql = "DELETE FROM stock_location WHERE loc_code =" . DB::_escape($item_location);
        DB::_query($sql, "a location could not be deleted");
      }
      /**
       * @static
       *
       * @param $item_location
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($item_location) {
        $sql    = "SELECT * FROM locations WHERE loc_code=" . DB::_escape($item_location);
        $result = DB::_query($sql, "a location could not be retrieved");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $stock_id
       * @param $loc_code
       * @param $reorder_level
       */
      public static function set_reorder($stock_id, $loc_code, $reorder_level) {
        $sql
          = "UPDATE stock_location SET reorder_level = $reorder_level
        WHERE stock_id = " . DB::_escape($stock_id) . " AND loc_code = " . DB::_escape($loc_code);
        DB::_query($sql, "an item reorder could not be set");
      }
      /**
       * @static
       *
       * @param $stock_id
       * @param $loc_code
       * @param $primary_location
       * @param $secondary_location
       */
      public static function set_shelves($stock_id, $loc_code, $primary_location, $secondary_location) {
        $sql = "UPDATE stock_location SET shelf_primary = " . DB::_escape($primary_location) . " , shelf_secondary = " . DB::_escape(
          $secondary_location
        ) . " WHERE stock_id = " . DB::_escape($stock_id) . " AND loc_code = " . DB::_escape($loc_code);
        DB::_query($sql, "an item reorder could not be set");
      }
      /**
       * @static
       *
       * @param $stock_id
       *
       * @return null|PDOStatement
       */
      public static function get_details($stock_id) {
        $sql
          = "SELECT stock_location.*, locations.location_name
        FROM stock_location, locations
        WHERE stock_location.loc_code=locations.loc_code
        AND stock_location.stock_id = " . DB::_escape($stock_id) . " AND stock_location.loc_code <> " . DB::_escape(
          LOC_DROP_SHIP
        ) . " AND stock_location.loc_code <> " . DB::_escape(LOC_NOT_FAXED_YET) . " ORDER BY stock_location.loc_code";
        return DB::_query($sql, "an item reorder could not be retreived");
      }
      /**
       * @static
       *
       * @param $loc_code
       *
       * @return mixed
       */
      public static function get_name($loc_code) {
        $sql    = "SELECT location_name FROM locations WHERE loc_code=" . DB::_escape($loc_code);
        $result = DB::_query($sql, "could not retreive the location name for $loc_code");
        if (DB::_numRows($result) == 1) {
          $row = DB::_fetchRow($result);
          return $row[0];
        }
        return Event::error("could not retreive the location name for $loc_code", $sql, true);
      }
      /***
       * @static
       *
       * @param $order
       *
       * @return \ADV\Core\DB\Query\Result|null
       * find inventory location for given transaction

       */
      public static function get_for_trans($order) {
        $sql    = "SELECT locations.* FROM stock_moves, locations WHERE stock_moves.type=" . DB::_escape($order->trans_type) . " AND trans_no=" . key(
          $order->trans_no
        ) . " AND qty!=0  AND locations.loc_code=stock_moves.loc_code";
        $result = DB::_query($sql, 'Retreiving inventory location');
        if (DB::_numRows($result)) {
          return DB::_fetch($result);
        }
        return null;
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $submit_on_change
       *
       * @return string
       */
      public static function select($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
        $sql = "SELECT loc_code, location_name, inactive FROM locations";
        if (!$selected_id && !isset($_POST[$name])) {
          $selected_id = $all_option === true ? -1 : Config::_get('default.location');
        }
        return Forms::selectBox(
          $name,
          $selected_id,
          $sql,
          'loc_code',
          'location_name',
          array(
            'spec_option'   => $all_option === true ? _("All Locations") : $all_option,
            'spec_id'       => ALL_TEXT,
            'select_submit' => $submit_on_change,
            'class'         => 'med'
          )
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $submit_on_change
       */
      public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
        if ($label != null) {
          echo "<td class='label'><label for=\"$name\"> $label</label></td>";
        }
        echo "<td>";
        echo Inv_Location::select($name, $selected_id, $all_option, $submit_on_change);
        echo "</td>\n";
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $submit_on_change
       */
      public static function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
        echo "<tr><td class='label'>$label</td>";
        Inv_Location::cells(null, $name, $selected_id, $all_option, $submit_on_change);
        echo "</tr>\n";
      }
    }
  }
