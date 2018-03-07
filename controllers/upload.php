<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\UploadHandler;

  //$upload_dir = ROOT_DOC.'sales/upload/';
  $order = (isset($_SESSION['order_no'])) ? $_SESSION['order_no'] : (isset($_GET['order'])) ? $_GET['order'] : false;
  if ($order) {
    $upload_handler = new UploadHandler($order, $o);
    switch (REQUEST_METHOD) {
      case 'HEAD':
      case 'GET':
        $upload_handler->get();
        break;
      case 'POST':
        $upload_handler->post();
        break;
      case 'DELETE':
        $upload_handler->delete();
        break;
      default:
        header('HTTP/1.0 405 Method Not Allowed');
    }
  }
