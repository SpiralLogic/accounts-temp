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

Adv.extend({Reconcile: {//
  group:       {}, //
  toChange:    {},//
  total:       0,//
  voidtrans:   false,//
  groupSelect: function (e, ui) {
    var target = $(this.obj_old).closest('tr')//
      , source = $(this.target_cell).closest('tr')//
      , data = {_action: 'deposit', trans1: source.data('id'), trans2: target.data('id')};
    return Adv.Reconcile.sendAction(data);
  },
  changeDate:  function (el) {
    var data = {_action: 'changeDate', trans_id: Adv.Reconcile.toChange.data('id')};
    $(el).find('[name="date"]').each(function () {
      data['date'] = $(this).val();
    });
    Adv.Reconcile.sendAction(data);
    $dateChanger.dialog('close');
  },
  changeBank:  function () {
    var data = {_action: 'changeBank', newbank: $('#changeBank').val(), type: Adv.Reconcile.toChange.data('type'), trans_no: Adv.Reconcile.toChange.data('transno')};
    Adv.Reconcile.sendAction(data);
    $(this).dialog('close')
  },
  createLink:  function () {
    var self = $(this)//
      , fee = ''//
      , url = self.attr('href')//
      , $row = $(this).closest('tr')//
      , date = $row.data('date')//
      , amount = $row.data('amount')//
      , memo = $row.find('.state_memo').text();
    if (self.data('fee')) {
      fee = '&fee=' + self.data('fee');
      amount = self.data('amount');
    }
    url = encodeURI(url + (url.indexOf('?')===-1?'&':'?')+'date=' + date + '&account=' + $('#bank_account').val() + '&amount=' + amount + fee + '&memo=' + memo);
    Adv.Reconcile.openLink(url);
    return false;
  },
  openLink:    function (url) {
    if (Adv.Reconcile.voidtrans && Adv.Reconcile.voidtrans.location) {
      Adv.Reconcile.voidtrans.location.href = url;
      Adv.Reconcile.voidtrans.focus();
    }
    else {
      Adv.Reconcile.voidtrans = window.open(url, '_blank');
    }
  },
  unGroup:     function () {
    return Adv.Reconcile.sendAction({_action: 'unGroup', groupid: $(this).closest('tr').data('id')});
  },
  sendAction:  function (data) {
    var overlay = $("<div id='loading' </div>").modal('show');
    $.post('#', data, function (data) {
      if (data.grid) {
        overlay.modal('hide');
        $("#_bank_rec_span").html($('#_bank_rec_span', data.grid).html());
        Adv.Reconcile.setUpGrid();
      }
    }, 'json');
    return false;
  },
  changeFlag:  function () {
    Adv.Scroll.set(this);
    JsHttpRequest.request('_' + $(this).attr('name') + '_update', this.form);
  },
  setUpGrid:   function () {
    // reference to the REDIPS.drag library and message line
    var rd = REDIPS.drag;
    // initialization
    rd.init();
    // set hover color for TD and TR
    // set hover border for current TD and TR
    rd.hover.color_tr = 'rgb(182, 255, 116)';
    // drop row after highlighted row (if row is dropped to other tables)
    // possible values are "before" and "after"
    rd.row_position = 'after';
    rd.myhandler_row_moved = function () {
      // set opacity for moved row
      // rd.obj is reference of cloned row (mini table)
      rd.row_opacity(rd.obj, 85);
      // set opacity for source row and change source row background color
      // obj.obj_old is reference of source row
      rd.row_opacity(rd.obj_old, 20, 'White');
      $('.cangroup').addClass('activeclass');
      $('.done,.deny').css('opacity', '.3');
      // display message
    };
    // row was dropped to the source - event handler
    // mini table (cloned row) will be removed and source row should return to original state
    rd.myhandler_row_dropped_source = function () {
      // make source row completely visible (no opacity)
      rd.row_opacity(rd.obj_old, 100);
      $('.done, .deny').css('opacity', '1');
      $('.cangroup').removeClass('activeclass');
      // display message
      return false;
    };
    rd.myhandler_row_dropped_before = function () {
      // return source row to its original state
      rd.row_opacity(rd.obj_old, 100);
      $('.cangroup').removeClass('activeclass');
      $('.done, .deny').css('opacity', '1');
      if ($(rd.target_cell).closest('tr').hasClass('cangroup')) {
        Adv.Reconcile.groupSelect.call(rd);
      }
      // cancel row drop
      return false;
    }
  }
}});
$(function () {
  $("#summary").draggable();
  Adv.o.wrapper.on('click', '.changeDate', function () {
    var $row = $(this).closest('tr');
    Adv.Reconcile.toChange = $row;
    Adv.o.dateChanger.render({id: $row.data('id'), date: $row.data('date')});
    $dateChanger.dialog('open').find('.datepicker').datepicker({dateFormat: 'dd/mm/yy'}).datepicker('show');
    return false;
  });
  Adv.o.wrapper.on('click', '.changeBank', function () {
    Adv.Reconcile.toChange = $(this).closest('tr');
    Adv.Forms.setFormValue('changeBank', $('#bank_account').val());
    $("#bankChanger").dialog('open');
    return false;
  });
  Adv.o.wrapper.on('click', '.voidTrans', function () {
    var $this = $(this).closest('tr')//
      , url = '/system/void_transaction?type=' + $this.data('type') + '&trans_no=' + $this.data('transno') + '&memo=Deleted%20during%20reconcile.';
    Adv.Reconcile.openLink(url);
    return false;
  });
  Adv.o.wrapper.on('click', '.unGroup', Adv.Reconcile.unGroup);
  Adv.o.wrapper.on('click', 'input[name^="rec_"]', Adv.Reconcile.changeFlag);
  Adv.o.wrapper.on('click', '[class^="create"]', Adv.Reconcile.createLink);
  var bankButtons = {'Cancel': function () {$(this).dialog('close');}, 'Save': Adv.Reconcile.changeBank};
  $("#bankChanger").dialog({autoOpen: false, modal: true, buttons: bankButtons});
});
"use strict";
// redips initialization
// add onload event listener
if (window.addEventListener) {
  window.addEventListener('load', Adv.Reconcile.setUpGrid, false);
}
else {
  if (window.attachEvent) {
    window.attachEvent('onload', Adv.Reconcile.setUpGrid);
  }
}
