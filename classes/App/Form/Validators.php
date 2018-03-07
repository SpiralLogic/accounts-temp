<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      27/08/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Form;

  /**
   *
   */
  class Validators
  {
    /**
     * @param $name
     */
    public function exists($name) {
      Input::_post($name);
    }
  }
