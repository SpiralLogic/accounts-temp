/*
 Behaviour v1.1 by Ben Nolan, June 2005. Based largely on the work
 of Simon Willison (see comments by Simon below).
 Small fixes by J.Dobrowolski for ADV Accounting May 2008
 Description:

 Uses css selectors to apply javascript behaviours to enable
 unobtrusive javascript in html documents.

 Usage:

 var myrules = {
 'b.someclass' : function(element){
 element.onclick = function(){
 alert(this.innerHTML);
 }
 },
 '#someid u' : function(element){
 element.onmouseover = function(){
 this.innerHTML = "BLAH!";
 }
 }
 };

 Behaviour.register(myrules);

 // Call Behaviour.apply() to re-apply the rules (if you
 // update the dom, etc).

 License:

 This file is entirely BSD licensed.

 More information:

 http://ripcord.co.nz/behaviour/

 */
var Behaviour = {
  list:         [],
  register:     function (sheet) {
    Behaviour.list.push(sheet);
  },
  start:        function () {
    Behaviour.addLoadEvent(Behaviour.apply);
  },
  apply:        function () {
    var selector = '', sheet, element, list;
    for (var h = 0; sheet = Behaviour.list[h]; h++) {
      for (selector in sheet) {
        var sels = selector.split(',');
        for (var n = 0; n < sels.length; n++) {
          list = document.getElementsBySelector(sels[n]);
          if (!list) {
            continue;
          }
          for (var i = 0; element = list[i]; i++) {
            sheet[selector](element);
          }
        }
      }
    }
  },
  addLoadEvent: function (func) {
    var oldonload = window.onload;
    if (typeof window.onload != 'function') {
      window.onload = func;
    }
    else {
      window.onload = function () {
        oldonload();
        func();
      }
    }
  }
};
Behaviour.start();
document.getElementsBySelector = jQuery;
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

JsHttpRequest.request = function (trigger, form, tout) {
  tout = (tout) ? tout : 15000;
  try {
    Adv.loader.on(tout);
  }
  catch (e) {
  }
  JsHttpRequest._request(trigger, form, tout, 0);
};
JsHttpRequest._request = function (trigger, form, tout, retry) {
  var tcheck;
  var url;
  var upload;
  var content;
  var submitObj;
  if (trigger === '_action') {
    return;
  }
  if (trigger.tagName == 'A') {
    content = {};
    upload = 0;
    url = trigger.href;
    if (trigger.id) {
      content[trigger.id] = 1;
    }
  }
  else {
    submitObj = typeof(trigger) == "string" ? document.getElementsByName(trigger)[0] : trigger;
    form = form || (submitObj && submitObj.form);
    upload = form && form.enctype == 'multipart/form-data';
    url = form && form.getAttribute('action') ? form.getAttribute('action') : window.location.pathname;
    if (form && !form.getAttribute('action')) {form.setAttribute('action', url) }
    content = this.formInputs(trigger, form, upload);
    if (!form) {
      url = url.substring(0, url.indexOf('?'));
      content[trigger.name] = trigger.value;
    }
    if (!submitObj) {
      content[trigger] = 1;
    }
  }
  // this is to avoid caching problems
  content['_random'] = Math.random() * 1234567;
  if (trigger.tagName === 'BUTTON') {
    content['_action'] = trigger.value;
  }
  if (trigger.tagName === 'INPUT' && trigger.type === 'checkbox') {
    content['_action'] = trigger.value;
    content['_value'] = !!trigger.checked;
    Adv.Forms.saveFocus(trigger);
  }
  if (trigger.tagName === 'SELECT' && $(trigger).is('.async')) {
    content['_action'] = 'Changed';
  }
  if (trigger.id) {
    content['_control'] = trigger.id;
  }
  subForms = $(form).find('form').each(function (e, f) {
    console.log(e, f);
  });
  tcheck = setTimeout(function () {
    for (var id in JsHttpRequest.PENDING) {
      var call = JsHttpRequest.PENDING[id];
      if (call != false) {
        if (call._ldObj.xr) // needed for gecko
        {
          call._ldObj.xr.onreadystatechange = function () {
          };
        }
        call.abort(); // why this doesn't kill request in firebug?
//						call._ldObj.xr.abort();
        delete JsHttpRequest.PENDING[id];
      }
    }
    retry ? Adv.loader.on(tout) : Adv.loader.warning();
    if (retry) {
      JsHttpRequest._request(trigger, form, tout, retry - 1);
    }
  }, tout);
  JsHttpRequest.query((upload ? "form." : "") + "POST " + url, // force form loader
                      content, // Function is called when an answer arrives.
                      function (result, errors) {
                        // Write the answer.
                        var newwin = 0, cmd, atom, property, type, id, data, objElement, hasStatus = false;
                        if (result) {
                          for (var i in result) {
                            atom = result[i];
                            cmd = atom['n'];
                            property = atom['p'];
                            type = atom['c'];
                            id = atom['t'];
                            data = atom['data'];
                            // seek element by id if there is no elemnt with given name
                            objElement = document.getElementsByName(id)[0] || document.getElementById(id);
                            if (cmd == 'as') {
                              $(objElement).attr(property, data);
                            }
                            else {
                              if (cmd == 'up') {
                                if (objElement) {
                                  if (objElement.tagName == 'INPUT' || objElement.tagName == 'TEXTAREA') {
                                    objElement.value = data;
                                  }
                                  else {
                                    $(objElement).empty().append(data);
                                  } // selector, div, span etc
                                }
                              }
                              else {
                                switch (cmd) {
                                  case 'di':
                                    objElement.disabled = data;
                                    break;
                                  case 'fc':
                                    if (data.el === undefined) {
                                      Adv.Forms.setFocus(data);
                                    }
                                    else {
                                      Adv.Forms.setFocus(data.el, undefined, data.pos);
                                    }
                                    break;
                                  case 'js':
                                    eval(data);
                                    break;
                                  case 'rd':
                                    window.location = data;
                                    break;
                                  case 'json':
                                    if (data.status) {
                                      hasStatus = true;
                                      Adv.Status.show(data.status);
                                    }
                                    if (Adv.Forms[property]) {
                                      Adv.Forms[property](data);
                                    }
                                    break;
                                  case 'pu':
                                    newwin = 1;
                                    window.open(data, undefined, 'toolbar=no,scrollbar=no,resizable=yes,menubar=no');
                                    break;
                                  default:
                                    errors = errors + '<br>Unknown ajax function: ' + cmd;
                                }
                              }
                            }
                          }
                          if (tcheck) {
                            JsHttpRequest.clearTimeout(tcheck);
                          }
                          // Write errors to the debug div.
                          if (errors && !hasStatus) {
                            if (cmd && cmd == 'fc') {
                              Adv.Forms.error(data, errors)
                            }
                            else {
                              Adv.Status.show({html: errors});
                            }
                          }
                          if (Adv.loader) {
                            Adv.loader.off();
                          }
                          Behaviour.apply();
                          //document.getElementById('msgbox').scrollIntoView(true);
                          // Restore focus if we've just lost focus because of DOM element refresh
                          Adv.Events.rebind();
                          if (!errors && !hasStatus && !newwin && cmd != 'fc') {
                            Adv.Scroll.loadPosition(true);
                          }
                        }
                      }, false);
};
// collect all form input values plus inp trigger value
JsHttpRequest.formInputs = function (inp, objForm, upload) {
  var name;
  var el;
  var formElements;
  var submitObj = inp, q = {}, value;
  if (typeof(inp) == "string") {
    submitObj = document.getElementsByName(inp)[0] || inp;
  }
  objForm = objForm || (submitObj && submitObj.form);
  if (objForm) {
    formElements = objForm.elements;
    for (var i = 0; i < formElements.length; i++) {
      el = formElements[i];
      name = el.name;
      if (!el.name) {
        continue;
      }
      if (upload) { // for form containing file inputs collect all
        // form elements and add value of trigger submit button
        // (internally form is submitted via form.submit() not button click())
        q[name] = submitObj.type == 'submit' && el == submitObj ? el.value : el;
        continue;
      }
      if (el.type) {
        if (((el.type == 'radio' || el.type == 'checkbox') && el.checked == false) || (el.type == 'submit' && (!submitObj || el.name != submitObj.name))) {
          continue;
        }
      }
      if (el.disabled && el.disabled == true) {
        continue;
      }
      if (name) {
        if (el.type == 'select-multiple') {
          name = name.substr(0, name.length - 2);
          q[name] = [];
          for (var j = 0; j < el.length; j++) {
            s = name.substring(0, name.length - 2);
            if (el.options[j].selected == true) {
              q[name].push(el.options[j].value);
            }
          }
        }
        else {
          value = el.value;
          if (el.hasAttribute('data-dec')) {
            value = value.replace(user.ts, '');
          }
          q[name] = value;
        }
      }
    }
  }
  return q;
};
/*
 Behaviour definitions
 */
Behaviour.register({
                     'input':                                                                                                          function (e) {
                       if (e.onfocus === undefined) {
                         e.onfocus = function () {
                           Adv.Forms.saveFocus(this);
                           if ($(this).is('.combo')) {
                             this.select();
                           }
                         };
                       }
                       if ($(e).is('.combo,.combo2')) {
                         e.setAttribute('_last', e.value);
                         e.onblur = function () {
                           var but_name = this.name.substring(0, this.name.length - 4) + 'button'
                             , button = document.getElementsByName(but_name)[0]
                             , select = document.getElementsByName(this.getAttribute('rel'))[0];
                           Adv.Forms.saveFocus(select);
                           // submit request if there is submit_on_change option set and
                           // search field has changed.
                           if (button && (this.value != this.getAttribute('_last'))) {
                             JsHttpRequest.request(button);
                           }
                           else {
                             if ($(this).is('.combo2')) {
                               this.style.display = 'none';
                               select.style.display = 'inline';
                               Adv.Forms.setFocus(select);
                             }
                           }
                           return false;
                         };
                         e.onkeyup = function () {
                           var select = document.getElementsByName(this.getAttribute('rel'))[0];
                           if (select && select.selectedIndex >= 0) {
                             var len = select.length;
                             var byid = $(this).is('.combo');
                             var ac = this.value.toUpperCase();
                             select.options[select.selectedIndex].selected = false;
                             for (var i = 0; i < len; i++) {
                               var txt = byid ? select.options[i].value : select.options[i].text;
                               if (txt.toUpperCase().indexOf(ac) >= 0) {
                                 select.options[i].selected = true;
                                 break;
                               }
                             }
                           }
                         };
                         e.onkeydown = function (ev) {
                           ev = ev || window.event;
                           var key = ev.keyCode || ev.which;
                           if (key == 13) {
                             this.blur();
                             return false;
                           }
                           return undefined;
                         }
                       }
                       else {
                         if (e.type == 'text') {
                           e.onkeydown = function (ev) {
                             ev = ev || window.event;
                             var key = ev.keyCode || ev.which;
                             if (key == 13) {
                               if ($(e).is('.searchbox')) {
                                 e.onblur();
                               }
                               return false;
                             }
                             return true;
                           }
                         }
                       }
                     },
                     'input.combo2':                                                                                                   function (e) {
                       // this hides search button for js enabled browsers
                       e.style.display = 'none';
                     },
                     'div.js_only':                                                                                                    function (e) {
                       // this shows divs for js enabled browsers only
                       e.style.display = 'block';
                     },
//	'.ajaxsubmit,.editbutton,.navibutton': // much slower on IE7
                     'button[name="_action"],button.ajaxsubmit,input.ajaxsubmit,input.editbutton,button.editbutton,button.navibutton': function (e) {
                       e.onclick = function () {
                         Adv.Forms.saveFocus(e);
                         var asp = e.getAttribute('data-aspect');
                         if (asp && asp.indexOf('process') !== -1) {
                           JsHttpRequest.request(this, null, 60000);
                         }
                         else {
                           JsHttpRequest.request(this);
                         }
                         return false;
                       }
                     },
                     'button':                                                                                                         function (e) {
                       if (e.name) {
                         var func = (e.name == '_action') ? _validate[e.value] : _validate[e.name];
                         var old = e.onclick;
                         if (func) {
                           if (typeof old != 'function' || old == func) { // prevent multiply binding on ajax update
                             e.onclick = func;
                           }
                           else {
                             e.onclick = function () {
                               if (func()) {
                                 old();
                                 return true;
                               }
                               else {
                                 return false;
                               }
                             }
                           }
                         }
                       }
                     },
                     '[data-dec]':                                                                                                     function (e) {
                       if (!e.onblur) {
                         e.onblur = function () {
                           var dec = e.getAttribute("data-dec");
                           Adv.Forms.priceFormat(this, Adv.Forms.getAmount(this), dec);
                         };
                         e.onblur();
                       }
                     },
                     '.freight':                                                                                                       function (e) {
                       if (e.onblur === undefined) {
                         e.onblur = function () {
                           var dec = this.getAttribute("data-dec");
                           Adv.Forms.priceFormat(this, Adv.Forms.getAmount(this), dec, '2');
                         };
                       }
                     },
                     '.searchbox':// emulated onchange event handling for text inputs
                                                                                                                                       function (e) {
                                                                                                                                         e.setAttribute('_last_val', e.value);
                                                                                                                                         e.setAttribute('autocomplete', 'off'); //must be off when calling onblur
                                                                                                                                         e.onblur = function () {
                                                                                                                                           var val = this.getAttribute('_last_val');
                                                                                                                                           if (val != this.value) {
                                                                                                                                             this.setAttribute('_last_val', this.value);
                                                                                                                                             JsHttpRequest.request('_' + this.name + '_changed', this.form);
                                                                                                                                           }
                                                                                                                                         }
                                                                                                                                       },
                     'button[data-aspect="selector"], input[data-aspect="selector"]':                                                  function (e) {
                       var passBack = function (value) {
                         var o = opener;
                         if (!value) {
                           var back = o.editors[o.editors._call]; // form input bindings
                           var to = o.document.getElementsByName(back[1])[0];
                           if (to) {
                             if (to[0] != undefined) {
                               to[0].value = value;
                             } // ugly hack to set selector to any value
                             to.value = value;
                             // update page after item selection
                             o.JsHttpRequest.request('_' + to.name + '_update', to.form);
                             o.setFocus(to.name);
                           }
                         }
                         document.close();
                       };
                       e.onclick = function () {
                         passBack(this.getAttribute('rel'));
                         return false;
                       }
                     },
                     'select':                                                                                                         function (e) {
                       if (e.onfocus === undefined) {
                         e.onfocus = function () {
                           Adv.Forms.saveFocus(this);
                         };
                       }
                       if ($(e).is('.combo,.combo2')) {


                         // When combo position is changed via js (eg from searchbox)
                         // no onchange event is generated. To ensure proper change
                         // signaling we must track selectedIndex in onblur handler.
                         var _update_box = function (s) {
                           var old
                             , opt
                             , byid = $(s).is('.combo')
                             , rel = s.getAttribute('rel')
                             , box = document.getElementsByName(rel)[0];
                           if (box && s.selectedIndex >= 0) {
                             opt = s.options[s.selectedIndex];
                             if (box) {
                               old = box.value;
                               box.value = byid ? opt.value : opt.text;
                               box.setAttribute('_last', box.value);
                               return old != box.value
                             }
                           }
                           return undefined;
                         };
                         e.setAttribute('_last', e.selectedIndex);
                         e.onblur = function () {
                           if ((this.selectedIndex != this.getAttribute('_last')) || ($(this).is('.combo') && _update_box(this))) {
                             this.onchange();
                           }
                         };
                         e.onchange = function () {
                           var update, sname, s = this;
                           s.setAttribute('_last', s.selectedIndex);
                           if ($(s).is('.combo')) {
                             _update_box(s);
                           }
                           if (s.selectedIndex >= 0) {
                             sname = '_' + s.name + '_update';
                             update = document.getElementsByName(sname)[0];
                             if ($(s).is('.async')) {
                               JsHttpRequest.request(this);
                             }
                             if (update) {
                               JsHttpRequest.request(update);
                             }
                           }
                           return true;
                         };
                         e.onkeydown = function (event) {
                           event = event || window.event;
                           var key = event.keyCode || event.which;
                           var box = document.getElementsByName(this.getAttribute('rel'))[0];
                           if (box && key == 32 && $(this).is('.combo2')) {
                             this.style.display = 'none';
                             box.style.display = 'inline';
                             box.value = '';
                             Adv.Forms.setFocus(box);
                             return false;
                           }
                           return undefined;
                         }
                       }
                     },
                     'a.printlink,button.printlink':                                                                                   function (e) {
                       e.onclick = function () {
                         Adv.Forms.saveFocus(this);
                         JsHttpRequest.request(this, null, 60000);
                         return false;
                       }
                     }
                   });
Behaviour.addLoadEvent(Adv.Scroll.loadPosition);
