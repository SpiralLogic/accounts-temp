<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  // For tag constants
  // Set up page security based on what type of tags we're working with
  if (Input::_get('type') == "account" || Input::_post('type') == TAG_ACCOUNT) {
    $security = SA_GLACCOUNTTAGS;
  } elseif (Input::_get('type') == "dimension" || Input::_post('type') == TAG_DIMENSION) {
    $security = SA_DIMTAGS;
  } else {
    $security = SA_DENIED;
  }
  // We use Input::_post('type') throughout this script, so convert $_GET vars
  // if Input::_post('type') is not set.
  if (!Input::_post('type')) {
    if (Input::_get('type') == "account") {
      $_POST['type'] = TAG_ACCOUNT;
    } elseif (Input::_get('type') == "dimension") {
      $_POST['type'] = TAG_DIMENSION;
    } else {
      die(_("Unspecified tag type"));
    }
  }
  // Set up page based on what type of tags we're working with
  switch (Input::_post('type')) {
    case TAG_ACCOUNT:
      // Account tags
      $_SESSION['page_title'] = _($help_context = "Account Tags");
      break;
    case TAG_DIMENSION:
      // Dimension tags
      $_SESSION['page_title'] = _($help_context = "Dimension Tags");
  }
  Page::start($_SESSION['page_title'], $security);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (can_process()) {
      if ($selected_id != -1) {
        if ($ret = Tags::update($selected_id, $_POST['name'], $_POST['description'])) {
          Event::success(_('Selected tag settings have been updated'));
        }
      } else {
        if ($ret = Tags::add(Input::_post('type'), $_POST['name'], $_POST['description'])) {
          Event::success(_('New tag has been added'));
        }
      }
      if ($ret) {
        $Mode = MODE_RESET;
      }
    }
  }
  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      Tags::delete($selected_id);
      Event::notice(_('Selected tag has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id   = -1;
    $_POST['name'] = $_POST['description'] = '';
  }
  $result = Tags::getAll(Input::_post('type'), Input::_hasPost('show_inactive'));
  Forms::start();
  Table::start('padded grid');
  $th = array(_("Tag Name"), _("Tag Description"), "", "");
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result)) {
    Cell::label($myrow['name']);
    Cell::label($myrow['description']);
    Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'tags', 'id');
    Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    echo '</tr>';
  }
  Forms::inactiveControlRow($th);
  Table::end(1);
  Table::start('standard');
  if ($selected_id != -1) // We've selected a tag
  {
    if ($Mode == MODE_EDIT) {
      // Editing an existing tag
      $myrow                = Tags::get($selected_id);
      $_POST['name']        = $myrow["name"];
      $_POST['description'] = $myrow["description"];
    }
    // Note the selected tag
    Forms::hidden('selected_id', $selected_id);
  }
  Forms::textRowEx(_("Tag Name:"), 'name', 15, 30);
  Forms::textRowEx(_("Tag Description:"), 'description', 40, 60);
  Forms::hidden('type');
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();
  /**
   * @return bool
   */
  function can_process() {
    if (strlen($_POST['name']) == 0) {
      Event::error(_("The tag name cannot be empty."));
      JS::_setFocus('name');
      return false;
    }
    return true;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function can_delete($selected_id) {
    if ($selected_id == -1) {
      return false;
    }
    $result = Tags::get_associated_records($selected_id);
    if (DB::_numRows($result) > 0) {
      Event::error(_("Cannot delete this tag because records have been created referring to it."));
      return false;
    }
    return true;
  }

