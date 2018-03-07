<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 8/07/12
   * Time: 4:50 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Application;

  /**

   */
  class Module
  {
    /**
     * @param      $name
     * @param null $icon
     */
    public function __construct($name, $icon = null) {
      $this->name              = $name;
      $this->icon              = $icon;
      $this->leftAppFunctions  = [];
      $this->rightAppFunctions = [];
    }
  }
