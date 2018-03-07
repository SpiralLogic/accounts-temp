<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  return array(
    'enabled'    => false, //
    'sql'        => false, //
    'pdf'        => false, //
    'email'      => true, //
    'query_log'  => false,
    'select_log' => false, //
    'throttling' => 10, // Log file for error/warning messages. Should be set to any location
    'log_file'   => ROOT_DOC . 'tmp/errors.log',
  );
