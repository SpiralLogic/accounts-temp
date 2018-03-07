<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /**
   * @method \ADV\Core\HTML button()
   * @method \ADV\Core\HTML _button()
   * @method \ADV\Core\HTML table()
   * @method static \ADV\Core\HTML tr()
   * @method \ADV\Core\HTML td()
   * @method \ADV\Core\HTML div()
   * @method \ADV\Core\HTML textarea()
   * @method \ADV\Core\HTML optgroup()
   * @method \ADV\Core\HTML form()
   * @method \ADV\Core\HTML _form()
   * @method \ADV\Core\HTML label()
   * @method \ADV\Core\HTML input()
   * @method static \ADV\Core\HTML _td()
   * @method \ADV\Core\HTML _div()
   * @method \ADV\Core\HTML script()
   * @method \ADV\Core\HTML span()
   * @method \ADV\Core\HTML _span()
   * @method \ADV\Core\HTML option()
   * @method \ADV\Core\HTML select()
   * @method \ADV\Core\HTML _select()
   * @property \ADV\Core\HTML tr
   * @property \ADV\Core\HTML td
   * @property \ADV\Core\HTML script
   * @property \ADV\Core\HTML table
   * @property \ADV\Core\HTML div
   * @property \ADV\Core\HTML form
   * @property \ADV\Core\HTML option
   * @property \ADV\Core\HTML select
   */
  class HTML {
    use Traits\HTML;

    /** @var HTML **/
    protected static $_instance = null;
    /** @var bool **/
    public $content;
    /**
     * @param $func
     * @param $args
     *
     * @return null
     */
    public function __call($func, $args) {
      if (count($args) == 0) {
        $this->content .= '</' . ltrim($func, '_') . '>';
      } else {
        $this->_Builder($func, $args);
      }
      return $this;
    }
    /**
     * @param $func
     *
     * @return null
     */
    public function __get($func) {
      $this->__call($func, []);
      return $this;
    }
    /**
     * @static
     *
     * @param       $func
     * @param array $args
     *
     * @return null
     */
    public static function __callStatic($func, $args = []) {
      if (static::$_instance === null) {
        static::$_instance = new static;
      }
      static::$_instance->__call($func, $args);
      return static::$_instance;
    }
    /**
     * @param $attr
     *
     * @return string
     */
    public static function attr($attr) {
      return static::expandAttributes($attr);
    }
    /**
     * @param        $func
     * @param        $args
     *
     * @internal param array $attr
     * @internal param string $content
     */
    protected function _Builder($func, $args) {
      $attr = [];
      $open = (is_bool(end($args))) ? array_pop($args) : true;
      foreach ($args as $key => $val) {
        if ($key == 0 && is_string($val)) {
          $attr['id'] = $val;
        } elseif (!isset($attr['content']) && is_string($val)) {
          $content = $val;
        } elseif (is_array($val)) {
          $attr = array_merge($attr, $val);
        }
      }
      if (!isset($content) && isset($attr['content'])) {
        $content = $attr['content'];
        unset($attr['content']);
      }
      $this->content .= $this->makeElement($func, $attr, $content, !$open);
    }
    /**
     * @return HTML|string
     */
    public function __tostring() {
      $content       = $this->content;
      $this->content = '';
      return $content;
    }
  }
