<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      1/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /** **/
  class Security
  {
    /**
     * @static
     *
     * @param $value
     *
     * @throws \RuntimeException
     * @return array|string
     */
    public static function htmlentities($value) {
      static $already_cleaned = [];
      // Nothing to escape for non-string scalars, or for already processed values
      if (is_bool($value) or is_int($value) or is_float($value) or in_array($value, $already_cleaned, true)) {
        return $value;
      }
      if (is_string($value)) {
        $value = htmlentities($value, ENT_COMPAT, 'UTF-8', false);
      } elseif (is_array($value) or ($value instanceof \Iterator and $value instanceof \ArrayAccess)) {
        // Add to $already_cleaned variable when object
        is_object($value) and $already_cleaned[] = $value;
        foreach ($value as $k => $v) {
          $value[$k] = static::htmlentities($v);
        }
      } elseif ($value instanceof \Iterator or get_class($value) == 'stdClass') {
        // Add to $already_cleaned variable
        $already_cleaned[] = $value;
        foreach ($value as $k => $v) {
          $value->{$k} = static::htmlentities($v);
        }
      } elseif (is_object($value)) {
        // Throw exception when it wasn't whitelisted and can't be converted to String
        if (!method_exists($value, '__toString')) {
          throw new \RuntimeException('Object class "' . get_class(
            $value
          ) . '" could not be converted to string or ' . 'sanitized as ArrayAcces. Whitelist it in security.whitelisted_classes in app/config/config.php ' . 'to allow it to be passed unchecked.');
        }
        $value = static::htmlentities((string)$value);
      }
      return $value;
    }
  }
