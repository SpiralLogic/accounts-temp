<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Reports;

  use PHPMailer;
  use phpmailerException;

  /** **/
  class Email
  {
    use Doctext;

    /** @var array * */
    public $to = [];
    /** @var array * */
    public $cc = [];
    /** @var array * */
    public $bcc = [];
    /** @var array * */
    public $attachment = [];
    /** @var string * */
    public $boundary = "";
    /** @var string * */
    public $header = "";
    /** @var string * */
    public $subject = "";
    /** @var string * */
    public $body = "";
    /** @var \PHPMailer * */
    public $mail;
    /** @var string * */
    public $toerror = "No vaild email address";
    protected $Config;
    /**
     * @param bool $defaults
     */
    public function __construct($defaults = true) {
      $this->Config = \Config::i();
      $this->mail   = new PHPMailer(true);
      $this->mail->IsSMTP(); // telling the class to use SMTP
      $this->mail->Host     = $this->Config->get('email.server'); // SMTP server
      $this->mail->Username = $this->Config->get('email.username');
      $this->mail->Password = $this->Config->get('email.password');
      $this->mail->From     = $this->Config->get('email.from_email');
      $this->mail->SMTPAuth = true;
      $this->mail->WordWrap = 50;
      if ($defaults) {
        $this->mail->FromName = $this->Config->get('email.from_name');
        $bcc                  = $this->Config->get('email.bcc');
        if ($bcc) {
          $this->mail->AddBCC($bcc);
        }
      }
    }
    /**
     * @param $email
     */
    private function _checkEmail($email) {
      if (preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email)) {
        $this->toerror = false;
      }
    }
    /**
     * @param $mail
     */
    public function to($mail) {
      $this->_checkEmail($mail);
      $this->mail->AddAddress($mail);
    }
    /**
     * @param $mail
     */
    public function from($mail) {
      $this->_checkEmail($mail);
      $this->mail->From = $mail;
    }
    /**
     * @param $mail
     */
    public function cc($mail) {
      $this->_checkEmail($mail);
      $this->mail->AddCC($mail);
    }
    /**
     * @param $mail
     */
    public function bcc($mail) {
      $this->_checkEmail($mail);
      $this->mail->AddBCC($mail);
    }
    /**
     * @param $file
     */
    public function attachment($file) {
      $this->mail->AddAttachment($file);
    }
    /**
     * @param $subject
     */
    public function subject($subject) {
      $this->mail->Subject = $subject;
    }
    /**
     * @param $text
     */
    public function text($text) {
      //$this->mail->ContentType = "Content-Type: text/plain; charset=ISO-8859-1\n";
      //$this->mail->Encoding = "8bit";
      $this->mail->Body = $text . "\n";
    }
    /**
     * @param $html
     */
    public function html($html) {
      //$this->mail->ContentType = "text/html; charset=ISO-8859-1";
      //$this->mail->Encoding = "quoted-printable";
      $this->mail->IsHTML(true);
      $this->mail->AltBody = $html . "\n";
      $this->mail->Body    = "<html><body>\n" . $html . "\n</body></html>\n";
    }
    /**
     * @param $filename
     *
     * @return string
     */
    public function mime_type($filename) {
      $file = basename($filename, '.zip');
      if ($filename == $file . '.zip') {
        return 'application/x-zip-compressed';
      }
      $file = basename($filename, '.pdf');
      if ($filename == $file . '.pdf') {
        return 'application/pdf';
      }
      $file = basename($filename, '.csv');
      if ($filename == $file . '.csv') {
        return 'application/vnd.ms-excel';
      }
      $file = basename($filename, '.tar');
      if ($filename == $file . '.tar') {
        return 'application/x-tar';
      }
      $file = basename($filename, '.tar.gz');
      if ($filename == $file . '.tar.gz') {
        return 'application/x-tar-gz';
      }
      $file = basename($filename, '.tgz');
      if ($filename == $file . '.tgz') {
        return 'application/x-tar-gz';
      }
      $file = basename($filename, '.gz');
      if ($filename == $file . '.gz') {
        return 'application/x-gzip';
      }
      return 'application/unknown';
    }
    /**
     * @return bool
     */
    public function send() {
      if ($this->toerror) {
        return false;
      }
      try {
        $ret = $this->mail->Send();
        return $ret;
      } catch (phpmailerException $e) {
        $this->toerror = $e->errorMessage();
        return false;
      }
    }
  }
