<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Item_GL
  {

    /** @var */
    public $code_id;
    /** @var */
    public $dimension_id;
    /** @var */
    public $dimension2_id;
    /** @var */
    public $amount;
    /** @var */
    public $reference;
    /** @var mixed|null * */
    public $description;
    /**
     * @param      $code_id
     * @param      $dimension_id
     * @param      $dimension2_id
     * @param      $amount
     * @param      $reference
     * @param null $description
     */
    public function __construct($code_id, $dimension_id, $dimension2_id, $amount, $reference, $description = null) {
      //echo "adding $index, $code_id, $dimension_id, $amount, $reference<br>";
      if ($description == null) {
        $this->description = GL_Account::get_name($code_id);
      } else {
        $this->description = $description;
      }
      $this->code_id       = $code_id;
      $this->dimension_id  = $dimension_id;
      $this->dimension2_id = $dimension2_id;
      $this->amount        = $amount;
      $this->reference     = $reference;
    }
  }

