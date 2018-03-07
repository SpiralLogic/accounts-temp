<?php
  use ADV\Core\Barcode;
  use ADV\App\Page;
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  $file = ROOT_DOC . 'tmp/test.csv';
  if (!isset($_SESSION['barcodefile'])) {
    $_SESSION['barcodefile'] = '1';
  }
  if (!isset($_POST['unique'])) {
    $_POST['unique'] = '0';
  }
  if ($_SESSION['barcodefile'] != $_POST['unique']) {
    Page::start(_($help_context = "Barcode Generator"), SA_INVENTORYLOCATION);
    $id                      = uniqid();
    $_SESSION['barcodefile'] = $id;
    echo "<form method='post' enctype='multipart/form-data' target='_blank'  action='#'><div class='center'><input
        type='hidden'  name='go' value=1 /><input
        type='hidden' name='unique' value='$id' /> <input type='file' name='csvitems' autofocus/><button>Go</button></div></form>";
    Page::end();
  } else {
    unset($_SESSION['barcodefile']);
    if (file_exists($file)) {
      unlink($file);
    }
    if (move_uploaded_file($_FILES['csvitems']['tmp_name'], $file)) {
      $status = true;
    } else {
      $status = false;
      echo "fucked the job!\n<br>Couldn't move the uploaded file check permissions";
      Page::footer_exit();
    }
    ini_set('auto_detect_line_endings', 1);
    echo '<html><head><style>
            body,table,img,td,div {
                margin:0;padding:0;border-width: 0;
            }
            table {
                border-collapse: collapse;
            }
            td {
                height:68pt;
                max-height:68pt;
                min-height:68pt;
                overflow:hidden;
                font-size:8pt;
                text-align:left;
                background-color:rgba(0,0,0,.1);
            }
            td.barcode {
                text-align:center;
                width:68pt;
                min-width:68pt;
                max-width:68pt;
            }
            td.space{
                width:4pt;
            }
            td.desc {
                width:109.5pt;
                min-width:109.5pt;
                max-width:109.5pt;
            }
            td.desc span {
                font-weight: bold;
                font-size: larger;
            }
            div  {
                left:-10pt;
                padding-top:11pt;
            }
            table{
                text-align:left;
                vertical-align: middle;
            }
            </style></head><body>';
    $csvitems = [];
    $file     = fopen($file, 'r');
    $result   = DB::_select('s.stock_id', 's.description')->from('stock_master s');
    while (($item = fgetcsv($file, 1000, ',')) !== false) {
      $result->orWhere("s.stock_id LIKE ", '%' . $item[0] . '%');
      $csvitems[strtolower($item[0])] = $item[1];
    }
    $result = $result->fetch()->all();
    $i      = 0;
    $j      = 0;
    $count  = 1;
    echo '<div class="page-break"><table ><tbody><tr>';
    while ($item = array_pop($result)) {
      if ($count < $csvitems[strtolower($item['stock_id'])]) {
        array_push($result, $item);
        $count++;
      } else {
        $count = 1;
      }
      $data  = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
      $image = base64_encode($data);
      echo '<td class="barcode"><IMG SRC="data:image/gif;base64,
        ' . $image . '">' . '</td><td class="desc"><span>' . $item['stock_id'] . '</span><br> ' . $item['description'] . '</td>';
      if ($i == 2) {
        $i = 0;
        if ($j == 10) {
          echo '</tr></table></div><div><table><tr>';
          $j = 0;
          continue;
        } else {
          echo '</tr><tr>';
          $j++;
        }
      } else {
        echo '<td class="space"></td>';
        $i++;
      }
    }
    echo '</table></div></body><script type="text/javascript">
    function breakeveryheader()
    {
    for (i=0; i<document.getElementsByTagName("div").length; i++) {
    document.getElementsByTagName("div")[i].style.pageBreakBefore="always";}
    }
breakeveryheader();
    </script></html>';
  }

