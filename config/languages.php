<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /* How to make new entries here

   -- if adding languages at the beginning of the list, make sure it's index is set to 0 (it has ' 0 => ')
   -- 'code' should match the name of the directory for the language under \lang
   -- 'name' is the name that will be displayed in the language selection list (in Users and Display Setup)
   -- 'rtl' only needs to be set for right-to-left languages like Arabic and Hebrew

   */
  return array(
    'installed' => array(
      0 => array(
        'code'     => 'en_AU', //
        'name'     => 'Australia', //
        'encoding' => 'UTF-8' //
      ),
      array(
        'code'     => 'en_US', //
        'name'     => 'English', //
        'encoding' => 'UTF-8' //
      )
    )
  );

