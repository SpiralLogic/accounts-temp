<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      6/10/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Form;

  /**
   *
   */
  class Checkbox extends Field
  {
    /**
     * @param $name
     */
    public function __construct($name) {
      parent::__construct('checkbox', $name);
      $this->attr['type'] = 'checkbox';
    }
    /**
     * @param $value
     */
    public function value($value) {
      $this->value = !!$value;
    }
    /**
     * @return string
     */
    public function __toString() {
      $value                 = (isset($this->value)) ? $this->value : $this->default;
      $this->attr['id']      = $this->id;
      $this->attr['checked'] = !!$value;
      $control               = $this->makeElement('input', $this->attr, false);
      $control               = $this->formatAddOns($control);
      if ($this->label) {
        $control = "<label for='" . $this->id . "'><span>" . $this->label . "</span>$control</label>";
      }
      return $control;
    }
  }
