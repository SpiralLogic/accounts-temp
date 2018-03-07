<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /**
   */
  class Language {
    use Traits\Singleton;

    public $name;
    /**
     * @var string
     * ar_EG, en_GB
     */
    public $code;
    /**
     * @var
     * eg. UTF-8, CP1256, ISO8859-1
     */
    public $encoding;
    /**
     * @var string
     * Currently support for Left-to-Right (ltr) and Right-To-Left (rtl)
     */
    public $dir;
    protected $installed_languages;
    public $is_locale_file;
    /**
     * @param        $name
     * @param        $code
     * @param        $encoding
     * @param string $dir
     */
    public function __construct($name = null, $code = null, $encoding = null, $dir = 'ltr') {
      $this->setLanguage($name, $code, $encoding, $dir);
    }
    /**
     * @param null   $name
     * @param        $code
     * @param null   $encoding
     * @param string $dir
     */
    public function setLanguage($name = null, $code = null, $encoding = null, $dir = 'ltr') {
      $changed = $this->code != $code;
      if ($changed) {
        $this->name           = $name;
        $this->code           = $code;
        $this->encoding       = $encoding;
        $this->dir            = $dir;
        $locale               = PATH_LANG . $this->code . "/locale.php";
        $this->is_locale_file = file_exists($locale);
        if ($this->is_locale_file) {
          /** @noinspection PhpIncludeInspection */
          include($locale);
        }
      }
    }
  }

  if (!function_exists("_")) {
    /**
     * @param $text
     *
     * @return mixed
     */
    function _($text) {
      return $text;
    }
  }
