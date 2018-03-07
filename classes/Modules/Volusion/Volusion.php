<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace Modules\Volusion;

  use \ADV\Core\Module;
  use ADV\Core\DIC;
  use \Modules\Volusion\Orders as Orders;
  use \ADV\Core\DB\DB;
  use \ADV\Core\DB\DBDuplicateException;
  use \ADV\Core\Event;

  /** **/
  class Volusion extends Module\Base
  {
    /** @var DB */
    protected $jobsboardDB;
    public static $DB;
    public function init() {
      if (isset($_POST['user_name'])) {
      $this->run();
      }
    }
    public function run() {
      \ADV\Core\Event::registerShutdown([$this, 'doWebsales']);
    }
    public function doWebsales() {
      if (!$this->jobsboardDB) {
        $this->jobsboardDB = DIC::get('DB', 'jobsboard');
      }
      if (!static::$DB) {
        static::$DB = \ADV\Core\DIC::get('DB');
      }
      $orders = $this->getNewWebsales();
      if (!$orders) {
        Event::notice("No new websales from website");
      } else {
        $success = 0;
        foreach ($orders as $order) {
          $this->insertJob($order['OrderID']);
          $success++;
        }
        if ($success) {
          Event::success('Succesfully added ' . $success . ' jobs to database');
        }
      }
      $this->notOnJobsboard();
      if (!headers_sent()) {
        header('Location: /');
      }
    }
    /**
     * @return bool|Orders
     */
    function getNewWebsales() {
      $orders = new Orders($this->config);
      if (!count($orders)) {
        return false;
      }
      $success = 0;
      foreach ($orders as $order) {
        if ($orders->process()) {
          $success++;
        }
      }
      if ($success) {
        Event::success('Added/Update ' . $success . ' websales');
      }
      return $orders;
    }
    /**
     * @return array
     */
    function getNotOnJobsboard() {

      $results = static::$DB->select('OrderID,ison_jobsboard')->from('WebOrders')->where('ison_jobsboard IS null')->fetch()->all();

      return $results;
    }
    /**
     * @return bool
     */
    function notOnJobsboard() {
      $neworders = $this->getNotOnJobsboard();
      if (!$neworders) {
        //		Event::notice('No jobs in database from website but not on jobsboard');
        return false;
      }
      $success = 0;
      foreach ($neworders as $neworder) {
        $job = $this->insertJob($neworder['OrderID']);
        if ($job) {
          Event::success("Websale " . $neworder['OrderID'] . " successfully added to Jobs Board with Job Number $job!");
        }
        $success++;
      }
      return $neworders;
    }
    /**
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|bool|int|mixed
     */
    protected function insertJob($id) {
      $order = static::$DB->select()->from('WebOrders')->where('OrderID=', $id)->fetch()->one();
      if (!$order) {
        Event::error('Could not find job ' . $id . ' in database');
        return false;
      }
      $orderdetails = static::$DB->select()->from('WebOrderDetails')->where('OrderID=', $id)->fetch()->all();
      $jobsboard_no = $this->jobsboardDB->select('Advanced_Job_No')->from('Job_List')->where('websaleid=', $id)->fetch()->one();
      $jobsboard_no = $jobsboard_no['Advanced_Job_No'];
      $lineitems    = $lines = array();
      foreach ($orderdetails as $detail) {
        $lines[]     = array(
          'item_code'   => '[' . $detail['ProductCode'] . ']',
          'ProductName' => $detail['ProductName'],
          'quantity'    => 'x' . $detail['Quantity'],
          'options'     => '</div><div>' . $detail['Options'],
        );
        $lineitems[] = array(
          'stock_code'  => $detail['ProductCode'],
          'quantity'    => $detail['Quantity'],
          'description' => $detail['ProductName'] . $detail['Options'],
          'line_id'     => $detail['OrderDetailID'],
        );
      }
      if ($jobsboard_no > 0) {
        $freight_method = Orders::$shipping_types[$order['ShippingMethodID']];
        $payment_method = Orders::$payment_types[$order['PaymentMethodID']];
        $comments       = (strlen($order['Order_Comments']) > 0) ? $order['Order_Comments'] . "\r\n" : '';
        $detail         = $comments . "Payment Method: " . $payment_method . "\r\nShipping Method: " . $freight_method . "\r\nFreight Paid: " . $order['TotalShippingCost'];
        $newJob         = array(
          'Advanced_Job_No' => $jobsboard_no,
          'websaleid'       => $id,
          'Detail'          => $detail,
        );
        $this->jobsboardDB->update('Job_List')->values($newJob)->where('Advanced_Job_No=', $jobsboard_no)->exec();
        static::$DB->update('WebOrders')->value('ison_jobsboard', $jobsboard_no)->where('OrderID=', $id)->exec();
        $this->insertJobsboardlines($lineitems, $jobsboard_no);
        return $jobsboard_no;
      }
      $newJob = array(
        'websaleid'             => $id,
        'Customer'              => "Websale: $id " . $order['BillingCompanyName'],
        'Date_Ordered'          => date('Y-m-d', strtotime("now")),
        'Promised_Due_Date'     => date('Y-m-d', strtotime("+1 week")),
        'Brief_Job_Description' => var_export($lines, true)
      );
      if ($order['PaymentDeclined'] == "Y") {
        $newJob['Priority_Level']       = 3;
        $newJob['Next_Action_Required'] = '<div><br/></div><div><font face="Tekton Pro Cond" size=3 color="red"><strong>PAYMENT WAS DECLINED FOR THIS ORDER</strong></font></div><div>Job has been added automatically from websales</div>';
      } else {
        $newJob['Priority_Level']       = 0;
        $newJob['Next_Action_Required'] = '<div><br/></div><div><font face="Tekton Pro Cond" size=3 color="red"><strong>' . $order['OrderStatus'] . '</strong></font></div><div>Job has been added automatically from websales</div>';
      }
      $newJob['Main_Employee_Responsible'] = 'Automatic Websale';
      $newJob['Can_work_be_done_today']    = -1;
      $newJob['Phone']                     = $order['BillingPhoneNumber'];
      $newJob['Deliver_to_Company']        = $order['ShipCompanyName'];
      $newJob['Client_PO']                 = $order['PONum'];
      $shipping_address                    = $order['ShipAddress1'] . "\r\n";
      if (!empty($order['ShipAddress2'])) {
        $shipping_address .= $order['ShipAddress2'] . "\r\n";
      }
      $shipping_address .= $order['ShipCity'] . " " . $order['ShipState'] . " " . $order['ShipPostalCode'] . "\r\n" . $order['ShipCountry'];
      $newJob['Site_Ship_to_Address'] = $shipping_address;
      $newJob['Attention']            = $order['ShipFirstName'] . ' ' . $order['ShipLastName'];
      $newJob['Goods_Ordered']        = 'No';
      $freight_method                 = Orders::$shipping_types[$order['ShippingMethodID']];
      $payment_method                 = Orders::$payment_types[$order['PaymentMethodID']];
      $comments                       = (strlen($order['Order_Comments']) > 0) ? $order['Order_Comments'] . "\r\n" : '';
      $newJob['Detail']               = $comments . "Payment Method: " . $payment_method . "\r\nShipping Method: " . $freight_method . "\r\nFreight Paid: " . $order['TotalShippingCost'];
      $updates                        = "Initial Automated Insert Details: \r\n";
      foreach ($order as $key => $value) {
        if (!empty($value)) {
          $updates .= "[$key]: $value\r\n";
        }
      }
      foreach ($orderdetails as $key => $detail) {
        $updates .= "----------------------\r\nOrder Line $key:\r\n----------------------\r\n";
        foreach ($detail as $key => $value) {
          if (!empty($value)) {
            $updates .= "[$key]: $value\r\n";
          }
        }
      }
      $newJob['Updates'] = $updates;
      $jobsboard_no      = $this->jobsboardDB->insert('Job_List')->values($newJob)->exec();
      $this->insertJobsboardlines($lineitems, $jobsboard_no);
      static::$DB->update('WebOrders')->value('ison_jobsboard', $jobsboard_no)->where('OrderID=', $order['OrderID'])->exec();
      $result = $jobsboard_no;
      return $result;
    }
    /**
     * @param $lines
     * @param $jobid
     */
    protected function insertJobsboardlines($lines, $jobid) {
      $existing_lines = $this->getJobsboardLines($jobid);
      $deleted        = array_diff_key($lines, $existing_lines);
      foreach ($deleted as $line) {
        $line['quantity'] = 0;
        $line['description'] .= " DELETED!";
        $this->jobsboardDB->update('JobListItems')->values($line)->where('line_id=', $line['line_id'])->andWhere('job_id=', $jobid)->exec();
      }
      foreach ($lines as $line) {
        $line['job_id'] = $jobid;
        try {
          $line['line_id'] = $this->jobsboardDB->insert('JobListItems')->values($line)->exec();
        } catch (DBDuplicateException $e) {
          $this->jobsboardDB->update('JobListItems')->values($line)->where('line_id=', $line['line_id'])->andWhere('job_id=', $jobid)->exec();
        }
      }
    }
    /**
     * @param $jobid
     *
     * @return array
     */
    protected function getJobsboardLines($jobid) {
      $result = $this->jobsboardDB->select()->from('JobListItems')->where('job_id=', $jobid)->fetch()->all();
      return $result;
    }
  }
