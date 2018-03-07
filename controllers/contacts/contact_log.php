<?php
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
  if (Input::_hasPost('contact_id', 'message', 'type')) {
    $message_id = Contact_Log::add($_POST['contact_id'], $_POST['contact_name'], $_POST['type'], $_POST['message']);
  }
  if (Input::_hasPost('contact_id', 'type')) {
    $contact_log = Contact_Log::read($_POST['contact_id'], $_POST['type']);
    JS::_renderJSON($contact_log);
  }
