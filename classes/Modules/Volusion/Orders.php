<?php

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace Modules\Volusion;

  use ADV\Core\XMLParser;
  use ADV\Core\DB\DBUpdateException;
  use ADV\Core\DB\DBInsertException;
  use ADV\Core\Event;
  use ADV\Core\DB\DB;

  /** **/
  use ADV\Core\DB\DBDuplicateException;

  /** **/
  class Orders implements \Iterator, \Countable
  {
    protected $config;
    /** @var */
    protected $data = array();
    /** @var int * */
    protected $current = -1;
    /** @var string * */
    protected $table = 'WebOrders';
    /** @var string * */
    protected $idcolumn = 'OrderID';
    /** @var mixed * */
    protected $_classname;
    /** @var array * */
    public static $shipping_types
      = array(
        502  => "Pickup", //
        28   => "Declined!", //
        902  => "To be calculated", //
        903  => "Use own courier", //
        998  => "Installation", //
        1006 => "Medium Courier (VIC Metro)", //
        1009 => "Small Parcel (0.5m,5kg)", //
        1010 => "Medium Courier (1m,25kg)", //
        1011 => "Medium Courier Rural (1.8m,25kg)"
      );
    /** @var array * */
    public static $payment_types
      = array(
        1  => "Account", //
        2  => "Cheque/Money Order", //
        5  => "Visa/Mastercard", //
        7  => "American Express", //
        18 => "PayPal", //
        23 => "Direct Deposit", //
        24 => "Wait for Freight Quotation", //
        26 => "Credit Card", //
        28 => "Visa", //
        31 => "Mastercard" //
      );
    /** @var OrderDetails * */
    public $details;
    /** @var */
    public $status;
    /** @var */
    public $XML;
    /**

     */
    public function __construct($config = []) {
      $this->config     = $config;
      $this->_classname = str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
      //echo 'Getting from Volusion<br>';
      $this->get();
      $this->next();
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
      $url .= '&EDI_Name=Generic\Orders';
      $url .= '&SELECT_Columns=*';
      if (!$result = file_get_contents($url)) {
        Event::warning('Could not retrieve web orders');
      }
      $this->XML = $result;
      return $result;
    }
    /**
     * @return bool
     */
    public function get() {
      $XML = $this->getXML();
      if (!$XML) {
        return false;
      }
      $this->data = XMLParser::XMLtoArray($XML);
      if (isset($this->data[$this->idcolumn])) {
        $this->data = array($this->data);
      }
      return true;
    }
    /**
     * @return bool
     */
    public function process() {
      if (!$this->data) {
        $this->status = "No new web orders";
        return false;
      }
      $this->save();
      /** @var OrderDetails $detail */
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach ($this->details as $detail) {
        $this->details->save();
        /** @var \Modules\Volusion\OrderOptions $option */
        if ($this->details->options) {
          /** @noinspection PhpUnusedLocalVariableInspection */
          foreach ($this->details->options as $option) {
            $this->details->options->save();
          }
        }
      }
      return true;
    }
    /**
     * @return bool|string
     */
    public function save() {
      $current = $this->current();
      $exists  = $this->exists();
      if ($exists) {
        //			echo $this->_classname . ' already exists, updating changes<br>';
        try {
          $current['ison_jobsboard'] = null;
          DB::_update($this->table)->values($current)->where($this->idcolumn . '=', $current[$this->idcolumn])->exec();
          return 'Updated ' . $this->_classname . ' ' . $current[$this->idcolumn];
        } catch (DBUpdateException $e) {
          return 'Could not update ' . $this->_classname . ' ' . $current[$this->idcolumn];
        } catch (DBDuplicateException $e) {
          $this->status = 'Could not insert ' . $this->_classname . ' ' . $current[$this->idcolumn] . ' it already exists!';
        }
      } else {
        echo $this->_classname . ' doesn\'t exist, adding<br>';
        try {
          DB::_insert($this->table)->values($current)->exec();
          return 'Inserted ' . $this->_classname . ' ' . $current[$this->idcolumn];
        } catch (DBInsertException $e) {
          return 'Could not insert ' . $this->_classname;
        } catch (DBDuplicateException $e) {
          return 'Could not insert ' . $this->_classname . ' ' . $current[$this->idcolumn] . ' it already exists!';
        }
      }
      return false;
    }
    /**
     * @return bool
     */
    public function exists() {
      $current = $this->current();
      $results = DB::_select($this->idcolumn)->from($this->table)->where($this->idcolumn . '=', $current[$this->idcolumn])->fetch()->one();
      return (count($results) > 0) ? $results[$this->idcolumn] : false;
    }
    public function next() {
      $this->current++;
      if (isset($this->data[$this->current]['OrderDetails'])) {
        $this->details = new OrderDetails($this->data[$this->current]['OrderDetails']);
        unset($this->data[$this->current]['OrderDetails']);
      }
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current() {
      if (!$this->valid()) {
        return false;
      }
      return $this->data[$this->current];
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, integer
     */
    public function key() {
      return $this->data[$this->current]['OrderID'];
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid() {
      return isset($this->data[$this->current]) && $this->data[$this->current] && $this->current < count($this->data);
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind() {
      $this->current = -1;
      $this->next();
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     *       The return value is cast to an integer.
     */
    public function count() {
      $this->data = $this->data ? : array();
      return count($this->data);
    }
  }

  /** **/
  class OrderDetails extends Orders implements \Iterator, \Countable
  {
    /** @var OrderOptions * */
    protected $table = 'WebOrderDetails';
    /** @var string * */
    protected $idcolumn = 'OrderDetailID';
    /** @var OrderOptions * */
    public $options;
    /**
     * @param $data
     */
    public function __construct($data) {
      $this->_classname = str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
      if (is_array($data)) {
        $this->data = (!is_array(reset($data))) ? array($data) : $data;
      }
      $this->next();
    }
    /**
     * @return bool|void
     */
    public function next() {
      $this->current++;
      if (!$this->valid()) {
        return;
      }
      if (isset($this->data[$this->current]['Options']) && isset($this->data[$this->current]['OrderDetails_Options'])) {
        $options       = $this->data[$this->current]['OrderDetails_Options'];
        $this->options = new OrderOptions($options);
        unset($this->data[$this->current]['OrderDetails_Options']);
      } else {
        $this->options = null;
      }
    }
    /**
     * @return mixed
     */
    public function current() {
      if (!$this->valid()) {
        return false;
      }
      return $this->data[$this->current];
    }
    /**
     * @return mixed
     */
    public function key() {
      return $this->data[$this->current]['OrderDetailID'];
    }
  }

  /** **/
  class OrderOptions extends OrderDetails implements \Iterator, \Countable
  {
    /** @var string * */
    protected $table = 'WebOrderDetails_Options';
    /** @var string * */
    protected $idcolumn = 'OptionID';
    /**
     * @param $data
     */
    public function __construct($data) {
      $this->_classname = str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
      if (is_array($data)) {
        $this->data = (!is_array(reset($data))) ? array($data) : $data;
      }
      $this->next();
    }
    /**
     * @return bool
     */
    public function exists() {
      $current = $this->current();
      $results = DB::_select()->from($this->table)->where($this->idcolumn . '=', $current[$this->idcolumn])->andWhere('OrderDetailID=', $current['OrderDetailID'])->fetch()->all();
      return (count($results) > 0);
    }
    /**
     * @return bool|void
     */
    public function next() {
      $this->current++;
    }
    /**
     * @return mixed
     */
    public function current() {
      return $this->data[$this->current];
    }
    /**
     * @return mixed
     */
    public function key() {
      return $this->data[$this->current]['OrderDetailID'];
    }
  }
