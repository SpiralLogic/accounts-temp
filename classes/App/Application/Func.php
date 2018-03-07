<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 8/07/12
   * Time: 4:48 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Application;

  /** **/
  class Func
  {
    /** @var */
    public $label;
    /** @var */
    public $link;
    /** @var string * */
    public $access;
    /**
     * @param        $label
     * @param        $link
     * @param string $access
     */
    public function __construct($label, $link, $access = SA_OPEN) {
      $this->label  = $label;
      $this->link   = e($link);
      $this->access = $access;
    }
  }

