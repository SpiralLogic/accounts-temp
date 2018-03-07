<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class WO_Requirements {
    /**
     * @static
     *
     * @param $woid
     *
     * @return null|PDOStatement
     */
    public static function get($woid) {
      $sql
        = "SELECT wo_requirements.*, stock_master.description,
        stock_master.mb_flag,
        locations.location_name,
        workcentres.name AS WorkCentreDescription FROM
        (wo_requirements, locations, " . "workcentres) INNER JOIN stock_master ON
        wo_requirements.stock_id = stock_master.stock_id
        WHERE workorder_id=" . DB::_escape($woid) . "
        AND locations.loc_code = wo_requirements.loc_code
        AND workcentres.id=workcentre";
      return DB::_query($sql, "The work order requirements could not be retrieved");
    }
    /**
     * @static
     *
     * @param $woid
     * @param $stock_id
     */
    public static function add($woid, $stock_id) {
      // create Work Order Requirements based on the bom
      $result = WO::get_bom($stock_id);
      while ($myrow = DB::_fetch($result)) {
        $sql
          = "INSERT INTO wo_requirements (workorder_id, stock_id, workcentre, units_req, loc_code)
            VALUES (" . DB::_escape($woid) . ", '" . $myrow["component"] . "', '" . $myrow["workcentre_added"] . "', '" . $myrow["quantity"] . "', '" . $myrow["loc_code"] . "')";
        DB::_query($sql, "The work order requirements could not be added");
      }
    }
    /**
     * @static
     *
     * @param $woid
     */
    public static function delete($woid) {
      $sql = "DELETE FROM wo_requirements WHERE workorder_id=" . DB::_escape($woid);
      DB::_query($sql, "The work order requirements could not be deleted");
    }
    /**
     * @static
     *
     * @param $woid
     * @param $stock_id
     * @param $quantity
     */
    public static function update($woid, $stock_id, $quantity) {
      $sql = "UPDATE wo_requirements SET units_issued = units_issued + " . DB::_escape($quantity) . "
        WHERE workorder_id = " . DB::_escape($woid) . " AND stock_id = " . DB::_escape($stock_id);
      DB::_query($sql, "The work requirements issued quantity couldn't be updated");
    }
    /**
     * @static
     *
     * @param null $type
     * @param      $woid
     */
    public static function void($type = null, $woid) {
      $sql = "UPDATE wo_requirements SET units_issued = 0 WHERE workorder_id = " . DB::_escape($woid);
      DB::_query($sql, "The work requirements issued quantity couldn't be voided");
    }
    /**
     * @static
     *
     * @param      $woid
     * @param      $quantity
     * @param bool $show_qoh
     * @param null $date
     */
    public static function display($woid, $quantity, $show_qoh = false, $date = null) {
      $result = WO_Requirements::get($woid);
      if (DB::_numRows($result) == 0) {
        Display::note(_("There are no Requirements for this Order."), 1, 0);
      } else {
        Table::start('padded grid width90');
        $th = array(
          _("Component"),
          _("From Location"),
          _("Work Centre"),
          _("Unit Quantity"),
          _("Total Quantity"),
          _("Units Issued"),
          _("On Hand")
        );
        Table::header($th);
        $k          = 0; //row colour counter
        $has_marked = false;
        if ($date == null) {
          $date = Dates::_today();
        }
        while ($myrow = DB::_fetch($result)) {
          $qoh      = 0;
          $show_qoh = true;
          // if it's a non-stock item (eg. service) don't show qoh
          if (!WO::has_stock_holding($myrow["mb_flag"])) {
            $show_qoh = false;
          }
          if ($show_qoh) {
            $qoh = Item::get_qoh_on_date($myrow["stock_id"], $myrow["loc_code"], $date);
          }
          if ($show_qoh && ($myrow["units_req"] * $quantity > $qoh) && !DB_Company::_get_pref('allow_negative_stock')
          ) {
            // oops, we don't have enough of one of the component items
            echo "<tr class='stockmankobg'>";
            $has_marked = true;
          } else {
          }
          if (User::_show_codes()) {
            Cell::label($myrow["stock_id"] . " - " . $myrow["description"]);
          } else {
            Cell::label($myrow["description"]);
          }
          Cell::label($myrow["location_name"]);
          Cell::label($myrow["WorkCentreDescription"]);
          $dec = Item::qty_dec($myrow["stock_id"]);
          Cell::qty($myrow["units_req"], false, $dec);
          Cell::qty($myrow["units_req"] * $quantity, false, $dec);
          Cell::qty($myrow["units_issued"], false, $dec);
          if ($show_qoh) {
            Cell::qty($qoh, false, $dec);
          } else {
            Cell::label("");
          }
          echo '</tr>';
        }
        Table::end();
        if ($has_marked) {
          Display::note(_("Marked items have insufficient quantities in stock."), 0, 0, "class='red'");
        }
      }
    }
  }

