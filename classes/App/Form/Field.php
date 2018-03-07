<?php

  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 25/08/12
   * Time: 5:04 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Form;

  /** **/
  class Field implements \ArrayAccess
  {
    use \ADV\Core\Traits\HTML;

    public $id;
    public $value = null;
    public $hide = false;
    public $default;
    public $validator;
    protected $attr = [];
    protected $extra = [];
    protected $name;
    protected $content = '';
    protected $label;
    protected $tag;
    protected $append;
    protected $prepend;
    protected $readonly = false;
    /**
     * @param $tag
     * @param $name
     *
     * @internal param $validator
     */
    public function __construct($tag, $name) {
      $this->tag = $tag;
      $this->name($name);
    }
    /**
     * @param $name
     * you
     */
    public function name($name) {
      $this->name = $this['name'] = $name;
      $this->id   = $this->nameToId();
    }
    /**
     * @param $label
     *
     * @return \ADV\App\Form\Field
     */
    public function label($label) {
      if ($label === null) {
        return $this;
      }
      $this->label = $label;
      if (!isset($this->attr['placeholder'])) {
        $this['placeholder'] = rtrim(trim($label), ':');
      }
      return $this;
    }
    /**
     * @param bool $on
     *
     * @return Field
     */
    public function focus($on = true) {
      $this->attr['autofocus'] = (bool) $on;
      return $this;
    }
    /**
     * @param bool $on
     *
     * @return Field
     */
    public function readonly($on = true) {
      $this->readonly = $on;
      return $this;
    }
    /**
     * @param $content
     *
     * @return Field
     */
    public function setContent($content) {
      $this->content = $content;
      return $this;
    }
    /**
     * @param $attr
     *
     * @return Field
     */
    public function mergeAttr($attr) {
      $this->attr = array_merge($this->attr, (array) $attr);
      return $this;
    }
    /**
     * Sets default value and value
     *
     * @param $value
     *
     * @return Field
     */
    public function value($value) {
      $this->value = $value;
      return $this;
    }
    /**
     * Sets default value and value
     *
     * @param $value
     *
     * @return Field
     */
    public function initial($value) {
      $this->value($value);
      $this->default = $this->value;
      return $this;
    }
    /**
     * @param $text
     *
     * @return \ADV\App\Form\Field
     */
    public function append($text) {
      $this->append = $text;
      return $this;
    }
    /**
     * @param $text
     *
     * @return \ADV\App\Form\Field
     */
    public function prepend($text) {
      $this->prepend = $text;
      return $this;
    }
    /**
     * @param $property
     * @param $value
     *
     * @return \ADV\App\Form\Field
     */
    public function extra($property, $value = null) {
      if ($value === null) {
        return $this->extra[$property];
      }
      $this->extra[$property] = $value;
      return $this;
    }
    /**
     * @param $validator
     *
     * @return \ADV\App\Form\Field
     * @internal param $function
     */
    public function setValidator($validator) {
      $this->validator = $validator;
      return $this;
    }
    /**
     * @return mixed
     */
    protected function nameToId() {
      return str_replace(['[', ']'], ['-', ''], $this->name);
    }
    /**
     * @param $content
     *
     * @return string
     */
    protected function formatAddOns($content) {
      if ($this->append && $this->prepend) {
        $return = "<span class='input-append input-prepend'><span class='add-on'>" . $this->prepend . "</span>";
      } elseif ($this->append) {
        $return = "<span class='input-append'>";
      } elseif ($this->prepend) {
        $return = "<span class='input-prepend'><span class='add-on'>" . $this->prepend . "</span>";
      } else {
        return $content;
      }
      $return .= $content;
      if ($this->append) {
        $return .= "<span class='add-on' >" . $this->append . "</span>";
      }
      return $return . "</span>";
    }
    /**
     * @return string
     */
    public function __toString() {
      $value = (isset($this->value)) ? $this->value : $this->default;
      if ($this->readonly) {
        $tag           = 'span';
        $this->content = $value;
        $this->attr = ['class' => 'readonly'];
      } else {
        $tag                 = $this->tag;
        $this->attr['value'] = $value;
      }
      $control = $this->makeElement($tag, $this->attr, $this->content, $tag != 'input');
      $control = $this->formatAddOns($control);
      if ($this->label) {
        $control = "<label><span>" . $this->label . "</span>$control</label>";
      }
      return $control;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php

     *
*@param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
*@return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
      return array_key_exists($offset, $this->attr);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php

     *
*@param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>

     *
*@return mixed Can return all value types.
     */
    public function offsetGet($offset) {
      if (isset($this->attr[$offset])) {
        return $this->attr[$offset];
      } elseif (isset($this->extra[$offset])) {
        return $this->extra[$offset];
      }
      return null;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php


*
*@param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>


*
*@return void
     */
    public function offsetSet($offset, $value) {
      if ($offset == 'value') {
        $this->value($value);
        return;
      }
      $this->attr[$offset] = $value;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php

     *
*@param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>

     *
*@return void
     */
    public function offsetUnset($offset) {
      unset($this->attr[$offset]);
    }
  }
