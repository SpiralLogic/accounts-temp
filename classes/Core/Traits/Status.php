<?php

  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 19/04/12
   * Time: 12:08 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Traits;

  /** **/
  trait Status
  {
    /** @var \ADV\Core\Status */
    protected $status = null;
    /**
     * @param bool $string return status as string if true and as array if false
     *
     * @return string|\ADV\Core\Status|array
     */
    public function getStatus($string = false) {
      if ($this->status === null) {
        $this->status = new \ADV\Core\Status();
      }
      return $this->status;
    }
    /**
     * @param \Adv\Core\Status $status
     */
    public function setStatus(\Adv\Core\Status $status = null) {
        $this->status = $status;
        $this->getStatus();
    }
    /***
     * @param null   $status
     * @param string $message
     * @param null   $var
     *
     * @return Status|bool
     */
    protected function status($status = null, $message = '', $var = null) {
      if (!$this->status) {
        $this->status = new \ADV\Core\Status();
      }
      return $this->status->set($status, $message, $var);
    }
  }
