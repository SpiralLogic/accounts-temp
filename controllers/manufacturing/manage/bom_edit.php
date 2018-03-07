<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Bill Of Materials"), SA_BOM);
  Validation::check(Validation::BOM_ITEMS, _("There are no manufactured or kit items defined in the system."), STOCK_MANUFACTURE);
  Validation::check(Validation::WORKCENTRES, _("There are no work centres defined in the system. BOMs require at least one work centre be defined."));
  list($Mode, $selected_id) = Page::simple_mode(true);
  $selected_component = $selected_id;
  //if (isset($_GET["NewItem"]))
  //{
  //	$_POST['stock_id'] = $_GET["NewItem"];
  //}
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
    $selected_parent   = $_GET['stock_id'];
  }
  /* selected_parent could come from a post or a get */
  /*if (isset($_GET["selected_parent"])) {
       $selected_parent = $_GET["selected_parent"];
     } elseif (isset($_POST["selected_parent"])) {
       $selected_parent = $_POST["selected_parent"];
     }
     */
  /* selected_component could also come from a post or a get */
  /*if (isset($_GET["selected_component"])) {
       $selected_component = $_GET["selected_component"];
     } else {
       $selected_component = Input::_post("selected_component",null,-1);
     }
     */
  /**
   * @param $ultimate_parent
   * @param $component_to_check
   *
   * @return int
   */
  function check_for_recursive_bom($ultimate_parent, $component_to_check) {
    /* returns true ie 1 if the bom contains the parent part as a component
                ie the bom is recursive otherwise false ie 0 */
    $sql    = "SELECT component FROM bom WHERE parent=" . DB::_escape($component_to_check);
    $result = DB::_query($sql, "could not check recursive bom");
    if ($result != 0) {
      while ($myrow = DB::_fetchRow($result)) {
        if ($myrow[0] == $ultimate_parent) {
          return 1;
        }
        if (check_for_recursive_bom($ultimate_parent, $myrow[0])) {
          return 1;
        }
      } //(while loop)
    } //end if $result is true
    return 0;
  } //end of function check_for_recursive_bom
  /**
   * @param $selected_parent
   */
  function display_bom_items($selected_parent) {
    $result = WO::get_bom($selected_parent);
    Ajax::_start_div('bom');
    Table::start('padded grid width60');
    $th = array(
      _("Code"),
      _("Description"),
      _("Location"),
      _("Work Centre"),
      _("Quantity"),
      _("Units"),
      '',
      ''
    );
    Table::header($th);
    $k = 0;
    while ($myrow = DB::_fetch($result)) {
      Cell::label($myrow["component"]);
      Cell::label($myrow["description"]);
      Cell::label($myrow["location_name"]);
      Cell::label($myrow["WorkCentreDescription"]);
      Cell::qty($myrow["quantity"], false, Item::qty_dec($myrow["component"]));
      Cell::label($myrow["units"]);
      Forms::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
      Forms::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
      echo '</tr>';
    } //END WHILE LIST LOOP
    Table::end();
    Ajax::_end_div();
  }

  /**
   * @param $selected_parent
   * @param $selected_component
   *
   * @return mixed
   */
  function on_submit($selected_parent, $selected_component = -1) {
    if (!Validation::post_num('quantity', 0)) {
      Event::error(_("The quantity entered must be numeric and greater than zero."));
      JS::_setFocus('quantity');
      return;
    }
    if ($selected_component != -1) {
      $sql = "UPDATE bom SET workcentre_added=" . DB::_escape($_POST['workcentre_added']) . ",loc_code=" . DB::_escape($_POST['loc_code']) . ",
            quantity= " . Validation::input_num('quantity') . "
            WHERE parent=" . DB::_escape($selected_parent) . "
            AND id=" . DB::_escape($selected_component);
      DB::_query($sql, "could not update bom");
      Event::success(_('Selected component has been updated'));
      $Mode = MODE_RESET;
    } else {
      /*Selected component is null cos no item selected on first time round
                        so must be adding a record must be Submitting new entries in the new
                        component form */
      //need to check not recursive bom component of itself!
      if (!check_for_recursive_bom($selected_parent, $_POST['component'])) {
        /*Now check to see that the component is not already on the bom */
        $sql
                = "SELECT component FROM bom
                WHERE parent=" . DB::_escape($selected_parent) . "
                AND component=" . DB::_escape($_POST['component']) . "
                AND workcentre_added=" . DB::_escape($_POST['workcentre_added']) . "
                AND loc_code=" . DB::_escape($_POST['loc_code']);
        $result = DB::_query($sql, "check failed");
        if (DB::_numRows($result) == 0) {
          $sql
            = "INSERT INTO bom (parent, component, workcentre_added, loc_code, quantity)
                    VALUES (" . DB::_escape($selected_parent) . ", " . DB::_escape($_POST['component']) . "," . DB::_escape($_POST['workcentre_added']) . ", " . DB::_escape(
            $_POST['loc_code']
          ) . ", " . Validation::input_num('quantity') . ")";
          DB::_query($sql, "check failed");
          Event::notice(_("A new component part has been added to the bill of material for this item."));
          $Mode = MODE_RESET;
        } else {
          /*The component must already be on the bom */
          Event::error(_("The selected component is already on this bom. You can modify it's quantity but it cannot appear more than once on the same bom."));
        }
      } //end of if its not a recursive bom
      else {
        Event::error(_("The selected component is a parent of the current item. Recursive BOMs are not allowed."));
      }
    }
  }

  if ($Mode == MODE_DELETE) {
    $sql = "DELETE FROM bom WHERE id=" . DB::_escape($selected_id);
    DB::_query($sql, "Could not delete this bom components");
    Event::notice(_("The component item has been deleted from this bom"));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST['quantity']);
  }
  Forms::start();
  Forms::start(false);
  Table::start('noborder');
  Item_UI::manufactured_row(_("Select a manufacturable item:"), 'stock_id', null, false, true);
  if (Forms::isListUpdated('stock_id')) {
    Ajax::_activate('_page_body');
  }
  Table::end();
  echo "<br>";
  Forms::end();
  if (Input::_post('stock_id') != '') { //Parent Item selected so display bom or edit component
    $selected_parent = $_POST['stock_id'];
    if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
      on_submit($selected_parent, $selected_id);
    }
    Forms::start();
    display_bom_items($selected_parent);
    echo '<br>';
    Table::start('standard');
    if ($selected_id != -1) {
      if ($Mode == MODE_EDIT) {
        //editing a selected component from the link to the line item
        $sql                       = "SELECT bom.*,stock_master.description FROM " . "bom,stock_master
                WHERE id=" . DB::_escape($selected_id) . "
                AND stock_master.stock_id=bom.component";
        $result                    = DB::_query($sql, "could not get bom");
        $myrow                     = DB::_fetch($result);
        $_POST['loc_code']         = $myrow["loc_code"];
        $_POST['component']        = $myrow["component"]; // by Tom Moulton
        $_POST['workcentre_added'] = $myrow["workcentre_added"];
        $_POST['quantity']         = Num::_format($myrow["quantity"], Item::qty_dec($myrow["component"]));
        Table::label(_("Component:"), $myrow["component"] . " - " . $myrow["description"]);
      }
      Forms::hidden('selected_id', $selected_id);
    } else {
      echo '<tr>';
      Cell::label(_("Component:"));
      echo "<td>";
      echo Item_UI::component('component', $selected_parent, null, false, true);
      if (Input::_post('_component_update')) {
        Ajax::_activate('quantity');
      }
      echo "</td>";
      echo '</tr>';
    }
    Forms::hidden('stock_id', $selected_parent);
    Inv_Location::row(_("Location to Draw From:"), 'loc_code', null);
    workcenter_list_row(_("Work Centre Added:"), 'workcentre_added', null);
    $dec               = Item::qty_dec(Input::_post('component'));
    $_POST['quantity'] = Num::_format(Validation::input_num('quantity', 1), $dec);
    Forms::qtyRow(_("Quantity:"), 'quantity', null, null, null, $dec);
    Table::end(1);
    Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
    Forms::end();
  }
  // ----------------------------------------------------------------------------------
  Page::end();

