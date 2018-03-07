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
  if (isset($_GET['postcode']) && isset($_GET['term'])) {
    $data = Postcode::searchByPostcode($_GET['term']);
  } elseif (isset($_GET['city']) && isset($_GET['term'])) {
    $data = Postcode::searchByCity($_GET['term']);
  }
  JS::_renderJSON($data, JSON_NUMERIC_CHECK);

