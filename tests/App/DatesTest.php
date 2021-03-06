<?php
  namespace ADV\App;

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-04-30 at 12:17:30.
   */
  class DatesTest extends \PHPUnit_Framework_TestCase {
    /** @var Dates **/
    protected $dates;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
      $company = $this->getMockBuilder('DB_Company')->disableOriginalConstructor()->getMock();
      $company->expects($this->any())->method('_get_current_fiscalyear')->will(
        $this->returnValue(
          [
          'begin'  => '01/07/2012',
          'end'    => '30/06/2013',
          'closed' => false
          ]
        )
      );
      $this->dates = new Dates($company);
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
      unset($this->dates);
    }
    public function testSetSep() {
      $this->dates->setSep(0);
      $this->assertAttributeEquals('/', 'sep', $this->dates);
    }
    /**
     * @covers  ADV\Core\Dates::_date
     * @depends testSetSep
     */
    public function testdate() {
      $class  = new \ReflectionClass('ADV\\App\\Dates');
      $method = $class->getMethod('date');
      $method->setAccessible(true);
      $expected = '01-13-2011';
      $actual   = $method->invokeArgs($this->dates, [2011, 1, 13, 0]);
      $this->assertEquals($expected, $actual);
      $expected = '13-01-2011';
      $actual   = $method->invokeArgs($this->dates, [2011, 1, 13, 1]);
      $this->assertEquals($expected, $actual);
      $expected = '2011-01-13';
      $actual   = $method->invokeArgs($this->dates, [2011, 1, 13, 2]);
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Dates::__isDate
     * @todo   Implement testisDate().
     */
    public function testisDate() {
      $this->dates->setSep(0);
      $date   = 'this is not a date';
      $result = $this->dates->isDate($date, 0);
      $this->assertFalse($result, 'this is not a date');

      $date   = 'this is not a date';
      $result = $this->dates->isDate($date, 0);
      $this->assertFalse($result, 'this is not a date');

      $date   = '12/20/2011';
      $result = $this->dates->isDate($date, 0);
      $this->assertEquals('12/20/2011', $result);


      $result = $this->dates->isDate($date, 1);
      $this->assertFalse($result);
      $this->dates->setSep(2);
      $date   = '20-12-2011';
      $result = $this->dates->isDate($date, 1);
      $this->assertEquals('20-12-2011', $result);
      $result = $this->dates->isDate($date, 0);
      $this->assertFalse($result);
      $this->dates->setSep(1);
      $date   = '2011.12.20';
      $result = $this->dates->isDate($date, 2);
      $this->assertEquals('2011.12.20', $result);
      $result = $this->dates->isDate($date, 0);
      $this->assertFalse($result);
      $date   = '130/13/11';
      $result = $this->dates->isDate($date, 0);
      $this->assertFalse($result);
    }
    /**
     * @covers ADV\Core\Dates::_dateToSql
     * @depnds testToday
     *     */
    public function testdateToSql() {
      $actual = $this->dates->dateToSql('04-03-2011');
      $this->assertEquals('2011-03-04', $actual);
      $actual = $this->dates->dateToSql('2012-03-05');
      $this->assertEquals('2012-03-05', $actual);
      $actual = $this->dates->dateToSql('06-03-2011');
      $this->assertEquals('2011-03-06', $actual);
      $expected = $this->dates->today(true);
      $actual   = $this->dates->dateToSql('');
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Dates::__now
     * @todo   Implement testNow().
     */
    public function testNow() {
      $this->assertEquals(date("H:i"), $this->dates->now());
    }
    /**
     * @covers ADV\Core\Dates::__today
     * @todo   Implement testToday().
     */
    public function testToday() {
      $today    = $this->dates->today();
      $expected = date('d-m-Y');
      $this->assertEquals($expected, $today);
      return $today;
    }
    /**
     * @covers  ADV\Core\Dates::__newDocDate
     */
    public function test_newDocDate() {
      $today = date('d-m-Y');
      $date  = $this->dates->newDocDate();
      $this->assertEquals($today, $date);
    }
    /**
     * @covers ADV\Core\Dates::_isDateInFiscalYear
     */
    public function test_isDateInFiscalYear() {
      $expected = true;
      $actual   = $this->dates->isDateInFiscalYear('01-02-2013');
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Dates::_beginFiscalYear
     * @todo   Implement testbeginFiscalYear().
     */
    public function testbeginFiscalYear() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::_endFiscalYear
     * @todo   Implement testendFiscalYear().
     */
    public function testendFiscalYear() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::_explode
     *     */
    public function testExplode() {
      $class  = new \ReflectionClass('ADV\\Core\\Dates');
      $method = $class->getMethod('explode');
      $method->setAccessible(true);
      $actual = $method->invokeArgs($this->dates, ['03-04-2012']);
      $this->assertEquals(['2012', '04', '03'], $actual);
      $actual = $method->invokeArgs($this->dates, ['04-03-2011']);
      $this->assertEquals(['2011', '03', '04'], $actual);
    }
    /**
     * @covers  ADV\Core\Dates::_beginMonth
     * @depends testdateToSql
     * @depends testExplode
     */
    public function testbeginMonth() {
      // Remove the following lines when you implement this test.
      $date     = $this->dates->beginMonth('04/03/2011');
      $expected = '01-03-2011';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->beginMonth('13/12/2011');
      $expected = '01-12-2011';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::_endMonth
     * @todo   Implement testendMonth().
     */
    public function testendMonth() {
      $date     = $this->dates->endMonth('04-04-2012');
      $expected = '30-04-2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->endMonth('13-12-2012');
      $expected = '31-12-2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->endMonth('2-2-2012');
      $expected = '29-02-2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::_addDays
     * @todo   Implement testaddDays().
     */
    public function testaddDays() {
      $date     = $this->dates->addDays('04-04-2012', 7);
      $expected = '11-04-2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->addDays('25-04-2012', 7);
      $expected = '02-05-2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->addDays('28-2-2012', 7);
      $expected = '06-03-2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->addDays('28-2-2012', -7);
      $expected = '21-02-2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::_addMonths
     * @todo   Implement testaddMonths().
     */
    public function testaddMonths() {
      // Remove the following lines when you implement this test.
      $date     = $this->dates->addMonths('04-04-2012', 4);
      $expected = '04-08-2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->addMonths('25-09-2012', 4);
      $expected = '25-01-2013';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->addMonths('25-09-2012', -4);
      $expected = '25-05-2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::_addYears
     * @todo   Implement testaddYears().
     */
    public function testaddYears() {
      $date     = $this->dates->addYears('04-04-2012', 4);
      $expected = '04-04-2016';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->addYears('25-09-2012', 4);
      $expected = '25-09-2016';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->addYears('25-09-2012', -4);
      $expected = '25-09-2008';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\$this->dates->sqlToDate
     * @todo   Implement testsqlToDate().
     */
    public function testsqlToDate() {
      $date   = '2012-10-01';
      $actual = $this->dates->sqlToDate($date);
      $this->assertEquals('01-10-2012', $actual);
      $date   = '';
      $actual = $this->dates->sqlToDate($date);
      $this->assertEquals('', $actual);
    }
    /**
     * @covers ADV\Core\Dates::_isGreaterThan
     * @todo   Implement testisGreaterThan().
     */
    public function testisGreaterThan() {
      $date1  = '01-01-2012';
      $date2  = '01-10-2012';
      $actual = $this->dates->isGreaterThan($date1, $date2);
      $this->assertEquals(false, $actual);
      $date1  = '01-01-2013';
      $date2  = '01-10-2010';
      $actual = $this->dates->isGreaterThan($date1, $date2);
      $this->assertEquals(true, $actual);
      $date1  = '01-01-2010';
      $date2  = '01-01-2010';
      $actual = $this->dates->isGreaterThan($date1, $date2);
      $this->assertEquals(true, $actual);
    }
    /**
     * @covers ADV\Core\Dates::_differenceBetween
     * @todo   Implement testdifferenceBetween().
     */
    public function testdifferenceBetween() {
      $date1  = '01-01-2012';
      $date2  = '15-01-2012';
      $actual = $this->dates->differenceBetween($date1, $date2, 'w');
      $this->assertEquals(-2, $actual);
      $date1  = '15-01-2012';
      $date2  = '01-01-2012';
      $actual = $this->dates->differenceBetween($date1, $date2, 'w');
      $this->assertEquals(2, $actual);
      $date1  = '02-01-2012';
      $date2  = '01-01-2012';
      $actual = $this->dates->differenceBetween($date1, $date2, 'd');
      $this->assertEquals(1, $actual);
      $date1  = '02-01-2012';
      $date2  = '01-01-2014';
      $actual = $this->dates->differenceBetween($date1, $date2, 'y');
      $this->assertEquals(-1, $actual);
      $date1  = '02-01-2012';
      $date2  = '01-01-2013';
      $actual = $this->dates->differenceBetween($date1, $date2, 'm');
      $this->assertEquals(-11, $actual);
    }
    /**
     * @covers ADV\Core\Dates::_div
     * @todo   Implement testDiv().
     */
    public function testDiv() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::_gregorianToJalai
     * @todo   Implement testgregorianToJalai().
     */
    public function testgregorianToJalai() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::_jalaiToGregorian
     * @todo   Implement testjalaiToGregorian().
     */
    public function testjalaiToGregorian() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::_months
     * @todo   Implement testMonths().
     */
    public function testMonths() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::_gregorianToIslamic
     * @todo   Implement testgregorianToIslamic().
     */
    public function testgregorianToIslamic() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::_islamicToGregorian
     * @todo   Implement testislamicToGregorian().
     */
    public function testislamicToGregorian() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::_getReadableTime
     * @todo   Implement testGetReadableTime().
     */
    public function testGetReadableTime() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
  }
