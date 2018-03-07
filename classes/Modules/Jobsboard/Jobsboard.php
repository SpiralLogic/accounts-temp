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
  namespace Modules\Jobsboard;

  use ADV\Core\Module;
  use ADV\Core\DIC;
  use ADV\Core\Event;
  use ADV\App\User;
  use ADV\Core\DB\DB;

  /**
   * Jobsboard
   */
  class Jobsboard extends Module\Base
  {
    /** @var */
    protected $currentJob;
    /** @var */
    protected $lines;
    /** @var */
    public $order_no;
    /** @var DB */
    protected $jobsboardDB;
    /**
     *
     */
    public function __construct() {
      parent::__construct();
      if (!$this->jobsboardDB) {
        $this->jobsboardDB = DIC::get('DB', 'jobsboard');
      }
    }
    public function init() {
      $this->tasks();
    }
    /**
     * @param $trans_no
     *
     * @return mixed
     */
    public function removejob($trans_no) {
      $job = $this->get_job($trans_no);
      if ($trans_no && $this->jobExists($trans_no)) {
        $this->currentJob['Customer']             = $job['Customer'] . ' - CANCELLED';
        $this->currentJob['Updates']              = date('Y-m-d h:m:s', strtotime("now")) . ' ' . 'Job has BEEN CANCELLED from acounts by ' . User::_i()->name . ' ' . chr(
          13
        ) . chr(
          10
        ) . $job['Updates'];
        $this->currentJob['Next_Action_Required'] = '<div>Job has BEEN CANCELLED from accounts by ' . User::_i()->name . '</div>' . $job['Next_Action_Required'];
        $this->currentJob['order_ref']            = '';
        $this->currentJob['order_no']             = '';
        $this->currentJob['Priority_Level']       = 5;
        $this->jobsboardDB->update('Job_List')->values($this->currentJob)->where('Advanced_Job_No=', $this->currentJob['Advanced_Job_No'])->exec();
        Event::success('Order ' . $trans_no . ' has been removed from the Jobs Board!');
      } else {
        Event::error('There is no current Order to remove from jobsboard');
      }
      return false;
    }

    /**
     * @param $job_data
     */
    public function addjob($job_data) {
      $this->order_no = $order_no = $job_data->trans_no;
      $user_name      = User::_i()->name;
      $orderlines     = $this->getOrderLines();
      $update         = var_export($job_data, true);
      $job            = $this->get_job($order_no);
      $exists         = ($job['Advanced_Job_No'] > 0);
      $lines          = [];
      foreach ($orderlines as $line) {
        /***
         * @var \Sales_Line $line
         */
        $lines[$line['id']] = [
          'line_id'     => $line['id'],
          'stock_code'  => $line['stk_code'],
          'price'       => $line['unit_price'],
          'description' => $line['description'],
          'quantity'    => $line['quantity']
        ];
      }
      if ($exists) {
        $jobslines = $this->getLines();
        $deleted   = array_diff_key($jobslines, $lines);
        foreach ($deleted as $line) {
          if ($line['quantity'] == 0) {
            continue;
          }
          $lines[$line['line_id']]             = $line;
          $lines[$line['line_id']]['quantity'] = 0;
          $lines[$line['line_id']]['description'] .= " DELETED!";
        }
        $new = array_diff_key($lines, $jobslines);
        if (count($new)) {
          $data['Priority_Level'] = 1;
        }
        $update                       = date('Y-m-d h:m:s', strtotime("now")) . ' ' . 'Job Updated from acounts by ' . $user_name . ' ' . chr(13) . chr(10) . $job['Updates'];
        $data['Next_Action_Required'] = '<div>Job has been updated from accounts ' . $user_name . '</div>' . $job['Next_Action_Required'];
      } else {
        $data['Customer']                  = $job_data->customer_name;
        $data['Priority_Level']            = 3;
        $data['Date_Ordered']              = date('Y-m-d', strtotime("now"));
        $data['Promised_Due_Date']         = date('Y-m-d', strtotime("+1 week"));
        $data['Next_Action_Required']      = 'Job has been added from accounts';
        $data['Main_Employee_Responsible'] = $user_name;
        $data['salesman']                  = $user_name;
        $data['Can_work_be_done_today']    = '-1';
        $data['Goods_Ordered']             = 'No';
      }
      $data['order_no'] = $order_no;
      if (empty($job_data->phone)) {
        $branch          = new \Debtor_Branch($job_data->Branch);
        $job_data->phone = $branch->phone;
      }
      $data['Phone']                = $job_data->phone;
      $data['Email']                = $job_data->email;
      $data['order_ref']            = $job_data->reference;
      $data['Client_PO']            = $job_data->cust_ref;
      $data['debtor_id']            = $job_data->debtor_id;
      $data['Site_Ship_to_Address'] = $job_data->deliver_to . chr(13) . chr(10) . str_replace('\n', chr(13) . chr(10), $job_data->delivery_address);
      $data['Deliver_to_Company']   = $job_data->deliver_to;
      $data['Attention']            = $job_data->name;
      $data['Detail']               = str_replace('\n', chr(13) . chr(10), $job_data->Comments);
      $data['Updates']              = $update;
      $this->lines                  = $lines;
      ($exists) ? $this->updateJob($data) : $this->insertJob($data);
      return;
    }
    /***
     * @param $trans_no
     *
     * @return array
     */
    protected function get_job($trans_no) {
      $this->currentJob = $this->jobsboardDB->select()->from('Job_List')->where('order_no=', $trans_no)->fetch()->one();
      if ($this->currentJob) {
        $this->getLines();
      }
      return $this->currentJob;
    }
    /***
     * @return bool
     * Returns if there is currently a job that exists stored in currentJob
     */
    protected function jobExists() {
      if (empty($this->currentJob)) {
        return false;
      }
      return (isset($this->currentJob['Advanced_Job_No']));
    }
    /**
     * @param array $data  Data to insert as job
     *                     Will insert lines
     */
    protected function insertJob($data) {
      $result = $this->jobsboardDB->insert('Job_List')->values($data)->exec();
      if ($result) {
        $data['Advanced_Job_No'] = $result;
        $this->currentJob        = $data;
        $this->insertLines();
      }
    }
    /**
     * @param array $data Data to update Jobsboard job
     */
    protected function updateJob($data) {
      $result = $this->jobsboardDB->update('Job_List')->values($data)->where('Advanced_Job_No=', $this->currentJob['Advanced_Job_No'])->exec();
      if ($result) {
        $this->insertLines();
      }
    }
    protected function insertLines() {
      $lines        = $this->lines;
      $this->lines  = [];
      $currentLines = $this->getLines();
      foreach ($lines as $line) {
        if (isset($currentLines[$line['line_id']])) {
          $this->updateline($line);
        } else {
          $this->insertLine($line);
        }
      }
    }
    /**
     * @param array $line Insert line into Jobsboard
     */
    protected function insertLine($line) {
      $line['job_id']        = $this->currentJob['Advanced_Job_No'];
      $line_id               = $this->jobsboardDB->insert('JobListItems')->values($line)->exec();
      $this->lines[$line_id] = $line;
    }
    /**
     * @param array $line Updateline into jobsboard
     */
    protected function updateLine($line) {
      $line['job_id'] = $this->currentJob['Advanced_Job_No'];
      $this->jobsboardDB->update('JobListItems')->values($line)->where('line_id=', $line['line_id'])->andWhere('job_id=', $this->currentJob['Advanced_Job_No'])->exec();
    }
    /**
     * @return array Get lines from jobsboard for current order
     */
    protected function getLines() {
      $lines  = $this->jobsboardDB->select()->from('JobListItems')->where('job_id=', $this->currentJob['Advanced_Job_No'])->fetch()->all();
      $result = [];
      foreach ($lines as $line) {
        $result[$line['line_id']] = $line;
      }
      return $result;
    }
    /***
     * Get line from order
     *
     * @return array Lines from accounting order
     */
    protected function getOrderLines() {
      $lines = DB::_select()->from('sales_order_details')->where('order_no=', $this->order_no)->fetch()->all();
      return $lines;
    }
    /**
     * @static
     */
    public function tasks() {
      $result = false;
      try {
        $this->jobsboardDB->query(
          'UPDATE Job_List SET priority_changed = NOW() , Main_Employee_Responsible = previous_user WHERE
        Priority_Level<5 AND priority_changed < (NOW() - INTERVAL 3 DAY) AND Main_Employee_Responsible<>previous_user AND priority_changed>0'
        );
        $result = $this->jobsboardDB->numRows();
      } catch (\Exception $e) {
      }
      if ($result) {
        Event::notice($result . ' Jobs were returned to their previous responslble person.');
      }
      $result = false;
      try {
        $this->jobsboardDB->query(
          'UPDATE Job_List SET has_worked_change = NOW() , Can_work_be_done_today = -1 WHERE
        Priority_Level<5 AND has_worked_change < (NOW() - INTERVAL 3 DAY) AND Can_work_be_done_today=0 AND has_worked_change>0'
        );
        $result = $this->jobsboardDB->numRows();
      } catch (\Exception $e) {
      }
      if ($result) {
        Event::notice($result . ' Jobs were changed back to having "work can be done" due to inactivity.');
      }
    }
  }

