<?php
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_("Email Statements"), SA_OPEN);
  $sql = "SELECT DISTINCT db.*, b.email , b.phone FROM debtor_balances db, branches b WHERE db.debtor_id = b.debtor_id AND LENGTH(b.email)>0 AND b.branch_ref LIKE "
    . Debtor_Account::ACCOUNTS . " AND
  Balance>0";
  DB::_query($sql, "The customer details could not be retrieved");
  $rows = DB::_fetchAll();
  $balance = $due = $overdue1 = $overdue2 = $overdue3 = 0;
  foreach ($rows as $row) {
    $balance += $row['Balance'];
    $due += ($row['Balance'] - $row["Due"]);
    $overdue1 += ($row["Due"] - $row["Overdue1"]);
    $overdue2 += ($row["Overdue1"] - $row["Overdue2"]);
    $overdue3 += ($row["Overdue2"]);
  }
  $view = new View('sales/email_statements');
  $view->set(
    'totals',
    [
    'balance'  => Num::_priceFormat($balance),
    'due'      => Num::_priceFormat($due),
    'overdue1' => Num::_priceFormat($overdue1),
    'overdue2' => Num::_priceFormat($overdue2),
    'overdue3' => Num::_priceFormat($overdue2)
    ]
  );
  $view->set('rows', $rows);
  $view->render();
  Page::end();
