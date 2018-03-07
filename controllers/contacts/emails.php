<?php
  use ADV\App\Debtor\Debtor;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (!REQUEST_AJAX) {
    header("Location: /");
    exit();
  }
  $content = false;
  if (Input::_hasPost('type', 'id')) {
    if ($_POST['type'] == CT_CUSTOMER) {
      $content = Debtor::getEmailDialogue($_POST['id']);
    } elseif ($_POST['type'] == CT_SUPPLIER) {
      $content = Creditor::getEmailDialogue($_POST['id']);
    }
    if ($content == false) {
      echo HTML::h3(null, 'No email addresses available.', array('class' => 'center bold top40 font15'), false);
    } else {
      echo $content;
    }
  }
  JS::_render();
