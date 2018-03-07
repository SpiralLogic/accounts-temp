<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 27/08/12
   * Time: 8:56 PM
   * To change this template use File | Settings | File Templates.
   */
  use ADV\Core\DB\DB;
  use ADV\Core\JS;

  if (REQUEST_POST) {
    if ($_POST['save']) {
      try {
        DB::_insert('help_texts')->value('page', $_POST['page'])->value('element', $_POST['element'])->value('text', $_POST['text'])->value('title', $_POST['title'])->exec();
      } catch (\ADV\Core\DB\DBDuplicateException $e) {
        DB::_update('help_texts')->value('text', $_POST['text'])->value('title', $_POST['title'])->where('page=', $_POST['page'])->andWhere('element=', $_POST['element'])->exec();
      }
    }
    $data = DB::_select('text,title')->from('help_texts')->where('page=', $_POST['page'])->andWhere('element=', $_POST['element'])->fetch()->one();
    JS::_renderJSON($data);
  }
