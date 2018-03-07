<?php
  namespace Modules\Volusion;

  use ADV\Core\XMLParser;
  use ADV\Core\Event;

  /** **/
  class Products
  {
    protected $config;
    /** @var array * */
    public $products = array();
    /**

     */
    public function __construct($config = []) {
      $this->config = $config;
    }
    /**
     * @return bool
     */
    public function get() {
      $productsXML = $this->getXML();
      if (!$productsXML) {
        return false;
      }
      $this->products = XMLParser::XMLtoArray($productsXML);
      return true;
    }
    /**
     * @return string
     */
    public function getXML() {
      $apiuser = $this->config['apiuser'];
      $apikey  = $this->config['apikey'];
      $url     = $this->config['apiurl'];
      $url .= "Login=" . $apiuser;
      $url .= '&EncryptedPassword=' . $apikey;
      $url .= '&EDI_Name=Generic\Products';
      $url .= '&SELECT_Columns=*&LIMIT=1';
      if (!$result = file_get_contents($url)) {
        Event::warning('Could not retrieve web products');
      }
      ;
      return $result;
    }
  }
