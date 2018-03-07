<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      8/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Traits;

  /** **/
  trait HTML
  {
    /**
     * @param      $tag
     * @param      $attributes
     * @param null $content
     * @param bool $close
     *
     * @return string
     */
    protected function makeElement($tag, $attributes, $content = null, $close = false) {
      if ($content === true) {
        $close   = true;
        $content = '';
      } elseif ($content === null) {
        $content = '';
      }
      if ($close) {
        $content .= '</' . $tag . '>';
      }
      return '<' . $tag .  static::expandAttributes($attributes) . '>' . $content;
    }
    /**
     * @param $attributes
     *
     * @return string
     */
    protected static function expandAttributes($attributes) {
      $attrs = '';
      foreach ($attributes as $key => $value) {
        if ($value === true) {
          $attrs .= ' ' . $key;
          continue;
        }
        if (is_bool($value) || $value === null) {
          continue;
        }
        if ($value !== 0 && empty($value) && $key !== 'value') {
          continue;
        } elseif ($key === 'value') {
          $value = htmlentities($value, ENT_COMPAT, 'UTF-8', false);
        }
        $attrs .= ' ' . $key . '="' . $value . '"';
      }
      return $attrs;
    }
  }
