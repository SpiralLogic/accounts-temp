<?php
  /** **/
  namespace ADV\Core;

  /** **/
  class CSSMin
  {
    protected $minified = '';
    protected $settings
      = array(
        'embed'           => true,
        'embedMaxSize'    => 5120,
        'embedExceptions' => array('htc')
      );
    protected $mimeTypes = [];
    protected $filedir = '.';
    protected $source;
    /**
     * @param       $source
     * @param array $options
     *
     * @internal param $filedir
     * @internal param \string[] $minify_type_settings
     * @internal param array $mimeTypes
     * @internal param $url
     * @internal param $count
     * @return CSSMin
     */
    public function __construct($source, $options = []) {
      if (isset($options['filedir'])) {
        $this->filedir = $options['filedir'];
      }
      if (isset($options['minify_type_settings'])) {
        $this->settings = array_merge($this->settings, (array)$options['minify_type_settings']);
      }
      $this->embed = $this->settings['embed'] && (!preg_match('/msie/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/msie 8|opera/i', $_SERVER['HTTP_USER_AGENT']));
      if (isset($options['mimeTypes'])) {
        $this->mimeTypes = array_merge($this->mimeTypes, (array)$options['mimeTypes']);
      }
      $this->source = $source;
    }
    /**
     * @param $url
     * @param $count
     *
     * @return string
     */
    protected function convertUrl($url, $count) {
      $baseUrl = (strpos($url, '..') === 0) ? '' : '/';
      $url     = trim($url);
      if (preg_match('@^[^/]+:@', $url)) {
        return $url;
      }
      $fileType = substr(strrchr($url, '.'), 1);
      if (isset($this->mimeTypes[$fileType])) {
        $mimeType = $this->mimeTypes[$fileType];
      } elseif (function_exists('mime_content_type')) {
        $file     = ROOT_WEB . preg_split('/[#?]/', $url)[0];
        $mimeType = mime_content_type($file);
      } else {
        $mimeType = null;
      }
      if (!$this->settings['embed'] || !file_exists($this->filedir . $url) || ($this->settings['embedMaxSize'] > 0 && filesize(
        $this->filedir . $url
      ) > $this->settings['embedMaxSize']) || !$fileType || in_array($fileType, (array)$this->settings['embedExceptions']) || !$mimeType || $count > 1
      ) {
        if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'] . '?') === 0 || strpos($_SERVER['REQUEST_URI'], rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/') . '/?') === 0
        ) {
          if ($baseUrl) {
            return $this->filedir . $url;
          }
        }
        return $baseUrl . $url;
      }
      $contents = file_get_contents($this->filedir . $url);
      if ($fileType == 'css') {
        $oldFileDir    = $this->filedir;
        $this->filedir = rtrim(dirname($this->filedir . $url), '\/') . '/';
        $oldBaseUrl    = $baseUrl;
        $baseUrl       = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . rtrim(
          dirname($_SERVER['SCRIPT_NAME']),
          '\/'
        ) . '/' . $this->filedir;
        $contents      = $this->minify($contents);
        $this->filedir = $oldFileDir;
        $baseUrl       = $oldBaseUrl;
      }
      $base64 = base64_encode($contents);
      return 'data:' . $mimeType . ';base64,' . $base64;
    }
    /**
     * @internal param $str
     * @return string
     */
    public function minify() {
      $str          = $this->source;
      $res          = '';
      $i            = 0;
      $inside_block = false;
      $current_char = '';
      while ($i + 1 < strlen($str)) {
        if ($str[$i] == '"' || $str[$i] == "'") { //quoted string detected
          $res .= $quote = $str[$i++];
          $url = '';
          while ($i < strlen($str) && $str[$i] != $quote) {
            if ($str[$i] == '\\') {
              $url .= $str[$i++];
            }
            $url .= $str[$i++];
          }
          if (strtolower(substr($res, -5, 4)) == 'url(' || strtolower(substr($res, -9, 8)) == '@import ') {
            $url = $this->convertUrl($url, substr_count($str, $url));
          }
          $res .= $url;
          $res .= $str[$i++];
          continue;
        } elseif (strtolower(substr($res, -4)) == 'url(') { //url detected
          $url = '';
          do {
            if ($str[$i] == '\\') {
              $url .= $str[$i++];
            }
            $url .= $str[$i++];
          } while ($i < strlen($str) && $str[$i] != ')');
          $url = $this->convertUrl($url, substr_count($str, $url));
          $res .= $url;
          $res .= $str[$i++];
          continue;
        } elseif ($str[$i] . $str[$i + 1] == '/*') { //css comment detected
          $i += 3;
          while ($i < strlen($str) && $str[$i - 1] . $str[$i] != '*/') {
            $i++;
          }
          if ($current_char == "\n") {
            $str[$i] = "\n";
          } else {
            $str[$i] = ' ';
          }
        }
        if (strlen($str) <= $i + 1) {
          break;
        }
        $current_char = $str[$i];
        if ($inside_block && $current_char == '}') {
          $inside_block = false;
        }
        if ($current_char == '{') {
          $inside_block = true;
        }
        if (preg_match('/[\n\r\t ]/', $current_char)) {
          $current_char = " ";
        }
        if ($current_char == " ") {
          $pattern = $inside_block ? '/^[^{};,:\n\r\t ]{2}$/' : '/^[^{};,>+\n\r\t ]{2}$/';
          if (strlen($res) && preg_match($pattern, $res[strlen($res) - 1] . $str[$i + 1])) {
            $res .= $current_char;
          }
        } else {
          $res .= $current_char;
        }
        $i++;
      }
      if ($i < strlen($str) && preg_match('/[^\n\r\t ]/', $str[$i])) {
        $res .= $str[$i];
      }
      $this->minified = $res;
      return $this->minified;
    }
    /**
     * @return string
     */
    public function __toString() {
      return $this->minified;
    }
  }
