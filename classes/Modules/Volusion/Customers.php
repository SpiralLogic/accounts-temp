<?php
  namespace Modules\Volusion;

  use \ADV\Core\DB\DBDuplicateException, \ADV\Core\DB\DB, \ADV\Core\XMLParser;
  use ADV\Core\Event;
  use ADV\App\Debtor\Debtor;

  /**
   * Class for getting Customers from Volusion and putting them in to the intermediate database.
   */
  class Customers
  {
    protected $config;
    /** @var \ADV\Core\Status * */
    public $status;
    /** @var array * */
      public $customers = [];
      /**

     */
    public function __construct($config = []) {
      $this->config = $config;
      echo __NAMESPACE__;
      $this->status = new \ADV\Core\Status();
    }
    public function process() {
      $this->get();
      $this->insertCustomersToDB();
      $this->createCustomer();
    }
    /**
     * Gets XML from website containing customer information and stores in in $this->customers
     *
     * @return bool returns false if nothing was retrieved or true otherwise.
     */
    public function get() {
      $customersXML = $this->getXML();
      if (!$customersXML) {
        return $this->status->set(false, 'getXML', "Nothing retrieved from website");
      }
      $this->customers = XMLParser::XMLtoArray($customersXML);
      if (!$this->customers) {
        return $this->status->set(false, 'XMLtoArray', "No new custoemrs!");
      }
      return $this->status->set(true, 'get', "Customers retrieved");
    }
    /**
     * @internal param $customers
     * @return array
     */
    public function insertCustomersToDB() {
      $customers = $this->customers;
      if (!$customers) {
        return $this->status->set(false, 'insertToDB', 'No Customers to add.');
      }
      foreach ($customers as $customer) {
        $this->insertCustomerToDB($customer);
      }
      return $this->status->set(true, 'addedToDB', "Finished adding Customers to DB!");
    }
    /**
     * @param $customer
     */
    public function insertCustomerToDB($customer) {
      if (!empty($customer['CompanyName'])) {
        $name = $customer['CompanyName'];
      } elseif (!empty($customer['FirstName']) || !empty($customer['LastName'])) {
        $name = ucwords($customer['FirstName'] . ' ' . $customer['LastName']);
      } else {
        $name = $customer['EmailAddress'];
      }
      try {
        DB::_insert('WebCustomers')->values($customer)->exec();
        $this->status->set(true, 'insert', "Added Customer $name to website customer database! {$customer['CustomerID']} ");
      } catch (DBDuplicateException $e) {
        DB::_update('WebCustomers')->values($customer)->where('CustomerID=', $customer['CustomerID'])->exec();
        $this->status->set(false, 'insert', "Updated Customer $name ! {$customer['CustomerID']}");
      }
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
      $url .= '&EDI_Name=Generic\Customers';
      $url .= '&SELECT_Columns=*';
      if (!$result = file_get_contents($url)) {
        Event::warning('Could not retrieve web customers');
      }
      return $result;
    }
    /**
     * @return array
     */
    protected function createCustomer() {
      $result = DB::_select()->from('WebCustomers')->where('extid=', 0)->fetch()->assoc()->all();
      if (!$result) {
        return $this->status->set(false, 'insert', "No new customers in database");
      }
      $added = $updated = 0;
      foreach ($result as $row) {
        if (!empty($row['CompanyName'])) {
          $name = $row['CompanyName'];
        } elseif (!empty($row['FirstName']) || !empty($row['LastName'])) {
          $name = $row['CompanyName'] = ucwords($row['FirstName'] . ' ' . $row['LastName']);
        } else {
          $name = $row['CompanyName'] = $row['EmailAddress'];
        }
        $result = DB::_select('debtor_id')->from('debtors')->where('webid =', $row["CustomerID"])->fetch()->assoc()->one();
        if ($result['debtor_id'] > 0) {
          $c = new Debtor($result['debtor_id']);
        } else {
          $c = new Debtor();
        }
        $c->name                                      = $c->debtor_ref = $name;
        $c->branches[$c->defaultBranch]->post_address = $row["BillingAddress2"];
        $c->branches[$c->defaultBranch]->br_address   = $row["BillingAddress1"];
        $c->branches[$c->defaultBranch]->city         = $row["City"];
        $c->branches[$c->defaultBranch]->state        = $row["State"];
        $c->branches[$c->defaultBranch]->postcode     = $row["PostalCode"];
        $c->branches[$c->defaultBranch]->contact_name = $row["FirstName"];
        $c->branches[$c->defaultBranch]->phone        = $row["PhoneNumber"];
        $c->branches[$c->defaultBranch]->fax          = $row["FaxNumber"];
        $c->branches[$c->defaultBranch]->website      = $row["WebsiteAddress"];
        $c->branches[$c->defaultBranch]->email        = $row["EmailAddress"];
        $c->address                                   = $row["BillingAddress1"];
        $c->post_address                              = $row ["BillingAddress2"];
        $c->tax_id                                    = $row["TaxID"];
        $c->webid                                     = $row["CustomerID"];
        $c->contact_name                              = $row["FirstName"] . ' ' . $row["LastName"];
        try {
          $c->save();
        } catch (\ADV\Core\DB\DBDuplicateException $e) {
          $this->status->set(true, 'Update ', "Customer {$c->name} could not be added or updated. {$c->webid}.<br>" . $result['address'] . ":" . $row["BillingAddress1"]);
          continue;
        }
        if ($c->debtor_id > 0) {
          $this->status->set(true, 'update', "Customer {$c->name} has been updated. {$c->id} ");
          $updated++;
        } else {
          $added++;
          $this->status->set(true, 'add', "Customer  {$c->name} has been added.  {$c->id} ");
        }
        DB::_update('WebCustomers')->value('extid', $c->id)->where('CustomerID=', $row['CustomerID'])->exec();
      }
      Event::notice("Added $added Customers. Updated $updated Customers.");
      return $this->status->set(true, 'adding', "Added $added Customers. Updated $updated Customers.");
    }
  }
