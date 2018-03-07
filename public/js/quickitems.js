console.profile();
/**
 * User: Eli Sklar
 * Date: 17/07/11 - 10:58 PM
 */
var Items = function () {
  var item, //
    $itemsearch = $('#itemSearchId'), //
    $webFrame = $('#webFrame'), //
    $selects = $('select'), //
    $Items = $("#Items").show(), //
    $Accounts = $("#Accounts"), //
    $stockRow = $("#stockRow"),//
    $stockLevels = $("#stockLevels");
  $Items.template('items');
  $Accounts.template('accounts');
  $stockRow.template('stockrow');
  Adv.TabMenu.defer('itemedit').done(function () {
    Items.tabs = Adv.o.tabs['itemedit'];
    Items.tabs.delegate("input,textarea,select", "change keyup", function () {
      Adv.Forms.stateModified($(this));
      if (Items.form.fieldsChanged > 0) {
        Items.btnNew.show();
        Items.btnCancel.show();
        Items.btnConfirm.show();
      }
      else {
        if (Items.form.fieldsChanged === 0) {
          Items.btnConfirm.show();
          Items.btnCancel.show();
          Items.btnNew.show();
        }
      }
      Items.set(this.name, this.value);
    });
  });
  return {
    tabs:        null,
    form:        document.getElementById('item_form'),
    fetch:       function (id) {
      if (id.value !== undefined) {
        $itemsearch.val(id.value);
        Items.getFrames(id.value);
      }
      else {
        $itemsearch.val('');
        Items.getFrames(0);
      }
      if (id.id !== undefined) {
        id = id.id;
      }
      $.post("/Items/Manage/Items", {id: id}, function (data) {
        Items.onload(data, true);
      }, 'json');
      return false;
    },
    getFrames:   function (id) {
      var disabledTabs = [];
      if (!id) {
        disabledTabs = [2, 3, 4, 5];
        $stockLevels.hide();
      }
      if (Items.tabs) {
        id || Items.tabs.tabs('option', 'active', 0);
        Items.tabs.tabs('option', 'disabled', disabledTabs);
        return;
      }
      Adv.TabMenu.defer('itemedit').done(function () {Items.tabs.tabs('option', 'disabled', disabledTabs)});
    },
    set:         function (fieldname, val) {
      item[fieldname] = val;
    },
    onload:      function (data, noframes) {
      var form;
      if (!noframes) {
        this.getFrames(data.item.stock_id);
      }
      $Items.empty();
      $Accounts.empty();
      item = data.item;
      $.tmpl('items', data.item).appendTo("#Items");
      $.tmpl('accounts', data.item).appendTo("#Accounts");
      $('#prices_table').replaceWith(data.sellprices);
      $('#purchasing_table').replaceWith(data.buyprices);
      $('#reorder_table').replaceWith(data.reorderlevels);
      if (data.stockLevels) {
        $stockLevels.show().find('tbody').html($.tmpl('stockrow', data.stockLevels));
      }
      form = data._form_id;
      $.each(item, function (i, data) {
        Adv.Forms.setFormDefault(i, data, 'item_form');
      });
      Adv.Forms.setFocus('stock_id');
      Adv.Forms.resetHighlights(Items.form);
    },
    get:         function () {
      return item;
    },
    save:        function () {
      var action = item;
      item['_action'] = 'Save';
      $.post('/Items/Manage/Items', item, function (data) {
        if (data.success && data.success.success) {
          Items.onload(data);
        }
      }, 'json');
    },
    revertState: function () {
      Items.form.reset();
      Items.btnConfirm.hide();
      Items.btnCancel.hide();
      Items.btnNew.show();
      Adv.Forms.resetHighlights(Items.form);
      $("#itemSearchId").val('');
    },
    resetState:  function () {
      $(Items.form).find("#tabs0 input, #tabs0 textarea").empty();
      Items.fetch(0);
      Items.btnCancel.hide();
      Items.btnConfirm.hide();
      Items.btnNew.show();
    },
    btnCancel:   $("#btnCancel").mousedown(function () {
      Items.revertState();
      return false;
    }),
    btnConfirm:  $("#btnConfirm").click(function () {
      Items.save();
      return false;
    }),
    btnNew:      $("#btnNew").mousedown(function () {
      Items.resetState();
      return false;
    })
  };
}();
console.profileEnd();
