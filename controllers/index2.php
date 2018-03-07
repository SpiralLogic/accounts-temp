<?php
    use ADV\Core\Errors;
    use ADV\Core\JS;

    echo "<pre>";
    $test = new \ADV\App\User(1);
  $test->password = 'testING33';
  $test->save();
    var_dump($test->getStatus());
