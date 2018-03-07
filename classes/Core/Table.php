<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 9/05/12
   * Time: 2:42 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core;

  /** **/
  class Table
  {
    /**
     * @param string $class
     */
    public static function startOuter($class = "") {
      Table::start($class);
      echo "<tr class='top'><td>\n"; // outer table
    }
    /**
     * @param string $class
     */
    public static function start($class = "") {
      if ($class) {
        $class = "class='$class'";
      }
      echo "<div class='center'><table $class>";
    }
    /**
     * @param int    $number
     * @param bool   $width
     * @param string $class
     */
    public static function section($number = 1, $width = null, $class = '') {
      if ($number > 1) {
        $width = $width ? "width=$width" : "";
        echo "</table></td><td $width>"; // outer table
      }
      echo "\n<table  class='inner $class'>";
    }
    /**
     * @param        $msg
     * @param int    $colspan
     * @param string $class
     */
    public static function sectionTitle($msg, $colspan = 2, $class = 'tablehead') {
      echo "<tr class='$class'><td colspan=$colspan class='$class'>$msg</td></tr>";
    }
    /**
     * @param        $labels
     * @param string $params
     * @param string $extra
     */
    public static function header($labels, $params = '', $extra = '') {
      $header = '<thead>' . $extra . '<tr>';
      $labels = (array)$labels;
      foreach ($labels as $label) {
        $header .= "<th $params>$label</th>";
      }
      echo $header . '</tr></thead>';
    }
    /**
     * @param                 $label
     * @param                 $value
     * @param string          $label_attrs
     * @param string          $value_attrs
     * @param int|null|string $rightfill
     * @param null            $id
     *
     * @internal param string $params
     * @internal param string $params2
     * @internal param int $leftfill
     */
    public static function label($label, $value, $label_attrs = '', $value_attrs = '', $rightfill = 0, $id = null) {
      if (stripos($label_attrs, 'class') === false) {
        $label_attrs .= " class='label' ";
      }
      if (!$id) {
        $value_attrs .= " id='$id'";
        Ajax::_addUpdate($id, $id, $value);
      }
      $rightfill = ((int)$rightfill) ? "<td colspan=" . (int)$rightfill . "></td>" : '';
      echo "<tr><td $label_attrs>$label</td><td $value_attrs>$value</td>" . $rightfill . "</tr>";
    }
    /**
     * @static
     *
     * @param string $class
     */
    public static function foot($class = '') {
      if ($class) {
        $class = "class='$class'";
      }
      echo "<tfoot $class>";
    }
    public static function footEnd() {
      echo "</tfoot>";
    }
    /**
     * @param int $breaks
     */
    public static function end($breaks = 0) {
      echo "</table></div>" . str_repeat('<br>', $breaks);
    }
    /**
     * @param int  $breaks
     * @param bool $close_table
     */
    public static function endOuter($breaks = 0, $close_table = true) {
      if ($close_table) {
        echo "</table>\n";
      }
      echo "</td></tr>";
      Table::end($breaks);
    }
  }
