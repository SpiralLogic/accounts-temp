<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 15/05/12
   * Time: 8:35 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Module;

  /** **/
  abstract class Base implements Loadable
  {
    /** @var array * */
    protected $enabled;
    protected $config;
    /**
     * @param array $config
     */
    public function __construct($config = []) {
      $this->config  = $config;
      $this->enabled = $config['enabled'];
      if ($this->enabled) {
        $this->init();
      }
    }
  }
