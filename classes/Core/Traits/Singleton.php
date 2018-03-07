<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 19/04/12
   * Time: 11:56 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Traits;

  /**
   */
  trait Singleton {
    /** @var null * */
    protected static $i = null;
    /**
     * @param object $class
     *
     * @return mixed|$this
     */
    public static function i($class = null) {
      /** @var \ADV\Core\DIC $dic */
      $dic = \ADV\Core\DIC::i();
      if (!$dic instanceof \ADV\Core\DIC) {
        if (static::$i === null) {
          static::$i = new static;
        }
        return static::$i;
      }
      $namespaced_class = $class_name = get_class() == get_class($class) ? get_class($class) : get_called_class();
      $lastNsPos        = strripos($namespaced_class, '\\');
      if ($lastNsPos) {
        $class_name = substr($namespaced_class, $lastNsPos + 1);
      }
      try {
        return $dic[$class_name];
      } catch (\InvalidArgumentException $e) {
      }
      if (is_a($class, $namespaced_class)) {
        $dic[$class_name] = function () use ($class) {
          return $class;
        };
      } else {
        $args             = (get_class() == get_class($class)) ? array_slice(func_get_args(), 1) : [$class];
        $dic[$class_name] = function () use ($namespaced_class, $args) {
          if (!$args) {
            return new $namespaced_class;
          }
          $ref = new \ReflectionClass($namespaced_class);
          return $ref->newInstanceArgs($args);
        };
      }
      return $dic[$class_name];
    }
  }
