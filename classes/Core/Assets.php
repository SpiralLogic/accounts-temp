<?php
  /* SmartOptimizer v1.8
     * SmartOptimizer enhances your website performance using techniques
     * such as compression, concatenation, minifying, caching, and embedding on demand.
     *
     * Copyright (c) 2006-2010 Ali Farhadi (http://farhadi.ir/)
     * Released under the terms of the GNU Public License.
     * See the GPL for details (http://www.gnu.org/licenses/gpl.html).
     *
     * Author: Ali Farhadi (a.farhadi@gmail.com)
     * Website: http://farhadi.ir/
     */
  namespace ADV\Core;

  use RangeException;

  /** **/
  class Assets
  {
    protected $baseDir = ROOT_WEB;
    protected $charSet = 'UTF-8';
    protected $debug = false;
    protected $gzip = false;
    protected $compressionLevel = 9;
    protected $gzipExceptions = ['gif', 'jpeg', 'jpg', 'png', 'swf', 'ico'];
    protected $minify = true;
    protected $concatenate = true;
    protected $separator = ',';
    protected $serverCache = true;
    protected $serverCacheCheck = true;
    protected $cacheDir = 'cache';
    protected $cachePrefix = 'so_';
    protected $clientCache = true;
    protected $clientCacheCheck = true;
    protected $file = [];
    protected $minifyTypes
      = [
        'js'  => [
          'minify'   => false, //
          'minifier' => '\\ADV\\Core\\JSMin', //
          'settings' => [] //
        ], //
        'css' => [ //
          'minify'   => true, //
          'minifier' => '\\ADV\\Core\\CSSMin', //
          'settings' => [ //
            'embed'           => true, //
            'embedMaxSize'    => 5120, //
            'embedExceptions' => 'htc',
          ]
        ]
      ];
    protected $mimeTypes
      = [
        "js"   => "text/javascript",
        "css"  => "text/css",
        "htm"  => "text/html",
        "html" => "text/html",
        "xml"  => "text/xml",
        "txt"  => "text/plain",
        "jpg"  => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png"  => "image/png",
        "gif"  => "image/gif",
        "swf"  => "application/x-shockwave-flash",
        "ico"  => "image/x-icon",
      ];
    protected $files = [];
    protected $fileType;
    protected $cacheFile;
    protected $generate = true;
    protected $fileDir; //mime typesprotected $cachedFile ;
    /**
     * @param $status
     */
    protected function headerExit($status) {
      header("Pragma: Public");
      header("Expires: " . $this->gmdatestr(time() + 315360000));
      header("Cache-Control: max-age=315360000");
      header("HTTP/1.0 $status");
      header("Vary: Accept-Encoding", false);
      $this->contentHeader();
      exit();
    }
    protected function headerNoCache() {
      // already expired
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      // always modified
      header("Last-Modified: " . $this->gmdatestr());
      // HTTP/1.1
      header("Cache-Control: no-store, no-cache, must-revalidate");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Cache-Control: max-age=0", false);
      header("Vary: Accept-Encoding", false);
      // HTTP/1.0
      header("Pragma: no-cache");
      //generate a unique Etag each time
      header('Etag: ' . microtime());
      $this->contentHeader();
    }
    protected function headerNeverExpire() {
      header("Expires: " . $this->gmdatestr(time() + 315360000));
      header("Cache-Control: max-age=315360000");
      header("Vary: Accept-Encoding", false);
      header("Last-Modified: " . $this->gmdatestr());
      $this->contentHeader();
    }
    /**
     * @param $msg
     */
    protected function debugExit($msg) {
      if (!$this->debug) {
        $this->headerExit('404 Not Found');
      }
      $this->headerNoCache();
      header("Content-Encoding: none");
      echo "<script>\n";
      echo "alert('Optimizer Error: " . str_replace("\n", "\\n", addslashes($msg)) . "');\n";
      echo "</script>\n";
      exit();
    }
    /**
     * @param null $time
     *
     * @return string
     */
    protected function gmdatestr($time = null) {
      if (is_null($time)) {
        $time = time();
      }
      return gmdate("D, d M Y H:i:s", $time) . " GMT";
    }
    /**
     * @return int|mixed
     */
    protected function filesmtime() {
      static $filesmtime;
      if ($filesmtime) {
        return $filesmtime;
      }
      foreach ($this->files as $file) {
        if (!file_exists($file)) {
          $this->debugExit("File not found ($file).");
        }
        $filesmtime = max(filemtime($file), $filesmtime);
      }
      return $filesmtime;
    }
    /**

     */
    public function __construct() {
      $this->getFiles();
      $this->setCompression();
      $this->serverCache();
      $this->clientCache();
      if ($this->generate) {
        $content = $this->generate();
        if ($this->serverCache) {
          $this->writeCache($content);
        } else {
          $this->sendContent($content);
        }
      }
      $this->sendFile();
    }
    /**
     * @return string
     */
    protected function generate() {
      $minify = false;
      if ($this->minify && isset($this->minifyTypes[$this->fileType])) {
        $minify_type_settings = $this->minifyTypes[$this->fileType];
        if (isset($minify_type_settings['minify']) && $minify_type_settings['minify']) {
          if (isset($minify_type_settings['minifier'])) {
            $minifier_class                   = $minify_type_settings['minifier'];
            $minify_type_settings['settings'] = $minify_type_settings['settings'] ? : [];
            $minify                           = true;
          }
        }
      }
      $content = [];
      foreach ($this->files as $file) {
        (($current = file_get_contents($file)) !== false) || $this->debugExit("File not found ($file).");
        if ($minify && strpos($file, '.min.' . $this->fileType) === false) {
          $minifier = new $minifier_class($current, array(
                                                         'fileDir'              => $this->fileDir,
                                                         'minify_type_settings' => $minify_type_settings['settings'],
                                                         'mimeTypes'            => $this->mimeTypes
                                                    ));
          $current  = $minifier->minify();
        }
        $content[] = $current;
      }
      $content = implode("\n", $content);
      if ($this->gzip) {
        return gzencode($content, $this->compressionLevel);
      }
      return $content;
    }
    /**
     * @param $content
     */
    protected function writeCache($content) {
      $tmpfile = tempnam($this->cacheDir, 'ADV');
      $handle  = fopen($tmpfile, 'w');
      if (flock($handle, LOCK_EX)) {
        fwrite($handle, $content);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
      } else {
        throw new RangeException('Could not write to cache!');
      }
      unlink($this->cacheFile);
      rename($tmpfile, $this->cacheFile);
    }
    protected function sendFile() {
      header('Content-Length: ' . filesize($this->cacheFile));
      if ($this->gzip) {
        header('Content-Encoding: gzip');
      }
      ob_clean();
      flush();
      readfile($this->cacheFile);
      exit;
    }
    protected function clientCache() {
      if ($this->serverCache) {
        $mtime = $this->generate ? time() : filemtime($this->cacheFile);
      } else {
        $mtime = $this->filesmtime();
      }
      $mtimestr = $this->gmdatestr($mtime);
      if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || $_SERVER['HTTP_IF_MODIFIED_SINCE'] != $mtimestr) {
        if ($this->clientCache && $this->clientCacheCheck) {
          $this->headerUpdatedCache($mtimestr);
        } elseif ($this->clientCache) {
          $this->headerNeverExpire();
        } else {
          $this->headerNoCache();
        }
      } else {
        $this->headerExit('304 Not Modified');
      }
    }
    /**
     * @param $mtimestr
     */
    protected function headerUpdatedCache($mtimestr) {
      header("Last-Modified: " . $mtimestr);
      header("Expires: " . $this->gmdatestr(time() + 315360000));
      header("Vary: Accept-Encoding", false);
      header("Cache-Control: must-revalidate");
    }
    /**
     * @return bool
     */
    protected function serverCache() {
      $this->cacheFile = $cachedFile = $this->cacheDir . DIRECTORY_SEPARATOR . $this->cachePrefix . md5(serialize($this->files)) . '.' . $this->fileType . ($this->gzip ? '.gz' :
        '');
      if (!is_writeable($this->cacheFile)) {
        $this->serverCache = false;
      }
      if (!$this->serverCache) {
        $this->generate = true;
      } else {
        $this->generate = !file_exists($cachedFile) || ($this->serverCacheCheck && $this->filesmtime() > filemtime($cachedFile));
      }
    }
    protected function setCompression() {
      if (in_array($this->fileType, $this->gzipExceptions)) {
        $this->gzip = false;
      }
      if (!function_exists('gzencode') || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
        $this->gzip = false;
      }
    }
    protected function getFiles() {
      $fileTypes = [];
      $fileNames = '';
      list($query) = explode('?', urldecode($_SERVER['QUERY_STRING']));
      if (preg_match('/^\/?(.+\/)?(.+)$/', $query, $matchResult)) {
        $fileNames     = $matchResult[2];
        $this->fileDir = $this->baseDir . $matchResult[1];
      } else {
        $this->debugExit("Invalid file name ($query)");
      }
      if ($this->concatenate) {
        $this->files       = explode('&', $fileNames);
        $this->files       = explode($this->separator, $this->files[0]);
        $this->concatenate = count($this->files) > 1;
      } else {
        $this->files = [$fileNames];
      }
      foreach ($this->files as &$file) {
        if (preg_match('/^[^\x00]+\.([a-z0-9]+)$/i', $file, $matchResult)) {
          $fileTypes[] = strtolower($matchResult[1]);
        } else {
          $this->debugExit("Unsupported file ($file)");
        }
        $file = $this->fileDir . $file;
      }
      if ($this->concatenate && count(array_unique($fileTypes)) > 1) {
        $this->debugExit("Files must be of the same type.");
      }
      $this->fileType = $fileTypes[0];
      if (!isset($this->mimeTypes[$this->fileType])) {
        $this->debugExit("Unsupported file type ($this->fileType)");
      }
      $this->contentHeader();
    }
    protected function contentHeader() {
      if (isset($this->mimeTypes[$this->fileType])) {
        header('Content-Type: ' . $this->mimeTypes[$this->fileType] . '; charset=' . $this->charSet);
      }
    }
    protected function sendContent($content) {
      echo $content;
      exit;
    }
  }
