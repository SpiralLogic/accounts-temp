<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  use ADV\Core\DB\DB;
  use ADV\Core\Config;
  use ADV\App\Item\Item;
  use ADV\App\User;

  print_price_listing();
  /**
   * @param int $category
   *
   * @return null|PDOStatement
   */
  function fetch_items($category = 0) {
    $sql
      = "SELECT stock_master.stock_id, stock_master.description AS name,
                stock_master.material_cost+stock_master.labour_cost+stock_master.overhead_cost AS Standardcost,
                stock_master.category_id,
                stock_category.description
            FROM stock_master,
                stock_category
            WHERE stock_master.category_id=stock_category.category_id AND NOT stock_master.inactive";
    if ($category != 0) {
      $sql .= " AND stock_category.category_id = " . DB::_escape($category);
    }
    $sql
      .= " ORDER BY stock_master.category_id,
                stock_master.stock_id";
    return DB::_query($sql, "No transactions were returned");
  }

  /**
   * @param int $category
   *
   * @return null|PDOStatement
   */
  function get_kits($category = 0) {
    $sql
      = "SELECT i.item_code AS kit_code, i.description AS kit_name, c.category_id AS cat_id, c.description AS cat_name, count(*)>1 AS kit
            FROM
            item_codes i
            LEFT JOIN
            stock_category c
            ON i.category_id=c.category_id";
    $sql .= " WHERE !i.is_foreign AND i.item_code!=i.stock_id";
    if ($category != 0) {
      $sql .= " AND c.category_id = " . DB::_escape($category);
    }
    $sql .= " GROUP BY i.item_code";
    return DB::_query($sql, "No kits were returned");
  }

  function print_price_listing() {
    $currency    = $_POST['PARAM_0'];
    $category    = $_POST['PARAM_1'];
    $salestype   = $_POST['PARAM_2'];
    $pictures    = $_POST['PARAM_3'];
    $showGP      = $_POST['PARAM_4'];
    $comments    = $_POST['PARAM_5'];
    $destination = $_POST['PARAM_6'];
    if ($destination) {
      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {
      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    $dec       = User::_price_dec();
    $home_curr = DB_Company::_get_pref('curr_default');
    if ($currency == ALL_TEXT) {
      $currency = $home_curr;
    }
    $curr     = GL_Currency::get($currency);
    $curr_sel = $currency . " - " . $curr['currency'];
    if ($category == ALL_NUMERIC) {
      $category = 0;
    }
    if ($salestype == ALL_NUMERIC) {
      $salestype = 0;
    }
    if ($category == 0) {
      $cat = _('All');
    } else {
      $cat = Item_Category::get_name($category);
    }
    if ($salestype == 0) {
      $stype = _('All');
    } else {
      $stype = Sales_Type::get_name($salestype);
    }
    if ($showGP == 0) {
      $GP = _('No');
    } else {
      $GP = _('Yes');
    }
      $cols    = [0, 100, 385, 450, 515];
      $headers = [_('Category/Items'), _('Description'), _('Price'), _('GP %')];
      $aligns  = ['left', 'left', 'right', 'right'];
      $params  = [
          0 => $comments,
          1 => [
              'text' => _('Currency'),
        'from' => $curr_sel,
        'to'   => ''
          ],
          2 => [
              'text' => _('Category'),
        'from' => $cat,
        'to'   => ''
          ],
          3 => [
              'text' => _('Sales Type'),
        'from' => $stype,
        'to'   => ''
          ],
          4 => [
              'text' => _('Show GP %'),
        'from' => $GP,
        'to'   => ''
          ]
      ];
      /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Price Listing'), "PriceListing", SA_PRICEREP, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $result                 = fetch_items($category);
    $catgor                 = '';
    $_POST['sales_type_id'] = $salestype;
    while ($myrow = DB::_fetch($result)) {
      if ($catgor != $myrow['description']) {
        $rep->Line($rep->row - $rep->lineHeight);
        $rep->NewLine(2);
        $rep->fontSize += 2;
        $rep->TextCol(0, 3, $myrow['category_id'] . " - " . $myrow['description']);
        $catgor = $myrow['description'];
        $rep->fontSize -= 2;
        $rep->NewLine();
      }
      $rep->NewLine();
      $rep->TextCol(0, 1, $myrow['stock_id']);
      $rep->TextCol(1, 2, $myrow['name']);
      $price = Item_Price::get_calculated_price($myrow['stock_id'], $currency, $salestype);
      $rep->AmountCol(2, 3, $price, $dec);
      if ($showGP) {
        $price2 = Item_Price::get_calculated_price($myrow['stock_id'], $home_curr, $salestype);
        if ($price2 != 0.0) {
          $disp = ($price2 - $myrow['Standardcost']) * 100 / $price2;
        } else {
          $disp = 0.0;
        }
        $rep->TextCol(3, 4, Num::_format($disp, User::_percent_dec()) . " %");
      }
      if ($pictures) {
        $image = PATH_COMPANY . "images/" . Item::img_name($myrow['stock_id']) . ".jpg";
        if (file_exists($image)) {
          $rep->NewLine();
          if ($rep->row - Config::_get('item_images_height') < $rep->bottomMargin) {
            $rep->Header();
          }
          $rep->AddImage($image, $rep->cols[1], $rep->row - Config::_get('item_images_height'), 0, Config::_get('item_images_height'));
          $rep->row -= Config::_get('item_images_height');
          $rep->NewLine();
        }
      } else {
        $rep->NewLine(0, 1);
      }
    }
    $rep->Line($rep->row - 4);
    $result = get_kits($category);
    $catgor = '';
    while ($myrow = DB::_fetch($result)) {
      if ($catgor != $myrow['cat_name']) {
        if ($catgor == '') {
          $rep->NewLine(2);
          $rep->fontSize += 2;
          $rep->TextCol(0, 3, _("Sales Kits"));
          $rep->fontSize -= 2;
        }
        $rep->Line($rep->row - $rep->lineHeight);
        $rep->NewLine(2);
        $rep->fontSize += 2;
        $rep->TextCol(0, 3, $myrow['cat_id'] . " - " . $myrow['cat_name']);
        $catgor = $myrow['cat_name'];
        $rep->fontSize -= 2;
        $rep->NewLine();
      }
      $rep->NewLine();
      $rep->TextCol(0, 1, $myrow['kit_code']);
      $rep->TextCol(1, 2, $myrow['kit_name']);
      $price = Item_Price::get_kit($myrow['kit_code'], $currency, $salestype);
      $rep->AmountCol(2, 3, $price, $dec);
      $rep->NewLine(0, 1);
    }
    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
  }

