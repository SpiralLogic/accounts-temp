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
    'login'   => [
      'Jobsboard' => array('enabled' => false, 'init' => true),
      'Youtrack'  => array('enabled' => true, 'init' => true),
    ],
    'default' => [
      'Volusion' => array(
        'enabled'       => true,
        'init'          => true,
        'apiuser'       => 'admin@advancedroadsigns.com.au',
        'apikey'        => '0380046EE0ABED5E1EDF6D1BF27FFA9BC089D69916B6C07636A28AFADC297090',
        'apiurl'        => 'http://www.advancedroadsigns.com.au/net/WebService.aspx?',
        'product_url'   => 'http://www.advancedroadsigns.com.au/-p/',
        'url_extension' => '.htm',
      )
    ],
  );
