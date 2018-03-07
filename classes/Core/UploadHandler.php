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

  use ADV\Core\DB\DB;

  /** **/
  class UploadHandler
  {
    /**
     */
    private $options;
    /** @var */
    private $order_no;
    /** @var */
    private static $inserted;
    /**
     * @param $order_no
     * @param $options
     */
    public function __construct($order_no, $options) {
      $this->order_no = $order_no;
      error_reporting(E_ALL | E_STRICT);
      ini_set('post_max_size', '3M');
      ini_set('upload_max_filesize', '3M');
      $this->options = array(
        'script_url'              => $_SERVER['DOCUMENT_URI'],
        'upload_dir'              => ROOT_DOC . '/upload/upload/',
        'upload_url'              => ROOT_URL . '/upload/upload/',
        'param_name'              => 'files',
        // The php.ini settings upload_max_filesize and post_max_size
        // take precedence over the following max_file_size setting:
        'max_file_size'           => null,
        'min_file_size'           => 1,
        'accept_file_types'       => '/.+$/i',
        'max_number_of_files'     => 10,
        'discard_aborted_uploads' => true,
        'image_versions'          => array(
          // Uncomment the following version to restrict the size of
          // uploaded images. You can also add additional versions with
          // their own upload directories:
          /*
                                         'large' => array(
                                             'upload_dir' => dirname(__FILE__).'/files/',
                                             'upload_url' => dirname($_SERVER['DOCUMENT_URI']).'/files/',
                                             'max_width' => 1920,
                                             'max_height' => 1200
                                         ),
                                         */
          'thumbnail' => array(
            'upload_dir' => ROOT_DOC . 'upload/upload/thumbnails/',
            'upload_url' => ROOT_URL . 'upload/upload/thumbnails/',
            'max_width'  => 80,
            'max_height' => 80
          )
        )
      );
      if ($options) {
        $this->options = array_replace_recursive($this->options, $options);
      }
    }
    /**
     * @return mixed
     */
    public function get() {
      $info      = [];
      $upload_id = (isset($_REQUEST['id'])) ? stripslashes($_REQUEST['id']) : null;
      if ($upload_id) {
        $sql    = "SELECT content as content,type FROM upload WHERE `id` = {$upload_id}";
        $result = DB::_query($sql, 'Could not retrieve file');
        $result = DB::_fetchAssoc($result);
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: ' . $result['type']);
        $content = $result['content'];
        echo $content;
      } else {
        $sql    = "SELECT `id`,`filename` as name, `size` ,`type` FROM upload WHERE `order_no` = " . $this->order_no;
        $result = DB::_query($sql, 'Could not retrieve upload information');
        if (DB::_numRows($result) < 1) {
          return;
        } else {
          /** @noinspection PhpAssignmentInConditionInspection */
          while ($row = DB::_fetchAssoc($result)) {
            $info[] = $row;
          }
        }
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        echo json_encode($info);
      }
    }
    public function post() {
      $upload = isset($_FILES[$this->options['param_name']]) ? $_FILES[$this->options['param_name']] : array(
        'tmp_name' => null,
        'name'     => null,
        'size'     => null,
        'type'     => null,
        'error'    => null
      );
      $info   = [];
      if (is_array($upload['tmp_name'])) {
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($upload['tmp_name'] as $index => $value) {
          $info[] = $this->handle_file_upload(
            $upload['tmp_name'][$index],
            isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index],
            isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index],
            isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index],
            $upload['error'][$index]
          );
        }
      } else {
        $info[] = $this->handle_file_upload(
          $upload['tmp_name'],
          isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'],
          isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'],
          isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'],
          $upload['error']
        );
      }
      header('Vary: Accept');
      if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
      ) {
        header('Content-type: application/json');
      } else {
        header('Content-type: text/plain');
      }
      echo json_encode($info);
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function insert($id) {
      if (!self::$inserted) {
        JS::_footerFile(
          array(
               '/js/js2/jquery.fileupload.js',
               '/js/js2/jquery.fileupload-ui.js',
               '/js/js2/jquery.fileupload-app.js'
          )
        );
        self::$inserted = true;
      }
      echo '
    <div id="file_upload"><form id="file_upload_" action="/upload/upload.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="file" multiple>
        <button type="submit">Upload</button>
        <div class="js">Upload files</div>
    </form>
    <table id="files" data-order-id="' . $id . '">
        <tr id="template_upload" style="display:none;">
            <td class="file_upload_preview"></td>
            <td class="file_upload_name"></td>
            <td class="file_upload_progress">
                <div></div>
            </td>
            <td class="file_upload_start">
                <button class="ui-button ui-widget ui-state-default ui-corner-all" title="Start">
                    <span class="ui-icon ui-icon-circle-arrow-e">Start</span>
                </button>
            </td>
            <td class="file_upload_cancel">
                <button class="ui-button ui-widget ui-state-default ui-corner-all" title="Cancel">
                    <span class="ui-icon ui-icon-cancel">Cancel</span>
                </button>
            </td>
        </tr>
        <tr id="template_download" style="display:none;">
            <td class="file_upload_preview"><img/></td>
            <td class="file_upload_name"><a></a></td>
            <td class="file_upload_delete" colspan="3">
                <button class="ui-button ui-widget ui-state-default ui-corner-all" title="Delete">
                    <span class="ui-icon ui-icon-trash">Delete</span>
                </button>
            </td>
        </tr>
    </table>
    <div id="file_upload_progress" class="js file_upload_progress">
        <div style="display:none;"></div>
    </div>
    <div class="js">

        <button id="file_upload_delete" class="ui-button ui-state-default ui-corner-all ui-button-text-icon-primary">
            <span class="ui-button-icon-primary ui-icon ui-icon-trash"></span>
            <span class="ui-button-text">Delete All</span>
        </button>
    </div></div>
';
    }
    public function delete() {
      $name   = isset($_REQUEST['file']) ? ($_REQUEST['file']) : null;
      $id     = isset($_REQUEST['id']) ? ($_REQUEST['id']) : null;
      $sql    = "DELETE FROM upload WHERE `id` = {$id} AND `filename` = '{$name}'";
      $result = DB::_query($sql, 'Could not delete file');
      header('Content-type: application/json');
      echo json_encode($result);
    }
    private function make_dir() {
      $old = umask(0);
      //@mkdir($this->upload_dir, 0777);
      umask($old);
    }
    /**
     * @param $file_name
     *
     * @return null|\stdClass
     */
    private function get_file_object($file_name) {
      $file_path = $this->options['upload_dir'] . $file_name;
      if (is_file($file_path) && $file_name[0] !== '.') {
        $file       = new \stdClass();
        $file->name = $file_name;
        $file->size = filesize($file_path);
        $file->url  = $this->options['upload_url'] . rawurlencode($file->name);
        foreach ($this->options['image_versions'] as $version => $options) {
          if (is_file($options['upload_dir'] . $file_name)) {
            $file->{$version . '_url'} = $options['upload_url'] . rawurlencode($file->name);
          }
        }
        $file->delete_url  = $this->options['script_url'] . '?file=' . rawurlencode($file->name);
        $file->delete_type = 'DELETE';
        return $file;
      }
      /*$sql = "SELECT * FROM upload WHERE id = {$upload_id} LIMIT 1";
               $result = DB::_query($sql, 'Could not query uploads');
               $result = DB::_fetchAssoc($result);
               $file = new stdClass();
               $file->name = $result ['filename'];
               $file->type = $result ['type'];
               $file->size = $result ['size'];

               return $file;
               */
      return null;
    }
    /**
     * @return array
     */
    private function get_file_objects() {
      return array_values(array_filter(array_map(array($this, 'get_file_object'), scandir($this->options['upload_dir']))));
    }
    /**
     * @param $file_name
     * @param $options
     *
     * @return bool
     * @return bool
     */
    private function create_scaled_image($file_name, $options) {
      $file_path     = $this->options['upload_dir'] . $file_name;
      $new_file_path = $options['upload_dir'] . $file_name;
      list($img_width, $img_height) = @getimagesize($file_path);
      if (!$img_width || !$img_height) {
        return false;
      }
      $scale = min($options['max_width'] / $img_width, $options['max_height'] / $img_height);
      if ($scale > 1) {
        $scale = 1;
      }
      $new_width  = $img_width * $scale;
      $new_height = $img_height * $scale;
      $new_img    = @imagecreatetruecolor($new_width, $new_height);
      switch (strtolower(substr(strrchr($file_name, '.'), 1))) {
        case 'jpg':
        case 'jpeg':
          $src_img     = @imagecreatefromjpeg($file_path);
          $write_image = 'imagejpeg';
          break;
        case 'gif':
          @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
          $src_img     = @imagecreatefromgif($file_path);
          $write_image = 'imagegif';
          break;
        case 'png':
          @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
          @imagealphablending($new_img, false);
          @imagesavealpha($new_img, true);
          $src_img     = @imagecreatefrompng($file_path);
          $write_image = 'imagepng';
          break;
        default:
          $src_img = $image_method = null;
      }
      $success = $src_img && @imagecopyresampled($new_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height) && isset($write_image) && $write_image(
        $new_img,
        $new_file_path
      );
      // Free up memory (imagedestroy does not delete files):
      @imagedestroy($src_img);
      @imagedestroy($new_img);
      return $success;
    }
    /**
     * @param $uploaded_file
     * @param $file
     * @param $error
     *
     * @return string
     */
    private function has_error($uploaded_file, $file, $error) {
      if ($error) {
        return $error;
      }
      if (!preg_match($this->options['accept_file_types'], $file->name)) {
        return 'acceptFileTypes';
      }
      if ($uploaded_file && is_uploaded_file($uploaded_file)) {
        $file_size = filesize($uploaded_file);
      } else {
        $file_size = $_SERVER['CONTENT_LENGTH'];
      }
      if ($this->options['max_file_size'] && ($file_size > $this->options['max_file_size'] || $file->size > $this->options['max_file_size'])
      ) {
        return 'maxFileSize';
      }
      if ($this->options['min_file_size'] && $file_size < $this->options['min_file_size']
      ) {
        return 'minFileSize';
      }
      if (is_int($this->options['max_number_of_files']) && (count($this->get_file_objects()) >= $this->options['max_number_of_files'])
      ) {
        return 'maxNumberOfFiles';
      }
      return $error;
    }
    /**
     * @param $uploaded_file
     * @param $name
     * @param $size
     * @param $type
     * @param $error
     *
     * @return \stdClass
     */
    private function handle_file_upload($uploaded_file, $name, $size, $type, $error) {
      $file = new \stdClass();
      // Remove path information and dots around the filename, to prevent uploading
      // into different directories or replacing hidden system files.
      // Also remove control characters and spaces (\x00..\x20) around the filename:
      $file->name = trim(basename(stripslashes($name)), ".\x00..\x20");
      $file->size = intval($size);
      $file->type = $type;
      $error      = $this->has_error($uploaded_file, $file, $error);
      if (!$error && $file->name) {
        $file_path   = $this->options['upload_dir'] . $file->name;
        $append_file = !$this->options['discard_aborted_uploads'] && is_file($file_path) && $file->size > filesize($file_path);
        clearstatcache();
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
          // multipart/formdata uploads (POST method uploads)
          if ($append_file) {
            file_put_contents($file_path, fopen($uploaded_file, 'r'), FILE_APPEND);
          } else {
            move_uploaded_file($uploaded_file, $file_path);
          }
        } else {
          // Non-multipart uploads (PUT method support)
          file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
        }
        $file_size = filesize($file_path);
        if ($file_size === $file->size) {
          $file->url = $this->options['upload_url'] . rawurlencode($file->name);
          foreach ($this->options['image_versions'] as $version => $options) {
            if ($this->create_scaled_image($file->name, $options)) {
              $file->{$version . '_url'} = $options['upload_url'] . rawurlencode($file->name);
            }
          }
        } else {
          if ($this->options['discard_aborted_uploads']) {
            unlink($file_path);
            $file->error = 'abort';
          }
        }
        $file->size        = $file_size;
        $file->delete_url  = $this->options['script_url'] . '?file=' . rawurlencode($file->name);
        $file->delete_type = 'MODE_DELETE';
      } else {
        $file->error = $error;
      }
      /* DB::_begin();
               $sql = "INSERT INTO upload (`filename`,`size`,`type`,`order_no`,`content`) VALUES ('{$file->name}','{$file->size}','{$file->type}','{$this->order_no}', '{$content}')";
               DB::_query($sql, 'Could not insert file into database');
               $upload_id = DB::_insertId();
               $file->id = $this->upload_id = $upload_id;*/
      return $file;
    }
  }
