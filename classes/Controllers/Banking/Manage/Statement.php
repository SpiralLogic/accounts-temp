<?php

  namespace ADV\Controllers\Banking\Manage;

  use ADV\Core\DB\DB;
  use ADV\Core\Status;
  use ADV\Core\Input\Input;
  use ADV\Core\View;
  use ADV\App\Forms;
  use Bank_Account;
  use ADV\App\Form\Form;
  use ADV\Core\DB\DBSelectException;

  /**
   *
   */
  class Statement extends \ADV\App\Controller\Action
  {
    protected $bank_account;
    protected $status;
    protected $security = SA_RECONCILE;
    protected function before() {
      $this->bank_account = & $this->Input->postGlobal('bank_account', INPUT::NUMERIC, Bank_Account::get_default()['id']);
      if (Forms::isListUpdated('bank_account')) {
        $this->Session->setGlobal('bank_account', $this->bank_account);
      }
      if (REQUEST_POST && $this->action = 'upload') {
        $this->getUpload();
        $this->Ajax->addStatus($this->status);
      }
    }
    protected function index() {
      $this->setTitle("Reconcile TO Bank Statement Compare");
      $view = new View('form/simple');
      $form = new Form();
      $form->start('statement', '', true);
      $view->set('form', $form);
      $view['title'] = 'Upload Bank Statement for reconcile';
      $field         = $form->text('csvitems')->focus()->label('Bank Statement (.csv)');
      $field['type'] = 'file';
      $form->custom(Bank_Account::select('bank_account', null, true))->label('Bank Acocunt:');
      $form->submit('upload', 'Upload');
      $view->render();
    }
    /**
     * @return bool
     */
    protected function getUpload() {
      $filename     = ROOT_DOC . 'tmp/bankstatement.csv';
      $this->status = new Status();
      if (sizeof($_FILES) == 0) {
        return $this->status->set('false', 'No files uploaded');
      }
      if (file_exists($filename)) {
        unlink($filename);
      }
      if (!move_uploaded_file($_FILES['csvitems']['tmp_name'], $filename)) {
        return $this->status->set(false, 'Could not move uploaded file!');
      }
      ini_set('auto_detect_line_endings', 1);
      $file     = fopen($filename, 'r');
      $existing = $inserted = $errors = 0;
      while (($item = fgetcsv($file, 1000, ',')) !== false) {
        if (isset($item[5]) && strlen($item[5] > $item[3])) {
          $memo = $item[4] . ' - ' . $item[5];
          $rb   = (is_numeric($item[6])) ? $item[6] : 0;
        } else {
          $memo = $item[2] . ' - ' . $item[3];
          $rb   = (is_numeric($item[4])) ? $item[4] : 0;
        }
        $amount = $item[1];
        $date   = strtotime($item[0]);
        if (!is_numeric($amount) || $date===false || date('Y', $date) == 1970 ) {
          $errors++;
          continue;
        }
        $date = date('Y-m-d', $date);
        try {
          $result = DB::_select('COUNT(*) as count')->from('temprec')->where('date=', $date)->andWhere('amount=', $amount)->andWhere('rb=', $rb)->fetch()->one();
          if ($result['count'] == 0) {
            DB::_insert('temprec')->values(['date' => $date, 'amount' => $amount, 'memo' => $memo, 'rb' => $rb, 'bank_account_id' => $this->bank_account])->exec();
            $inserted++;
          } else {
            $existing++;
          }
        } catch (DBSelectException $e) {
          return $this->status->set(false, 'Could not search current statement entries! Inserted: ' . $inserted);
        }
      }
      fclose($file);
      unlink($filename);
      return $this->status->set(true, 'Bank statement successfully uploaded, records inserted: ' . $inserted . ' Errors: ' . $errors . ' Existing: ' . $existing);
    }
  }
