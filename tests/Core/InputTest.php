<?php
  namespace ADV\Core\Input;

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-05-17 at 14:37:31.
   */
  class InputTest extends \PHPUnit_Framework_TestCase {
    /** @var Input **/
    protected $object;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
      $_GET     = [];
      $_POST    = [];
      $_REQUEST = [];
      $_SESSION = [];
      $this->object = new Input();
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }
    /**
     * @covers ADV\Core\Input\Input::post
     */
    public function testPost() {
      $_POST['test0'] = 'wawa';
      $expected       = 'wawa';
      $actual         = $this->object->post('test0');
      $this->assertSame($expected, $actual);
      $_POST['test0'] = '0';
      $expected       = '0';
      $actual         = $this->object->post('test0');
      $this->assertSame($expected, $actual);
      $_POST['test0'] = '0';
      $expected       = '';
      $actual         = $this->object->post('name', Input::STRING);
      $this->assertSame($expected, $actual);
      $_POST['name'] = null;
      $expected      = 'phil';
      $actual        = $this->object->post('name', Input::STRING, 'phil');
      $this->assertSame($expected, $actual);
      $_POST['test'] = 'ing';
      $this->assertSame('ing', $this->object->post('test'));
      $_POST['test'] = 'ing';
      $this->assertSame('ing', $this->object->post('test', Input::STRING));
      $_POST['test'] = 'ing';
      $this->assertSame('ing', $this->object->post('test', Input::STRING, ''));
      $_POST['test'] = 'ing';
      $this->assertSame(0, $this->object->post('test', Input::NUMERIC));
      $_POST['test'] = 'ing';
      $this->assertSame(0, $this->object->post('test', Input::NUMERIC, 0));
      $_POST['test'] = 'ing';
      $this->assertSame(1, $this->object->post('test', Input::NUMERIC, 1));
      $_POST['test'] = 'ing';
      $this->assertSame(null, $this->object->post('test2'));
      unset($_POST['test2']);
      $this->assertEquals('', $this->object->post('test2'));
      unset($_POST['test2']);
      $this->assertSame('', $this->object->post('test2', Input::STRING));
      unset($_POST['test2']);
      $this->assertSame(0, $this->object->post('test2', Input::NUMERIC));
      unset($_POST['test2']);
      $this->assertSame(5, $this->object->post('test2', Input::NUMERIC, 5));
      $_POST['test2'] = '0';
      $this->assertSame('0', $this->object->post('test2'));
      $_POST['test2'] = '0';
      $this->assertSame('0', $this->object->post('test2', Input::STRING));
      $_POST['test2'] = '0';
      $this->assertSame('0', $this->object->post('test2', Input::STRING, ''));
      $_POST['test2'] = '0';
      $this->assertSame(0, $this->object->post('test2', Input::NUMERIC));
      $_POST['test2'] = '0';
      $this->assertSame(0, $this->object->post('test2', Input::NUMERIC, 0));
      $_POST['test2'] = '0';
      $this->assertSame(0, $this->object->post('test2', Input::NUMERIC, 1));
      unset($_POST['test3']);
      $this->assertSame(null, $this->object->post('test3'));
      unset($_POST['test3']);
      $this->assertEquals(0, $this->object->post('test3'));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3'));
      $_POST['test3'] = 7;
      $this->assertSame('', $this->object->post('test3', Input::STRING));
      $_POST['test3'] = 7;
      $this->assertSame('', $this->object->post('test3', Input::STRING, ''));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC, 0));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC, 1));
    }
    /**
     * @covers ADV\Core\Input\Input::get
     * @todo   Implement testGet().
     */
    public function testGet() {
      $expected = null;
      $actual   = $this->object->get('test0');
      $this->assertSame($expected, $actual);
      $actual = $this->object->get('test0', null);
      $this->assertSame($expected, $actual);
      $expected = false;
      $actual   = $this->object->get('test0', null, false);
      $this->assertSame($expected, $actual);
      $_GET['test0'] = 'wawa';
      $expected      = 'wawa';
      $actual        = $this->object->get('test0');
      $this->assertSame($expected, $actual);
      $_GET['test0'] = '0';
      $expected      = '0';
      $actual        = $this->object->get('test0');
      $this->assertSame($expected, $actual);
      $expected = '';
      $actual   = $this->object->get('name', Input::STRING);
      $this->assertSame($expected, $actual);
      $expected = 'phil';
      unset($_GET['name']);
      $actual = $this->object->get('name', Input::STRING, 'phil');
      $this->assertSame($expected, $actual);
      $_GET['test'] = 'ing';
      $this->assertSame('ing', $this->object->get('test'));
      $_GET['test'] = 'ing';
      $this->assertSame('ing', $this->object->get('test', Input::STRING));
      $_GET['test'] = 'ing';
      $this->assertSame('ing', $this->object->get('test', Input::STRING, ''));
      $_GET['test'] = 'ing';
      $this->assertSame(0, $this->object->get('test', Input::NUMERIC));
      $_GET['test'] = 'ing';
      $this->assertSame(0, $this->object->get('test', Input::NUMERIC, 0));
      $_GET['test'] = 'ing';
      $this->assertSame(1, $this->object->get('test', Input::NUMERIC, 1));
      $_GET['test'] = 'ing';
      $this->assertSame(null, $this->object->get('test2'));
      unset($_GET['test2']);
      $this->assertEquals('', $this->object->get('test2'));
      unset($_GET['test2']);
      $this->assertSame('', $this->object->get('test2', Input::STRING));
      unset($_GET['test2']);
      $this->assertSame(0, $this->object->get('test2', Input::NUMERIC));
      unset($_GET['test2']);
      $this->assertSame(5, $this->object->get('test2', Input::NUMERIC, 5));
      $_GET['test2'] = '0';
      $this->assertSame('0', $this->object->get('test2'));
      $this->assertSame('0', $this->object->get('test2', Input::STRING));
      $this->assertSame('0', $this->object->get('test2', Input::STRING, ''));
      $this->assertSame(0, $this->object->get('test2', Input::NUMERIC));
      $this->assertSame(0, $this->object->get('test2', Input::NUMERIC, 0));
      $this->assertSame(0, $this->object->get('test2', Input::NUMERIC, 1));
      $this->assertSame(null, $this->object->get('test3'));
      $this->assertEquals(0, $this->object->get('test3'));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3'));
      $_GET['test3'] = 7;
      $this->assertSame('', $this->object->get('test3', Input::STRING));
      $_GET['test3'] = 7;
      $this->assertSame('', $this->object->get('test3', Input::STRING, ''));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC, 0));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC, 1));
    }
    /**
     * @covers ADV\Core\Input\Input::request
     * @todo   Implement testRequest().
     */
    public function testRequest() {
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3'));
      $_GET['test3'] = 7;
      $this->assertSame('', $this->object->get('test3', Input::STRING));
      $_GET['test3'] = 7;
      $this->assertSame('', $this->object->get('test3', Input::STRING, ''));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC, 0));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC, 1));
      $_GET['test3']  = 7;
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3'));
      $_POST['test3'] = 7;
      $this->assertSame('', $this->object->post('test3', Input::STRING));
      $_POST['test3'] = 7;
      $this->assertSame('', $this->object->post('test3', Input::STRING, ''));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC, 0));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC, 1));
      $_POST['test3'] = 7;
    }
    /**
     * @covers ADV\Core\Input\Input::getPost
     * @todo   Implement testgetPost().
     */
    public function testGetPost() {
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->getPost('test3'));
      $_POST['test3'] = 8;
      $this->assertSame(7, $this->object->getPost('test3'));
      unset($_GET['test3']);
      $this->assertSame(8, $this->object->getPost('test3'));
    }
    /**
     * @covers ADV\Core\Input\Input::firstThenSecond
     */
    public function testFirstThenSecond() {
      $class  = new \ReflectionClass('ADV\\Core\\Input\\Input');
      $method = $class->getMethod('firstThenSecond');
      $method->setAccessible(true);
      $actual = $method->invokeArgs($this->object, [Input::$get, Input::$post, 'test3', null, false]);
      $this->assertfalse($actual);
      $expected       = 7;
      $_GET['test3']  = 7;
      $_POST['test3'] = 8;
      $actual         = $method->invokeArgs($this->object, [Input::$get, Input::$post, 'test3']);
      $this->assertSame($expected, $actual);
      $expected = 8;
      $actual   = $method->invokeArgs($this->object, [Input::$post, Input::$get, 'test3']);
      $this->assertSame($expected, $actual);
      $expected = 7;
      unset($_POST['test3']);
      $actual = $method->invokeArgs($this->object, [Input::$post, Input::$get, 'test3']);
      $this->assertSame($expected, $actual);
      unset($_GET['test3']);
      $actual = $method->invokeArgs($this->object, [Input::$post, Input::$get, 'test3']);
      $this->assertNull($actual);
      $actual = $method->invokeArgs($this->object, [Input::$post, Input::$get, 'test3', null, false]);
      $this->assertFalse($actual);
    }
    /**
     * @covers ADV\Core\Input\Input::getPostGlobal
     * @todo   Implement testgetPostGlobal().
     */
    public function testgetPostGlobal() {
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->getPostGlobal('test3'));
      $_POST['test3'] = 8;
      $this->assertSame(7, $this->object->getPostGlobal('test3'));
      unset($_GET['test3']);
      unset($_POST['test3']);
      $_SESSION['_globals']['test3'] = 9;
      $this->assertSame(9, $this->object->getPostGlobal('test3'));
    }
    /**
     * @covers ADV\Core\Input\Input::postGlobal
     * @todo   Implement testpostGlobal().
     */
    public function testpostGlobal() {
      $_GET['test3'] = 7;
      $this->assertSame(null, $this->object->postGlobal('test3'));
      $_POST['test3'] = 8;
      $this->assertSame(8, $this->object->postGlobal('test3'));
      unset($_GET['test3']);
      unset($_POST['test3']);
      $_SESSION['_globals']['test3'] = 9;
      $this->assertSame(9, $this->object->postGlobal('test3'));
      $_POST['test4']                = 'right';
      $_SESSION['_globals']['test4'] = 'wrong';
      $actual                        = $this->object->postGlobal('test4', null, 'wrong');
      $this->assertSame('right', $actual);
      $_SESSION['_globals']['test5'] = 'wrong';
      $actual                        = $this->object->postGlobal('test5', null, 'wrong2');
      $this->assertSame('wrong', $actual);
      $actual = $this->object->postGlobal('test6', null, 'usethisone');
      $this->assertSame('usethisone', $actual);
    }
    /**
     * @covers ADV\Core\Input\Input::postGet
     * @todo   Implement testpostGet().
     */
    public function testpostGet() {
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->postGet('test3'));
      $_POST['test3'] = 8;
      $this->assertSame(8, $this->object->postGet('test3'));
      unset($_GET['test3']);
      $this->assertSame(8, $this->object->postGet('test3'));
    }
    /**
     * @covers ADV\Core\Input\Input::session
     * @todo   Implement testSession().
     */
    public function testSession() {
      $_SESSION['test3'] = 7;
      $this->assertSame(7, $this->object->session('test3', Input::NUMERIC));
    }
    /**
     * @covers ADV\Core\Input\Input::hasPost
     * @todo   Implement testhasPost().
     */
    public function testhasPost() {
      $this->assertSame(false, $this->object->hasPost('test'));
      $this->assertSame(false, $this->object->hasPost('test', 'test2'));
      $_POST['test'] = false;
      $this->assertSame(false, $this->object->hasPost('test', 'test2'), 'Should return false even if one variable is set.');
      $this->assertSame(true, $this->object->hasPost('test'), 'Should return true if post variable is set to false because it exists');
      $_POST['test2'] = null;
      $this->assertSame(false, $this->object->hasPost('test2'), 'Test2 is set but is null so it should return false!');
      $this->assertSame(false, $this->object->hasPost('test', 'test2'), 'Both are set but test2 is set but is null so it should return false!');
      $_POST['test2'] = 'something';
      $this->assertSame(true, $this->object->hasPost('test', 'test2'), 'Both are set but test2 is set but is null so it should return false!');
    }
    /**
     * @covers ADV\Core\Input\Input::hasGet
     * @todo   Implement testhasGet().
     */
    public function testhasGet() {
      $_GET['test'] = false;
      $this->assertSame(true, $this->object->hasGet('test'));
      $this->assertSame(false, $this->object->hasGet('test', 'test2'));
    }
    /**
     * @covers ADV\Core\Input\Input::has
     * @todo   Implement testHas().
     */
    public function testHas() {
      $_REQUEST['test'] = false;
      $this->assertSame(true, $this->object->has('test'));
      $this->assertSame(false, $this->object->has('test', 'test2'));
    }
    /**
     * @covers ADV\Core\Input\Input::hasSession
     * @todo   Implement testhasSession().
     */
    public function testhasSession() {
      $_SESSION['test'] = false;
      $this->assertSame(true, $this->object->hasSession('test'));
      $this->assertSame(false, $this->object->hasSession('test', 'test2'));
    }
    public function testSetting() {
      Input::$post['test'] = 'wawa';
      $this->assertSame('wawa', $_POST['test']);
    }
    public function testGetting() {
      $_POST['test'] = 'wawa';
      $this->assertSame('wawa', Input::$post['test']);
    }
    public function testUnsetting() {
      Input::$post['test'] = 'wawa';
      unset(Input::$post['test']);
      $this->assertNotContains('test', $_POST);
    }
  }
