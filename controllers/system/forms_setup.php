<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Forms Setup"), SA_FORMSETUP);
  if (isset($_POST['setprefs'])) {
    $systypes = SysTypes::get();
    DB::_begin();
    while ($type = DB::_fetch($systypes)) {
      Ref::save($type["type_id"], $_POST['id' . $type["type_id"]]);
    }
    DB::_commit();
    Event::success(_("Forms settings have been updated."));
  }
  Forms::start();
  Table::startOuter('standard');
  $systypes = SysTypes::get();
  Table::section(1);
  $th = array(_("Form"), _("Next Reference"));
  Table::header($th);
  $i = 0;
  while ($type = DB::_fetch($systypes)) {
    if ($i++ == ST_CUSTCREDIT) {
      Table::section(2);
      Table::header($th);
    }
    Forms::refRow(SysTypes::$names[$type["type_id"]], 'id' . $type["type_id"], '', $type["next_reference"]);
  }
  Table::endOuter(1);
  Forms::submitCenter('setprefs', _("Update"), true, '', 'default');
  Forms::end(2);
  Page::end();

