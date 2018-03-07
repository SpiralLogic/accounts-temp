<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core {

    /**
     */
    class Arr {
      /**
       * @static
       *
       * @param $array
       * @param $index
       * @param $elements
       * Inserts $elements into $array at position $index.
       * $elements is list of any objects
       *
       * @return bool
       */
      public static function insert(&$array, $index, $elements) {
        if (is_null($index)) {
          return $array = $elements;
        }
        if (is_numeric($index)) {
          $head = array_splice($array, 0, $index);
          return $array = array_merge($head, (array) $elements, $array);
        }
        $keys = explode('.', $index);

        // This loop allows us to dig down into the array to a dynamic depth by
        // setting the array value for each level that we dig into. Once there
        // is one key left, we can fall out of the loop and set the value as
        // we should be at the proper depth.
        while (count($keys) > 1) {
          $key = array_shift($keys);

          // If the key doesn't exist at this depth, we will just create an
          // empty array to hold the next value, allowing us to create the
          // arrays to hold the final value.
          if (!isset($array[$key]) or !is_array($array[$key])) {
            $array[$key] = [];
          }

          $array =& $array[$key];
        }

        return $array[array_shift($keys)] = $elements;
      }
      /**
       * @static
       *
       * @param     $array
       * @param     $index
       * @param int $len
       *
       * @return bool
       */
      public static function remove(&$array, $index, $len = 1) {
        array_splice($array, $index, $len);
        return true;
      }
      /**
       * @static
       *
       * @param array      $array
       * @param int|string $key
       * @param mixed      $default
       *
       * @return mixed null
       */
      public static function get(array $array, $key, $default = null) {
        if (is_null($key)) {
          return $array;
        }
        foreach (explode('.', $key) as $segment) {
          if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
          }
          $array = $array[$segment];
        }
        return $array;
      }
      /**
       * @static
       *
       * @param $array
       * @param $index
       * @param $len
       * @param $elements
       *
       * @return bool
       */
      public static function substitute(&$array, $index, $len, $elements) {
        array_splice($array, $index, $len);
        $elements = (array) $elements;
        while (count($elements) < $len) {
          Arr::append($elements, $elements);
        }
        Arr::insert($array, $index, array_splice($elements, 0, $len));
        return true;
      }
      /**
       * @static
       *
       * @param             &$array
       * @param array|mixed $elements elements to append,
       */
      public static function append(&$array, $elements = []) {
        $elements = (array) $elements;
        foreach ($elements as $key => $el) {
          if (is_int($key)) {
            $array[] = $el;
          } else {
            if (isset($array[$key]) && is_array($array[$key]) && is_array($el)) {
              static::append($array[$key], $el);
              continue;
            }
            $array[$key] = $el;
          }
        }
      }
      /**
       * @static
       *
       * @param       $needle
       * @param array $haystack
       * @param null  $valuekey
       *
       * @return int|null
       */
      public static function searchValue($needle, $haystack, $valuekey = null) {
        foreach ($haystack as $value) {
          if ($valuekey === null) {
            $val = $value;
          } elseif (is_array($value)) {
            $val = $value[$valuekey];
          } else {
            continue;
          }
          if ($needle == $val) {
            return $value;
          }
        }
        return null;
      }
      /**
       * @static
       *
       * @param      $needle
       * @param      $haystack
       * @param null $valuekey
       *
       * @return int|null|string
       */
      public static function searchKey($needle, $haystack, $valuekey = null) {
        foreach ($haystack as $key => $value) {
          $val = isset($valuekey) ? $value[$valuekey] : $value;
          if ($needle == $val) {
            return $key;
          }
        }
        return null;
      }
    }
  }
  namespace {
    /**
     * @static
     *
     * @param             &$array
     * @param array|mixed $elements elements to append,
     */
    function array_append(&$array, $elements = []) {
      $elements = (array) $elements;
      foreach ($elements as $key => $el) {
        if (is_int($key)) {
          $array[] = $el;
        } else {
          if (isset($array[$key]) && is_array($array[$key]) && is_array($el)) {
            array_append($array[$key], $el);
            continue;
          }
          $array[$key] = $el;
        }
      }
    }
  }
