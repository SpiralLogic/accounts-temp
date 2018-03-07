<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use ADV\Core\DB\DB;

  /** **/
  class Tags
  {
    public static $types
      = array(
        TAG_ACCOUNT   => "Account", //
        TAG_DIMENSION => "Dimension"
      );
    /**
     * @static
     *
     * @param $type
     * @param $name
     * @param $description
     *
     * @return null|\PDOStatement
     */
    public static function add($type, $name, $description) {
      $sql
        = "INSERT INTO tags (type, name, description)
         VALUES (" . DB::_escape($type) . ", " . DB::_escape($name) . ", " . DB::_escape($description) . ")";
      return DB::_query($sql);
    }
    /**
     * @static
     *
     * @param      $id
     * @param      $name
     * @param      $description
     * @param null $type
     *
     * @return null|\PDOStatement
     */
    public static function update($id, $name, $description, $type = null) {
      $sql = "UPDATE tags SET name=" . DB::_escape($name) . ", description=" . DB::_escape($description);
      if ($type != null) {
        $sql .= ", type=" . DB::_escape($type);
      }
      $sql .= " WHERE id = " . DB::_escape($id);
      return DB::_query($sql);
    }
    /**
     * @static
     *
     * @param      $type
     * @param bool $all
     *
     * @return null|\PDOStatement
     */
    public static function getAll($type, $all = false) {
      $sql = "SELECT * FROM tags WHERE type=" . DB::_escape($type);
      if (!$all) {
        $sql .= " AND !inactive";
      }
      $sql .= " ORDER BY name";
      return DB::_query($sql, "could not get tags");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($id) {
      $sql    = "SELECT * FROM tags WHERE id = " . DB::_escape($id);
      $result = DB::_query($sql, "could not get tag");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_type($id) {
      $sql    = "SELECT type FROM tags WHERE id = " . DB::_escape($id);
      $result = DB::_query($sql, "could not get tag type");
      $row    = DB::_fetchRow($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_name($id) {
      $sql    = "SELECT name FROM tags WHERE id = " . DB::_escape($id);
      $result = DB::_query($sql, "could not get tag name");
      $row    = DB::_fetchRow($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_description($id) {
      $sql    = "SELECT description FROM tags WHERE id = " . DB::_escape($id);
      $result = DB::_query($sql, "could not get tag description");
      $row    = DB::_fetchRow($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id) {
      $sql = "DELETE FROM tags WHERE id = " . DB::_escape($id);
      DB::_query($sql, "could not delete tag");
    }
    /**
     * @static
     *
     * @param $recordid
     * @param $tagids
     */
    public static function add_associations($recordid, $tagids) {
      foreach ($tagids as $tagid) {
        if (!$tagid) {
          continue;
        }
        $sql
          = "INSERT INTO tag_associations (record_id, tag_id)
             VALUES (" . DB::_escape($recordid) . ", " . DB::_escape($tagid) . ")";
        DB::_query($sql, "could not add tag association");
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $recordid
     * @param $tagids
     */
    public static function update_associations($type, $recordid, $tagids) {
      // Delete the old associations
      Tags::delete_associations($type, $recordid, false);
      // Add the new associations
      Tags::add_associations($recordid, $tagids);
    }
    // To delete tag associations, we need to specify the tag type.
    // Otherwise we may inadvertantly delete records for another type of tag
    //
    /**
     * @static
     *
     * @param      $type
     * @param      $recordid
     * @param bool $all
     */
    public static function delete_associations($type, $recordid, $all = false) {
      /* multiply table DELETE syntax available since MySQL 4.0.0:
      $sql = "DELETE ta FROM ".''."tag_associations ta
            INNER JOIN ".''."tags tags ON tags.id = ta.tag_id
            WHERE tags.type = ".DB::_escape($type)." AND ta.record_id = ".DB::_escape($recordid);
    */
      // To support MySQL 3.xx we have to use multiply queries
      $sql
        = "SELECT * FROM tag_associations ta
             INNER JOIN tags tags ON tags.id = ta.tag_id
             WHERE tags.type = " . DB::_escape($type) . " AND ta.record_id = " . DB::_escape($recordid);
      if (!$all) {
        $sql .= " AND tags.inactive = 0";
      }
      $result = DB::_query($sql, "could not select tag associations");
      while ($ta = DB::_fetch($result)) {
        $sql2
          = "DELETE FROM tag_associations WHERE
             record_id = '" . $ta['record_id'] . "' AND tag_id=" . $ta['tag_id'];
        DB::_query($sql2, "could not delete tag associations");
      }
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return null|\PDOStatement
     */
    public static function get_associated_records($id) {
      // Which table we query is based on the tag type
      $type  = Tags::get_type($id);
      $table = $key = '';
      switch ($type) {
        case TAG_ACCOUNT:
          $table = "chart_master";
          $key   = "account_code";
          break;
        case TAG_DIMENSION:
          $table = "dimensions";
          $key   = "id";
          break;
      }
      $sql
        = "SELECT $table.* FROM $table
         INNER JOIN tag_associations AS ta ON ta.record_id = $table.$key
         INNER JOIN tags AS tags ON ta.tag_id = tags.id
      WHERE tags.id = " . DB::_escape($id);
      return DB::_query($sql, "could not get tag associations for tag");
    }
    /**
     * @static
     *
     * @param $type
     * @param $recordid
     *
     * @return null|\PDOStatement
     */
    public static function get_all_associated_with_record($type, $recordid) {
      $sql
        = "SELECT tags.* FROM tag_associations AS ta
                 INNER JOIN tags AS tags ON tags.id = ta.tag_id
                 WHERE tags.type = $type	AND ta.record_id = " . DB::_escape($recordid);
      return DB::_query($sql, "could not get tags associations for record");
    }
    /**
     * @static
     *
     * @param      $name
     * @param      $height
     * @param      $type
     * @param bool $multi
     * @param bool $all
     * @param bool $spec_opt
     *
     * @return string
     */
    public static function select($name, $height, $type, $multi = false, $all = false, $spec_opt = false) {
      // Get tags
      $results = Tags::getAll($type, $all);
      while ($tag = DB::_fetch($results)) {
        $tags[$tag['id']] = $tag['name'];
      }
      if (!isset($tags)) {
        $tags[''] = $all ? _("No tags defined.") : _("No active tags defined.");
        $spec_opt = false;
      }
      return Forms::arraySelect(
        $name,
        null,
        $tags,
        array(
             'multi'       => $multi,
             'height'      => $height,
             'spec_option' => $spec_opt,
             'spec_id'     => -1,
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param      $height
     * @param      $type
     * @param bool $mult
     * @param bool $all
     * @param bool $spec_opt
     */
    public static function cells($label, $name, $height, $type, $mult = false, $all = false, $spec_opt = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>\n";
      echo Tags::select($name, $height, $type, $mult, $all, $spec_opt);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param      $height
     * @param      $type
     * @param bool $mult
     * @param bool $all
     * @param bool $spec_opt
     */
    public static function row($label, $name, $height, $type, $mult = false, $all = false, $spec_opt = false) {
      echo "<tr><td class='label'>$label</td>";
      Tags::cells(null, $name, $height, $type, $mult, $all, $spec_opt);
      echo "</tr>\n";
    }
  }

