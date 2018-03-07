<?php
  use ADV\App\User;

  /** **/
  class Reports_Printer_Remote
  {
    /** @var string * */
    public $host;
    /** @var int * */
    public $port;
    /** @var int * */
    public $timeout;
    /** @var */
    public $queue;
    //
    //	Setting connection parameters
    //
    /**
     * @param        $queue
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     */
    public function __construct($queue, $host = '', $port = 515, $timeout = 20) {
      if ($host == '') {
        $host = $_SERVER['REMOTE_ADDR'];
      } // default is user's host
      $this->host    = $host;
      $this->port    = $port;
      $this->timeout = $timeout;
      $this->queue   = $queue;
    }
    //
    //	Send file to remote network printer.
    //
    /**
     * @param $fname
     *
     * @return mixed|string
     */
    public function print_file($fname) {
      $queue = $this->queue;
      //Private static function prints waiting jobs on the queue.
      $ret = $this->flush_queue($queue);
      //		if($ret) return $ret;
      //Open a new connection to send the control file and data.
      $stream = fsockopen("tcp://" . $this->host, $this->port, $errNo, $errStr, $this->timeout);
      if (!$stream) {
        return _('Cannot open connection to printer');
      }
      if (!isset($_SESSION['_print_job'])) {
        $_SESSION['print_job'] = 0;
      }
      $job = $_SESSION['print_job']++;
      //Set printer to receive file
      fwrite($stream, chr(2) . $queue . "\n");
      $ack = fread($stream, 1);
      if ($ack != 0) {
        fclose($stream);
        return _('Printer does not acept the job') . ' (' . ord($ack) . ')';
      }
      // Send Control file.
      $server = $_SERVER['SERVER_NAME'];
      $ctrl   = "H" . $server . "\nP" . substr(User::_i()->loginname, 0, 31) . "\nfdfA" . $job . $server . "\n";
      fwrite($stream, chr(2) . strlen($ctrl) . " cfA" . $job . $server . "\n");
      $ack = fread($stream, 1);
      if ($ack != 0) {
        fclose($stream);
        return _('Error sending print job control file') . ' (' . ord($ack) . ')';
      }
      fwrite($stream, $ctrl . chr(0)); //Write null to indicate end of stream
      $ack = fread($stream, 1);
      if ($ack != 0) {
        fclose($stream);
        return _('Print control file not accepted') . ' (' . ord($ack) . ')';
      }
      $data = fopen($fname, "rb");
      fwrite($stream, chr(3) . filesize($fname) . " dfA" . $job . $server . "\n");
      $ack = fread($stream, 1);
      if ($ack != 0) {
        fclose($stream);
        return _('Cannot send report to printer') . ' (' . ord($ack) . ')';
      }
      while (!feof($data)) {
        if (fwrite($stream, fread($data, 8192)) < 8192) {
          break;
        }
      }
      fwrite($stream, chr(0)); //Write null to indicate end of stream
      $ack = fread($stream, 1);
      if ($ack != 0) {
        fclose($stream);
        return _('No ack after report printout') . ' (' . ord($ack) . ')';
      }
      fclose($data);
      fclose($stream);
      return '';
    }
    //
    //	Print all waiting jobs on remote printer queue.
    //
    /**
     * @param $queue
     *
     * @return bool|mixed|string
     */
    public function flush_queue($queue) {
      $stream = fsockopen("tcp://" . $this->host, $this->port, $errNo, $errStr, $this->timeout);
      if (!$stream) {
        return _('Cannot flush printing queue');
        // .':<br>' . $errNo." (".$errStr.")"; return 0 (success) even on failure
      } else {
        //Print any waiting jobs
        fwrite($stream, chr(1) . $queue . "\n");
        while (!feof($stream)) {
          fread($stream, 1);
        }
      }
      return false;
    }
  }
