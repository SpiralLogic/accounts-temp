<?php
  /**
   * PHP version 5.4
   * @category  PHPw
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use ADV\Core\HTML;
  use ADV\Core\Dialog;
  use ADV\Core\JS;

  /** **/
  class UI
  {
    /**
     * @static
     *
     * @param bool                $id
     * @param array               $options
     * @param array               $params
     * @param null                $selected
     * @param \ADV\Core\HTML|bool $return
     *
     * @return \ADV\Core\HTML|null
     */
    public static function select($id = false, $options = [], $params = [], $selected = null, HTML $return = null) {
      if ($return instanceof HTML) {
        $HTML = $return;
      } else {
        $HTML = new HTML();
      }
      $HTML->select($id, $params);
      foreach ((array) $options as $value => $name) {
        if (is_array($name)) {
          $HTML->optgroup(array('label' => $value));
          foreach ($name as $value) {
            $selected = ($selected == $value[1]) ? null : true;
            $HTML->option(null, $value[0] . ' (' . $value[1] . ')', array('selected' => $selected, 'value' => $value[1]), false);
          }
          $HTML->optgroup();
        } else {
          $selected = ($selected === $value) ? null : true;
          $HTML->option(null, $name, array('value' => $value, 'selected' => $selected), false);
        }
      }
      $select = $HTML->_select();
      if ($return) {
        return $select;
      }
      echo $select;
      return $HTML;
    }
    /***
     * @static
     *
     * @param              $id
     * @param array        $options
     * @param bool         $return
     * @param \ADV\Core\JS $js
     *
     * @internal param array $attr includes (url,label,size,name,set,focus, nodiv, callback, options
     * @return HTML|null
     *           url: url to get search results from<br>
     *           label: if set becomes the text of a &lt;label&gt; element for the input<br>
     *           size: size of the input<br>
     *           focus: whether to start with focus<br>
     *           nodiv: if true then a div is not included<br>
     *           callback: name of the javascript function to be the callback for the results, refaults to the same name as the id with camel case<br>
     *           options: Javascript function autocomplete options<br>
     */
    public static function search($id, $options = [], $return = false, JS $js = null) {
      $js   = $js ? : JS::i();
      $o    = array(
        'url'               => false, //
        'nodiv'             => false, //
        'label'             => false, //
        'name'              => null, //
        'set'               => null, //
        'class'             => 'width95 ', //
        'value'             => null, //
        'focus'             => null, //
        'idField'           => null, //
        'data'              => [], //
        'callback'          => false, //
        'cells'             => false, //
        'cell_class'        => null, //
        'placeholder'       => null, //
        'input_cell_params' => [],
        'label_cell_params' => ['class' > 'label pointer']
      );
      $o    = array_merge($o, $options);
      $url  = $o['url'] ? : false;
      $HTML = new HTML;
      if (!$o['nodiv']) {
        $HTML->div(['class' => 'ui-widget ']);
      }
      if ($o['cells']) {
        $HTML->td(null, $o['label_cell_params']);
      }
      if (($o['label'])) {
        $HTML->label(null, $o['label'], ['for' => $id], false);
      }
      if ($o['cells']) {
        $HTML->_td();
      }
      $input_attr = [
        'class'       => $o['class'], //
        'name'        => $o['name'], //
        'data-set'    => $o['set'], //
        'value'       => htmlentities($o['value']), //
        'autofocus'   => $o['focus'], //
        'type'        => 'search', //
        'placeholder' => $o['placeholder'] ? : $o['label'],
      ];
      if ($o['cells']) {
        $HTML->td(null, $o['input_cell_params']);
      }
      $HTML->input($id, $input_attr);
      if ($o['cells']) {
        $HTML->_td();
      }
      if (!($o['nodiv'])) {
        $HTML->div();
      }
      $callback = $o['callback'] ? : '"' . $o['idField'] . '"';
      $js->autocomplete($id, $callback, $url, $o['data']);
      $search = $HTML->__toString();
      if ($return) {
        return $search;
      }
      echo $search;
      return $HTML;
    }
    /**
     * @static
     *
     * @param        $id
     * @param string $url
     * @param array  $options 'description' => false,<br>
    'disabled' => false,<br>
    'editable' => true,<br>
    'selected' => '',<br>
    'label' => false,<br>
    'cells' => false,<br>
    'inactive' => false,<br>
    'purchase' => false,<br>
    'sale' => false,<br>
    'js' => '',<br>
    'selectjs' => '',<br>
    'submitonselect' => '',<br>
    'sales_type' => 1,<br>
    'no_sale' => false,<br>
    'select' => false,<br>
    'type' => 'local',<br>
    'kits'=>true,<br>
    'where' => '',<br>
    'size'=>'20px'<br>
     *
     * @return HTML|string
     */
    public static function searchLine($id, $url = '#', $options = []) {
      $defaults                      = array(
        'description'       => false,
        'disabled'          => false,
        'editable'          => true,
        'selected'          => '',
        'label'             => null,
        'placeholder'       => 'Item',
        'cells'             => false,
        'class'             => 'med',
        'inactive'          => false,
        'purchase'          => false,
        'sale'              => false,
        'js'                => '',
        'selectjs'          => '',
        'submitonselect'    => '',
        'sales_type'        => 1,
        'no_sale'           => false,
        'select'            => false,
        'type'              => 'local',
        'kitsonly'          => false,
        'where'             => '',
        'size'              => null,
        'cell_class'        => false,
        'input_cell_params' => null
      );
      $o                             = array_merge($defaults, $options);
      $UniqueID                      = md5(serialize($o));
      $_SESSION['search'][$UniqueID] = $o;
      $desc_js                       = $o['js'];
      $HTML                          = new HTML;
      if ($o['cells']) {
        $HTML->td(null, array('class' => 'label'));
      }
      if ($o['label']) {
        $HTML->label(null, $o['label'], array('for' => $id), false);
      }
      $HTML->input(
        $id, array(
                  'value'       => $o['selected'],
                  'placeholder' => $o['placeholder'],
                  'name'        => $id,
                  'class'       => $o['class'],
                  'size'        => $o['size']
             )
      );
      if ($o['editable']) {
        $HTML->label(
          'lineedit', 'edit', array(
                                   'for'   => $id,
                                   'class' => 'stock button',
                                   'style' => 'display:none'
                              ), false
        );
        $desc_js .= '$("#lineedit").data("stock_id",value.stock_id).show().parent().css("white-space","nowrap"); ';
      }
      if ($o['cells']) {
        $HTML->td()->td(true);
      }
      $selectjs = '';
      if ($o['selectjs']) {
        $selectjs = $o['selectjs'];
      } elseif ($o['description'] !== false) {
        $HTML->textarea(
          'description', $o['description'], array(
                                                 'name'  => 'description',
                                                 'rows'  => 1,
                                                 'class' => 'width90'
                                            ), false
        );
        $desc_js .= "$('#description').css('height','auto').attr('rows',4);";
      } elseif ($o['submitonselect']) {
        $selectjs
          = <<<JS
                $(this).val(value.stock_id);
                $('form').trigger('submit'); return false;
JS;
      } else {
        $selectjs
          = <<<JS
                $(this).val(value.stock_id);return false;
JS;
      }
      if ($o['cells']) {
        $HTML->td();
      }
      $js
             = <<<JS
    try { if (Adv.o.stock_id&&Adv.o.stock_id.attr('type')!=='hidden'){Adv.o.stock_id.catcomplete('destroy');}}catch(e){console.log(e)}
    Adv.o.stock_id = \$$id = $("#$id");
    if (\$$id.attr('type')!=='hidden'){
    \$$id.catcomplete({
                delay: 0,
                autoFocus: true,
                minLength: 1,
                source: function( request, response ) {
                        if (Adv.lastXhr) {Adv.lastXhr.abort();}
                        Adv.loader.off();
                        Adv.lastXhr = $.ajax({
                                url: "$url",
                                dataType: "json",
                                data: {UniqueID: '{$UniqueID}',term: request.term},
                                success: function( data,status,xhr ) {
                                if ( xhr === Adv.lastXhr ) {
                                if (!Adv.o.stock_id.data('active')) {
                                var value = data[0];
                             Adv.Forms.setFocus("stock_id",true);
                                $.each(value,function(k,v) {Adv.Forms.setFormValue(k,v);});
                                    $desc_js
                                        return false;
                                }
                                        response($.map( data, function( item ) {
                                                return {
                                                        label: item.stock_id+": "+item.item_name,
                                                        value: item,
                                                        category: item.category
                                                }
                                        }));
                                        Adv.loader.on();
                                }}})
                        },
                     select: function( event, ui ) {
 var value = ui.item.value;
 $selectjs
                                 Adv.Forms.setFocus("description",true);
                                $.each(value,function(k,v) {Adv.Forms.setFormValue(k,v);});
                                    $desc_js

                                return false;
                        },
                        focus: function(){return false;},
                        open: function() { $('.ui-autocomplete').unbind('mouseover');}
                        }
                ).blur(function() { $(this).data('active',false)})
                .focus(function() { $(this).data('active',true)})
                .on('paste',function() {var \$this=$(this);window.setTimeout(function(){\$this.catcomplete('search', \$this.val())},1)});
                }
JS;
        JS::_addLive($js);
        return $HTML->__toString();
    }
    /**
     * @static
     *
     * @param              $contactType
     * @param \ADV\Core\JS $js
     *
     * @return mixed
     */
    public static function emailDialogue($contactType, JS $js = null) {
      static $loaded = false;
      $js = $js ? : JS::i();
      if ($loaded == true) {
        return;
      }
      $emailBox = new Dialog('Select Email Address:', 'emailBox', '');
      $emailBox->setJsObject($js);
      $emailBox->addButtons(array('Close' => '$(this).dialog("close");'));
      $emailBox->setOptions(['modal' => true, 'width' => 500, 'height' => 350, 'resizeable' => false]);
      $emailBox->show();
      $action
        = <<<JS
     var emailID= $(this).data('emailid');
     $.post('/contacts/emails.php',{type: $contactType, id: emailID}, function(data) {
     Adv.o.\$emailBox.html(data).dialog('open');
     },'html');
     return false;
JS;
      $js->addLiveEvent('.email-button', 'click', $action, 'wrapper', true);
      $loaded = true;
    }
    public static function lineSortable() {
      $js
        = <<<JS
        var grid = $('.grid');
grid.find('tbody').sortable({
  items:'tr:not(.newline,.editline)',
  delay:300,
  stop:function () {
    var self = $(this)//
     , _this = self.find('tr:not(".newline,.editline")')//
      , lines = {};
    self.sortable('disable');
    $.each(_this, function (k) {
      lines[$(this).data('line')] = k;
      if (k == _this.length - 1) {
        $.post('#', {lineMap:lines, _action:'setLineOrder', order_id:$("#order_id").val()},
          function () {
            $.each(_this, function () {
              var that = $(this)//
               , curline = that.data('line')//
               , buttons = that.find('#_action');
              that.data('line', lines[curline]);
              if (that.hasClass('editline')) {
                that.find('#LineNo').attr('value', lines[curline]).val(lines[curline])
              }
              $.each(buttons, function () {
                var curbutton = $(this)//
                 , curvalue = curbutton.val()//
                 , newvalue = curvalue.replace(curline, lines[curline]);
                curbutton.attr('value', newvalue).val(newvalue);
              });
            });
            self.sortable('enable');
          }, 'json');
      }
    });
  },
  helper:function (e, ui) {
    ui.children().each(function () {
      $(this).width($(this).width());
    });
    return ui;
  }}).find('tr:not(.newline,.editline)');
grid.find('.newline').droppable({drop:function (event, ui) {
  var infields = $(this).find('td');
  $(ui.draggable).find('td').each(function (k, v) {
    var currfield = infields.eq(k),currvalue=$(v).text();
    currfield.find('input').val(currvalue).end().find('textarea').text(currvalue).attr('rows',4);
    currfield.not(':has(input),:has(textarea),:has(button)').text(currvalue)})}})
JS;
      JS::_addLive($js);
    }
  }
