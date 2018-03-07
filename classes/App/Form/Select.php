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

  use ADV\Core\HTML;

  /** **/
  class Select extends Field
  {
    use \ADV\Core\Traits\SetFromArray;

    public $multi = false;
    public $async = true;
    public $sel_hint = null;
    public $disabled = null;
    public $items = [];
    public $spec_id = 0;
    public $spec_option = false;
    /**
     * @param       $name
     * @param       $items
     * @param array $options
     */
    public function __construct($name, $items, array $options = []) {
      parent::__construct('select', $name);
      $this->items = $items;
      $this->setFromArray($options);
    }
    /**
     * @param $selected
     */
    public function value($selected) {
      $this->value = $this->multi ? (array)$selected : $selected;
    }
    /**
     * @return \ADV\Core\HTML|string
     */
    public function generate() {
      if ($this->spec_option !== false) { // if special option used - add it
        array_unshift($this->items, [$this->spec_id => $this->spec_option]);
      }
      if ($this->default === null) {
        reset($this->items);
        $this->default = key($this->items);
      }
      $selector = '';
      $HTML     = new HTML;
      foreach ($this->items as $value => $label) {
        $selector .= $HTML->option(null, $label, ['value' => $value], false);
      }
      $this['multiple'] = $this->multi;
      $this['disabled'] = $this->disabled;
      $this['class']    = $this['class'] . ' combo';
      $this['title']    = $this->sel_hint;
      $selector         = $HTML->span("_" . $this->name . "_sel", ['class' => 'combodiv'])->select($this->id, $selector, $this->attr, false)->_span()->__toString();
      return $selector;
    }
    /**
     * @return string
     */
    public function __toString() {
      $value            = (isset($this->value)) ? $this->value : $this->default;
      $this->attr['id'] = $this->id;
      $values           = (array)$value;
      $control          = $this->generate();
      foreach ($values as $v) {
        $control = preg_replace('/value=([\'"]?)' . preg_quote($v) . '\1/', 'selected \0', $control);
      }
      $control = $this->formatAddOns($control);
      if ($this->label) {
        $control = "<label for='" . $this->id . "'><span>" . $this->label . "</span>$control</label>";
      }
      return $control;
    }
  }
