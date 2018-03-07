<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\App\Item\Item;
  use ADV\Core\JS;

  if (REQUEST_AJAX) {
    if (isset($_GET['term'])) {
      $data = Item::searchOrder($_GET['term'], $_GET['UniqueID']);
    } elseif (isset($_POST['id'])) {
      if (isset($_POST['name'])) {
        $item = new Item($_POST);
        $item->save($_POST);
      } else {
        $item = new Item($_POST['id']);
      }
      $data['item'] = $item;
    }
    if (isset($_GET['page'])) {
      $data['page'] = $_GET['page'];
    }
    JS::_renderJSON($data, JSON_NUMERIC_CHECK);
  }
