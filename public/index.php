<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (!isset($_SERVER['QUERY_STRING']) && preg_match('/\.(?:js|css)$/', $_SERVER["REQUEST_URI"])) {
    $_SERVER['DOCUMENT_URI'] = '/assets.php';
    $_SERVER['QUERY_STRING'] = $_SERVER['REQUEST_URI'];
  } elseif (preg_match('/\.(?:png|jpg|jpeg|gif|eot|woff|ttf|svg)$/', $_SERVER["REQUEST_URI"])) {
    return false;
  }
  require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';
