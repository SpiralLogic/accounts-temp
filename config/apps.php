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
    'active'  => array(
      'Sales'             => ['enabled'=> true], //
      'Customers'         => ['enabled'=> true, 'route'=> '/contacts/manage/customers'], //
      'Purchases'         => ['enabled'=> true], //
      'Suppliers'         => ['enabled'=> true, 'route'=> '/contacts/manage/suppliers'], //
      'Inventory'         => ['enabled'=> true], //
      'Manufacturing'     => ['enabled'=> true], //
      'Banking'           => ['enabled'=> true], //
      'Gl'                => ['enabled'=> false], //

      'Advanced'          => ['enabled'=> true], //
      'System'            => ['enabled'=> true]
    ), //
    'default' => 'Sales'
  );
