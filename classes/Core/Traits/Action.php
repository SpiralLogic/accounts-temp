<?php

  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 22/10/12
   * Time: 5:24 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Traits;

  use ADV\Core\Input\Input;

  /**
   *
   */
  trait Action
  {
    protected $action = null;
    protected $actionID;
    /**
     * @param $prefix
     *
     * @return int|mixed
     */
    protected function getActionId($prefix) {
      $this->action = Input::_post(FORM_ACTION);
      $prefix       = (array) $prefix;
      foreach ($prefix as $action) {
        if (strpos($this->action, $action) === 0) {
          $result = str_replace($action, '', $this->action);
          if (strlen($result)) {
            $this->action   = $action;
            $this->actionID = $result;
            return $result;
          }
        }
      }
      return -1;
    }
  }
