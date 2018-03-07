console.profile();
(function (window, $, undefined) {
  var Contacts = {};
  (function () {
    var self = this, blank, count = 0, adding = false, $Contacts = $("#Contacts");
    $('#contact_tmpl').template('contact');
    this.list = function () {
      return list;
    };
    this.empty = function () {
      count = 0;
      adding = false;
      $Contacts.empty();
      return this;
    };
    this.init = function (data) {
      self.empty();
      self.addMany(data);
    };
    this.add = function (data) {
      self.addMany(data);
    };
    this.addMany = function (data) {
      var contacts = [];
      $.each(data, function ($k, $v) {
        if (!blank && $v.id === 0) {
          blank = $v;
        }
        $v._k = $k;
        contacts[contacts.length] = $v;
      });
      $.tmpl('contact', contacts).appendTo($Contacts);
    };
    this.setval = function (key, value) {
      key = key.split('-');
      if (value !== undefined) {
        Company.get().contacts[key[1]][key[0]] = value;
      }
    };
    this.New = function () {
      $.tmpl('contact', blank).appendTo($Contacts);
    }
  }).apply(Contacts);
  window.Contacts = Contacts
})(window, jQuery);
var Branches = function () {
  var current = {} //
    , list = $("#branchList")//
    , menu = $("#branchMenu")//
    , addBtn = $(".addBranchBtn").eq(0)//
    , delBtn = $(".delBranchBtn").eq(0);
  return {
    adding:       false,
    init:         function () {
      menu.hide();
      list.change(function () {
        if (!$(this).val().length) {
          return;
        }
        var ToBranch = Company.get().branches[$(this).val()];
        Branches.change(ToBranch);
      })
    },
    empty:        function () {
      list.empty();
      return this;
    },
    add:          function (data) {
      if (data.branch_id === undefined) {
        var toAdd;
        $.each(data, function (key, value) {
          toAdd += '<option value="' + value.branch_id + '">' + value.br_name + '</option>';
        });
        list.append(toAdd);
      }
      else {
        list.append('<option value="' + data.branch_id + '">' + data.br_name + '</option>');
      }
      return this;
    },
    get:          function () {
      return current
    },
    setval:       function (key, value) {
      current[key] = value;
      Company.get().branches[current.branch_id][key] = value;
    },
    change:       function (data) {
      if (typeof data !== 'object') {
        data = Company.get().branches[data];
      }
      $.each(data, function (key, value) {
        Adv.Forms.setFormDefault('branch[' + key + ']', value, 'company_form');
      });
      Adv.Forms.resetHighlights();
      list.val(data.branch_id);
      current = data;
      if (current.branch_id > 0) {
        list.find("[value=0]").remove();
        delete Company.get().branches[0];
        Branches.adding = false;
        Branches.btnBranchAdd();
      }
    },
    New:          function () {
      $.post('#', {_action: 'newBranch', id: Company.get().id}, function (data) {
        data = data.branch;
        Branches.add(data).change(data);
        Company.get().branches[data.branch_id] = data;
        menu.hide();
        Branches.adding = true;
      }, 'json');
    },
    remove:       function () {
      $.post('#', {_action: 'deleteBranch', branch_id: current.id, id: Company.get().id}, function (data) {
        list.find("[value=" + current.id + "]").remove();
        Branches.change(list.val());
      }, 'json');
    },
    btnBranchAdd: function () {
      addBtn.off('click');
      delBtn.off('click');
      if (!Branches.adding && current.branch_id > 0 && Company.get().id > 0) {
        addBtn.on('click', function () {
          Branches.New();
          Branches.adding = true;
        });
        delBtn.on('click', function () {
          Branches.remove();
          Branches.adding = false;
        });
        menu.show();
      }
      else {
        current.branch_id > 0 ? menu.show() : menu.hide();
      }
      return false;
    }
  };
}();
var Accounts = function () {
  return {
    change: function (data) {
      $.each(data, function (id, value) {
        Adv.Forms.setFormDefault('accounts[' + id + ']', value);
      })
    }
  }
}();
var Company = function () {
  var company, //
    transactions = $('#transactions'), //
    companyIDs = $("#companyIDs"), //
    $companyID = $("#name").attr('autocomplete', 'off'),//
    tabs = null;
  Adv.TabMenu.defer('companyedit').done(function () {
    Company.tabs = Adv.o.tabs['companyedit'];
    Company.tabs.delegate("input, textarea,select", "change keyup", function () {
      var $this = $(this), $thisname = $this.attr('name'), buttontext;
      if ($thisname === 'messageLog' || $thisname === 'branchList') {
        return;
      }
      Adv.Forms.stateModified($this);
      if (this.form.fieldsChanged > 0) {
        Company.btnNew.hide();
        Company.btnCancel.show();
        Company.btnConfirm.show();
        Company.companySearch.prop('disabled', true);
      }
      else {
        if (this.form.fieldsChanged === 0) {
          Company.btnConfirm.hide();
          Company.btnCancel.hide();
          Company.btnNew.show();
          Adv.Events.onLeave();
        }
      }
      Company.set($thisname, $this.val());
    });
  });
  return {
    companySearch: $('#companysearch'),
    fetchUrl:      '#',
    init:          function () {
      Branches.init();
      $companyID.autocomplete({
                                source:     function (request, response) {
                                  request['type'] = (company.type == 1 ? 'Debtor' : 'Creditor');
                                  var lastXhr = $.getJSON('/search', request, function (data, status, xhr) {
                                    if (xhr === lastXhr) {
                                      response(data);
                                    }
                                  });
                                },
                                select:     function (event, ui) {
                                  Company.fetch(ui.item);
                                  return false;
                                },
                                focus:      function () {
                                  return false;
                                },
                                autoFocus:  false, //
                                delay:      10,//
                                'position': {
                                  my:        "left middle",
                                  at:        "right top",
                                  of:        $companyID,
                                  collision: "none"
                                }
                              }).on('paste', function () {
                                      var $this = $(this);
                                      window.setTimeout(function () {$this.autocomplete('search', $this.val())}, 1)
                                    });
    },
    setValues:     function (content) {
      if (!content.company) {
        return;
      }
      company = content.company;
      var data = company, disabledTabs = [];
      if ((Number(company.id) == 0)) {
        disabledTabs = [1, 2, 3, 4];
        Company.tabs.tabs('select', 0);
      }
      else {
        $('.email-button').data('emailid', company.id + '-91-1');
      }
      if (Company.tabs) {
        Company.tabs.tabs('option', 'disabled', disabledTabs);
      }
      else {
        Adv.TabMenu.defer('companyedit').done(function () {Company.tabs.tabs('option', 'disabled', disabledTabs)});
      }
      $('#shortcuts').find('button').prop('disabled', !company.id);
      if (content.contact_log !== undefined) {
        Company.setContactLog(content.contact_log);
      }
      if (content.transactions !== undefined) {
        transactions.empty().append(content.transactions);
      }
      if (data.contacts) {
        Contacts.init(data.contacts);
      }
      if (data.branches) {
        Branches.empty().add(data.branches).change(data.branches[data.defaultBranch]);
      }
      if (data.accounts) {
        Accounts.change(data.accounts);
      }
      $.each(company, function (i, data) {
        if (i !== 'contacts' && i !== 'branches' && i !== 'accounts') {
          Adv.Forms.setFormDefault(i, data, 'company_form');
        }
      });
      Adv.Forms.resetHighlights();
    },
    hideSearch:    function () {
      $companyID.autocomplete('disable');
    },
    showSearch:    function () {
      $companyID.autocomplete('enable');
    },
    fetch:         function (item) {
      if (typeof(item) === "number") {
        item = {id: item};
      }
      $.post(Company.fetchUrl, {_action: 'fetch', id: item.id}, function (data) {
        Company.setValues(data);
      }, 'json');
      Company.getFrames(item.id);
    },
    getFrames:     function (id, data) {
      if (id === undefined && company.id) {
        id = company.id
      }
      var $invoiceFrame = $('#invoiceFrame'), urlregex = /[\w\-\.:/=&!~\*\'"(),]+/g, $invoiceFrameSrc = $invoiceFrame.data('src').match(urlregex)[0];
      if (!id) {
        return;
      }
      data = (data) ? '&' + data + '&' : '';
      $invoiceFrame.load($invoiceFrameSrc, data + "frame&id=" + id);
    },
    useShipFields: function () {
      Company.accFields.each(function () {
        var newval //
          , $this = $(this)//
          , name = $this.attr('name').match(/([^[]*)\[(.+)\]/);
        if ($this.val().length > 0) {
          return;
        }
        if (!name) {
          name = $this.attr('name');
          name = [name, name.split('_')[1]];
          if (!name) {
            return
          }
          newval = $("[name='" + name[1] + "']").val();
        }
        else {
          newval = $("[name='branch[" + name[2] + "]']").val();
        }
        $this.val(newval).trigger('keyup');
        Company.set(name[0], newval);
      });
    },
    Save:          function () {
      Branches.btnBranchAdd();
      Company.btnConfirm.prop('disabled', true);
      $.post(Company.fetchUrl, {_action: 'Save', company: Company.get()}, function (data) {
        Company.btnConfirm.prop('disabled', false);
        if (data.status && data.status.status) {
          Branches.adding = false;
          Company.setValues(data);
          Company.revertState();
        }
      }, 'json');
    },
    set:           function (key, value) {
      var group//
        , valarray = key.match(/([^[]*)\[(.+)\]/);
      if (valarray !== null) {
        group = valarray[1];
        key = valarray[2];
      }
      switch (group) {
        case 'accounts':
          company.accounts[key] = value;
          break;
        case 'branch':
          Branches.setval(key, value);
          break;
        case 'contact':
          Contacts.setval(key, value);
          break;
        default:
          company[key] = value;
      }
    },
    get:           function () {
      return company
    },
    revertState:   function () {
      var form = document.getElementById('company_form');
      form.reset();
      Company.companySearch.prop('disabled', false);
      Company.btnConfirm.hide();
      Company.btnCancel.hide();
      Company.btnNew.show();
      Branches.btnBranchAdd();
      Adv.Forms.resetHighlights(form);
    },
    resetState:    function () {
      var form = document.getElementById('company_form');
      $(form).find("#tabs0 input, #tabs0 textarea").empty();
      $("#company").val('');
      Company.fetch(0);
      Company.fieldsChanged = 0;
      Company.btnCancel.hide();
      Company.btnConfirm.hide();
      Company.btnNew.show();
    },
    accFields:     $("[name^='accounts']"),
    fieldsChanged: 0,
    btnConfirm:    $("#btnConfirm").mousedown(function () {
      Company.Save();
      return false;
    }).hide(),
    btnCancel:     $("#btnCancel").mousedown(function () {
      Company.revertState();
      return false;
    }).hide(),
    btnNew:        $("#btnNew").mousedown(function () {
      Company.resetState();
      return false;
    }),
    ContactLog:    $("#contactLog").hide(),
    getContactLog: function (id, type) {
      var data = {
        contact_id: id,
        type:       type
      };
      $.post('contact_log.php', data, function (data) {
        Company.setContactLog(data);
      }, 'json');
    },
    setContactLog: function (data) {
      var logbox = $("[id='messageLog']").val('')//
        , str = '';
      $.each(data, function (key, message) {
        str += '[' + message['date'] + '] Contact: ' + message['contact_name'] + "\nMessage:  " + message['message'] + "\n\n";
      });
      logbox.val(str);
    }}
}();
$(function () {
  if (!Company.accFields.length) {
    Company.accFields = $("[name^='supp_']");
  }
  $("#useShipAddress").click(function (e) {
    Company.useShipFields();
    return false;
  });
  $("#addLog").click(function () {
    Company.ContactLog.dialog("open");
    return false;
  });
  Company.ContactLog.dialog({
                              autoOpen:  false,
                              show:      "slide",
                              resizable: false,
                              hide:      "explode",
                              modal:     true,
                              width:     700,
                              maxWidth:  700,
                              buttons:   {
                                "Ok":   function () {
                                  var data = {
                                    contact_name: Company.ContactLog.find("[name='contact_name']").val(),
                                    contact_id:   Company.get().id,
                                    message:      Company.ContactLog.find("[name='message']").val(),
                                    type:         Company.ContactLog.find("#type").val()
                                  };
                                  Company.ContactLog.dialog('disable');
                                  $.post('contact_log.php', data, function (data) {
                                    Company.ContactLog.find(':input').each(function () {
                                      Company.ContactLog.dialog('close').dialog('enable');
                                    });
                                    Company.ContactLog.find("[name='message']").val('');
                                    Company.setContactLog(data);
                                  }, 'json');
                                },
                                Cancel: function () {
                                  Company.ContactLog.find("[name='message']").val('');
                                  $(this).dialog("close");
                                }
                              }
                            }).click(function () {
                                       $(this).dialog("open");
                                     });
  $("#messageLog").prop('disabled', true).css('background', 'white');
  $("[name='messageLog']").keypress(function () {
    return false;
  });
  $("#shortcuts").on('click', 'button', function () {
    var $this = $(this)//
      , url = $this.data('url');
    if (url) {
      Adv.openTab(url + Company.get().id);
    }
  });
  $("#id").prop('disabled', true);
  Adv.o.wrapper.delegate('#RefreshInquiry', 'click', function () {
    Company.getFrames(undefined, $('#invoiceForm').serialize());
    return false;
  });
  Company.init();
  // Company.getFrames($("#id").val());
});
console.profileEnd();
