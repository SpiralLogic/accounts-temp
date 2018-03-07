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
  class Files
  {
    /**
     * @static
     *
     * @param      $backupfile
     * @param      $fileData
     * @param bool $zip
     *
     * @return bool
     * saves the string in $fileData to the file $backupfile as gz file or not ($zip)
     * returns backup file name if name has changed (zip), else true. If saving failed, return value is false
     */
    public static function saveToFile($backupfile, $fileData, $zip = false) {
      if ($zip == "gzip") {
        $zp = gzopen(PATH_BACKUP . $backupfile, "a9");
        if ($zp) {
          gzwrite($zp, $fileData);
          gzclose($zp);
          return true;
        } else {
          return false;
        }
        // $zip contains the timestamp
      } elseif ($zip == "zip") {
        // based on zip.lib.php 2.2 from phpMyBackupAdmin
        // offical zip format: http://www.pkware.com/appnote.txt
        // End of central directory record
        $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
        // "local file header" segment
        $unc_len = strlen($fileData);
        $crc     = crc32($fileData);
        $zdata   = gzcompress($fileData);
        // extend stored file name with suffix
        // needed for decoding (because of crc bug)
        $name_suffix  = substr($zdata, -4, 4);
        $name_suffix2 = "_";
        for ($i = 0; $i < 4; $i++) {
          $name_suffix2 .= sprintf("%03d", ord($name_suffix[$i]));
        }
        $name = substr($backupfile, 0, strlen($backupfile) - 8) . $name_suffix2 . ".sql";
        // fix crc bug
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
        $c_len = strlen($zdata);
        // dos time
        $timearray = getdate($zip);
        $dostime   = (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
        $dtime     = dechex($dostime);
        $hexdtime  = "\x" . $dtime[6] . $dtime[7] . "\x" . $dtime[4] . $dtime[5] . "\x" . $dtime[2] . $dtime[3] . "\x" . $dtime[0] . $dtime[1];
        // ver needed to extract, gen purpose bit flag, compression method, last mod time and date
        $sub1 = "\x14\x00\x00\x00\x08\x00" . $hexdtime;
        // crc32, compressed filesize, uncompressed filesize
        $sub2 = pack('V', $crc) . pack('V', $c_len) . pack('V', $unc_len);
        $fr   = "\x50\x4b\x03\x04" . $sub1 . $sub2;
        // length of filename, extra field length
        $fr .= pack('v', strlen($name)) . pack('v', 0);
        $fr .= $name;
        // "file data" segment and "data descriptor" segment (optional but necessary if archive is not served as file)
        $fr .= $zdata . $sub2;
        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00"; // version made by
        $cdrec .= $sub1 . $sub2;
        // length of filename, extra field length, file comment length, disk number start, internal file attributes, external file attributes - 'archive' bit set, offset
        $cdrec .= pack('v', strlen($name)) . pack('v', 0) . pack('v', 0) . pack('v', 0) . pack('v', 0) . pack('V', 32) . pack('V', 0);
        $cdrec .= $name;
        // combine data
        $fileData = $fr . $cdrec . $eof_ctrl_dir;
        // total # of entries "on this disk", total # of entries overall, size of central dir, offset to start of central dir, .zip file comment length
        $fileData .= pack('v', 1) . pack('v', 1) . pack('V', strlen($cdrec)) . pack('V', strlen($fr)) . "\x00\x00";
        /** @noinspection PhpAssignmentInConditionInspection */
        if ($zp = fopen(PATH_BACKUP . $backupfile, "a")) {
          fwrite($zp, $fileData);
          fclose($zp);
          return true;
        } else {
          return false;
        }
        // uncompressed
      } else {
        /** @noinspection PhpAssignmentInConditionInspection */
        if ($zp = fopen(PATH_BACKUP . $backupfile, "a")) {
          fwrite($zp, $fileData);
          fclose($zp);
          return true;
        } else {
          return false;
        }
      }
    }
    /**
     * @static
     *
     * @param $size
     *
     * @return string
     */
    public static function convertSize($size) {
      $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
      $i    = (int)floor(log($size, 1024));
      return round($size / pow(1024, $i), 2) . ' ' . $unit[$i];
    }
    /**
     * @static
     *
     * @param      $path
     * @param bool $wipe
     */
    public static function flushDir($path, $wipe = false) {
      $dir = opendir($path);
      while (false !== ($fname = readdir($dir))) {
        if ($fname == '.' || $fname == '..' || $fname == 'CVS' || (!$wipe && $fname == 'index.php')) {
          continue;
        }
        if (is_dir($path . DS . $fname)) {
          static::flushDir($path . DS . $fname, $wipe);
          if ($wipe) {
            rmdir($path . DS . $fname);
          }
        } else {
          unlink($path . DS . $fname);
        }
      }
    }
  }
