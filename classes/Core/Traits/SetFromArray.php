<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 15/04/12
   * Time: 12:31 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Traits;

  /** **/
  trait SetFromArray
  {
    /**
     * @param null $changes
     *
     * @return array|null
     */
    protected function setFromArray($changes = null) {
      if (!$changes || !is_array($changes)) {
        return;
      }
      foreach ($changes as $key => $value) {
        if (!is_array($value) && !is_object($value)) {
          $value = (trim($value) == null) ? '' : trim($value);
        }
        if (property_exists($this, $key)) {
          if ($this->$key === null && ($value === '' || $value === 'null')) {
            $value = null;
          }
          $this->$key = $value;
        }
      }
    }
  }
