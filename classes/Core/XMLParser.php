<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /** **/
  class XMLParser
  {
    /**
     * @public null
     */
    public $rawXML;
    /**
     * @public null
     */
    public $parser;
    /**
     * @public array
     */
    public $valueArray = [];
    /**
     * @public array
     */
    public $keyArray = [];
    /**
     * @public array
     */
    public $parsed = [];
    /**
     * @public int
     */
    public $index = 0;
    /**
     * @public string
     */
    public $attribKey = 'attributes';
    /**
     * @public string
     */
    public $valueKey = 'value';
    /**
     * @public string
     */
    public $cdataKey = 'cdata';
    /**
     * @public bool
     */
    public $isError = false;
    /**
     * @public string
     */
    public $error = '';
    /**
     * @public string
     */
    public $status = '';
    /**
     * @param null $xml
     */
    public function __construct($xml = null) {
      $this->rawXML = $xml;
    }
    /**
     * @param null $xml
     *
     * @return array|bool
     */
    public function parse($xml = null) {
      if (!is_null($xml)) {
        $this->rawXML = $xml;
      }
      $this->isError = false;
      if (!$this->parseInit()) {
        return false;
      }
      $this->index  = 0;
      $this->parsed = $this->parseRecurse();
      $this->status = 'parsing complete';
      return $this->parsed;
    }
    /**
     * @return array
     */
    public function parseRecurse() {
      $found    = [];
      $tagCount = [];
      while (isset($this->valueArray[$this->index])) {
        $tag = $this->valueArray[$this->index];
        $this->index++;
        if ($tag['type'] == 'close') {
          return $found;
        }
        if ($tag['type'] == 'cdata') {
          $tag['tag']  = $this->cdataKey;
          $tag['type'] = 'complete';
        }
        $tagName = $tag['tag'];
        if (isset($tagCount[$tagName])) {
          if ($tagCount[$tagName] == 1) {
            $found[$tagName] = array($found[$tagName]);
          }
          $tagRef = & $found[$tagName][$tagCount[$tagName]];
          $tagCount[$tagName]++;
        } else {
          $tagCount[$tagName] = 1;
          $tagRef             = & $found[$tagName];
        }
        switch ($tag['type']) {
          case 'open':
            $tagRef = $this->parseRecurse();
            if (isset($tag['attributes'])) {
              $tagRef[$this->attribKey] = $tag['attributes'];
            }
            if (isset($tag['value'])) {
              if (isset($tagRef[$this->cdataKey])) {
                $tagRef[$this->cdataKey] = (array)$tagRef[$this->cdataKey];
                array_unshift($tagRef[$this->cdataKey], $tag['value']);
              } else {
                $tagRef[$this->cdataKey] = $tag['value'];
              }
            }
            break;
          case 'complete':
            if (isset($tag['attributes'])) {
              $tagRef[$this->attribKey] = $tag['attributes'];
              $tagRef                   = & $tagRef[$this->valueKey];
            }
            if (isset($tag['value'])) {
              $tagRef = $tag['value'];
            }
            break;
        }
      }
      return $found;
    }
    /**
     * @return bool
     */
    public function parseInit() {
      $this->parser = xml_parser_create();
      $parser       = $this->parser;
      xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
      xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
      if (!$res = (bool)xml_parse_into_struct($parser, $this->rawXML, $this->valueArray, $this->keyArray)) {
        $this->isError = true;
        $this->error   = 'error: ' . xml_error_string(xml_get_error_code($parser)) . ' at line ' . xml_get_current_line_number($parser);
      }
      xml_parser_free($parser);
      return $res;
    }
    /**
     * @static
     *
     * @param $data
     *
     * @return array|mixed
     */
    public static function XMLtoArray($data) {
      $XML    = new XMLParser($data);
      $array  = $XML->parse();
      $result = '';
      if (!$XML->isError && is_array($array['xmldata'])) {
        foreach ($array['xmldata'] as $key => $value) {
          $result[$key] = $value;
        }
        if (count($result) == 1) {
          return current($result);
        } else {
          return $result;
        }
      } else {
        return false;
      }
    }
  }
