<?php
  use ADV\Core\DB\Utils;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\Config;
  use ADV\App\User;
  use ADV\App\Page;
  use ADV\Core\Ajax;
  use ADV\Core\Event;
  use ADV\Core\Input\Input;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (Input::_post('view')) {
    if (!Input::_post('backups')) {
      Event::error(_('Select backup file first.'));
    } else {
      $filename = PATH_BACKUP . Input::_post('backups');
      if (Ajax::_inAjax()) {
        Ajax::_popup($filename);
      } else {
        header('Content-type: application/octet-stream');
        header('Content-Length: ' . filesize($filename));
        header("Content-Disposition: inline; filename=$filename");
        readfile($filename);
        exit();
      }
    }
  }

  if (Input::_post('download')) {
    download_file(PATH_BACKUP . Input::_post('backups'));
    exit;
  }
  Page::start(_($help_context = "Backup and Restore Database"), SA_BACKUP);
  check_paths();
  $db_name     = User::_i()->company;
  $connections = Config::_getAll('db');
  $conn        = $connections[$db_name];
  if (Input::_post('creat')) {
    generate_backup($conn, Input::_post('comp'), Input::_post('comments'));
    Ajax::_activate('backups');
  }
  if (Input::_post('restore')) {
    if (Utils::import(PATH_BACKUP . Input::_post('backups'), $conn)) {
      Event::success(_("Restore backup completed."));
    }
  }
  if (Input::_post('deldump')) {
    if (unlink(PATH_BACKUP . Input::_post('backups'))) {
      Event::notice(_("File successfully deleted.") . " " . _("Filename") . ": " . Input::_post('backups'));
      Ajax::_activate('backups');
    } else {
      Event::error(_("Can't delete backup file."));
    }
  }

  if (Input::_post('upload')) {
    $tmpname = $_FILES['uploadfile']['tmp_name'];
    $fname   = $_FILES['uploadfile']['name'];
    if (!preg_match("/.sql(.zip|.gz)?$/", $fname)) {
      Event::error(_("You can only upload *.sql backup files"));
    } elseif (is_uploaded_file($tmpname)) {
      rename($tmpname, PATH_BACKUP . $fname);
      Event::notice("File uploaded to backup directory");
      Ajax::_activate('backups');
    } else {
      Event::error(_("File was not uploaded into the system."));
    }
  }
  Forms::start(true);
  Table::startOuter('standard');
  Table::section(1);
  Table::sectionTitle(_("Create backup"));
  Forms::textareaRow(_("Comments:"), 'comments', null, 30, 8);
  compress_list_row(_("Compression:"), 'comp');
  Forms::submitRow('creat', _("Create Backup"), false, "colspan=2 class='center'", '', 'process');
  Table::section(2);
  Table::sectionTitle(_("Backup scripts maintenance"));
  echo '<tr>';
  echo "<td style='padding-left:20px'class='left'>" . get_backup_file_combo() . "</td>";
  echo "<td class='top'>";
  Table::start();
  Forms::submitRow('view', _("View Backup"), false, '', '', true);
  Forms::submitRow('download', _("Download Backup"), false, '', '', false);
  Forms::submitRow('restore', _("Restore Backup"), false, '', '', 'process');
  Forms::submitConfirm('restore', _("You are about to restore database from backup file.\nDo you want to continue?"));
  Forms::submitRow('deldump', _("Delete Backup"), false, '', '', true);
  // don't use 'delete' name or IE js errors appear
  Forms::submitConfirm('deldump', sprintf(_("You are about to remove selected backup file.\nDo you want to continue ?")));
  Table::end();
  echo "</td>";
  echo '</tr>';
  echo '<tr>';
  echo "<td style='padding-left:20px' class='left'><input name='uploadfile' type='file'></td>";
  Forms::submitCells('upload', _("Upload file"), '', '', true);
  echo '</tr>';
  Table::endOuter();
  Forms::end();
  Page::end();

