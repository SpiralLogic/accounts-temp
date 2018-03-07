/**
 * Created by JetBrains PhpStorm.
 * User: complex
 * Date: 11/22/10
 * Time: 3:30 AM
 * To change this template use File | Settings | File Templates.
 */
;
//noinspection JSUnusedLocalSymbols
(function (window, $, undefined) {
  var $current, Searchboxtimeout//
    , menuTimeout//
    , Adv = window.Adv//
    , sidemenu = {}//
    , activestate = true//
    , searchInput = $('<input/>').attr({'id': 'searchInput', type: 'text', class: 'small'}).data({'id': '', url: ''})//
    , $search = $("#search")//
    , $quickCustomer = $('#quickCustomer')//
    , $quickSupplier = $('#quickSupplier');
  (function () {
    var $this = this, $wrapper = $("#wrapper"), previous;
    this.menu = $("#sidemenu").accordion({ heightStyle: "content", active: false, event: "mouseenter"}).draggable().show();
    this.sidemenuHide = function () {
      $this.menu.clearQueue().animate({right: ' -10em', opacity: '.75'}, 500).accordion('option', {collapsible: true});
    };
    this.sidemenuActive = function () {
      activestate = true;
      $this.menu.stop().animate({right: '-10em', opacity: '.75'}, 300).accordion("enable").mouseenter(function () {
        window.clearTimeout(menuTimeout);
        $(this).stop().animate({right: '1em', opacity: '1'}, 500).accordion('option', {collapsible: false});
      }).mouseleave(function () {
                      window.clearTimeout(menuTimeout);
                      menuTimeout = window.setTimeout($this.sidemenuActive, 1000);
                    });
    };
    this.sidemenuActive();
    this.sidemenuInactive = function () {
      activestate = false;
      $this.menu.unbind('mouseenter').accordion('option', {collapsible: false, active: true}).accordion("disable");
      $this.menu.find("h3").one("click", function () {
        $this.hideSearch();
        $this.sidemenuActive();
      })
    };
    this.doSearch = function () {
      var term = searchInput.val();
      Adv.lastXhr = $.post(searchInput.data("url"), { 'q': term, limit: true }, $this.showSearch);
    };
    this.showSearch = function (data) {
      Adv.Forms.setFocus(false);
      previous = $wrapper.contents().detach();
      $this.sidemenuHide();
      history.pushState({}, 'Search Results', searchInput.data("url") + 'q=' + searchInput.val());
      $wrapper.html(data);
      Adv.Status.show({html: $('.msgbox').detach().html()});
    };
    this.hideSearch = function () {
      if ($current) {
        searchInput.val('').detach();
        $current.show();
      }
    }
    $search.delegate("li", "click", function () {
      $this.hideSearch();
      $current = $(this).hide();
      $this.sidemenuInactive();
      searchInput.data({'id': $current.data('href'), url: $current.data('href')}).insertBefore($current).focus();
      return false;
    });
    $search.delegate('input', "change blur keyup paste", function (event) {
      window.clearTimeout(Searchboxtimeout);
      if (Adv.lastXhr && event.type == 'keyup') {
        if (event.keyCode == 13) {
          Searchboxtimeout = window.setTimeout($this.doSearch, 1);
          return false;
        }
        Adv.lastXhr.abort();
      }
      if (event.type == 'paste') {
        Searchboxtimeout = window.setTimeout($this.doSearch, 1);
        return;
      }
      if (event.type != "blur" && searchInput.val().length > 1 && event.which < 123) {
        Searchboxtimeout = window.setTimeout($this.doSearch, 1000);
      }
      if (event.type == 'blur') {
        menuTimeout = window.setTimeout($this.sidemenuHide, 1000);
        Searchboxtimeout = window.setTimeout($this.sidemenuActive, 1000);
      }
    });
    $quickCustomer.focus(function () { $this.sidemenuInactive()}).blur(function () {
      searchInput.trigger('blur');
    }).autocomplete({
                      source:    function (request, response) {
                        Adv.lastXhr = $.getJSON('/contacts/manage/customers', request, function (data, status, xhr) {
                          if (xhr === Adv.lastXhr) {
                            response(data);
                          }
                        })
                      },
                      minLength: 2,
                      select:    function (event, ui) {
                        window.location.href = '/contacts/manage/customers?id=' + ui.item.id;
                      }
                    });
    $quickSupplier.focus(function () { $this.sidemenuInactive()}).blur(function () {searchInput.trigger('blur')}).autocomplete({
                                                                                                                                 source:    function (request, response) {
                                                                                                                                   Adv.lastXhr = $.getJSON('/contacts/manage/suppliers', request, function (data, status, xhr) {
                                                                                                                                     if (xhr === Adv.lastXhr) {
                                                                                                                                       response(data);
                                                                                                                                     }
                                                                                                                                   })
                                                                                                                                 },
                                                                                                                                 minLength: 2,
                                                                                                                                 select:    function (event, ui) {
                                                                                                                                   window.location.href = '/contacts/manage/suppliers?id=' + ui.item.id;
                                                                                                                                 }
                                                                                                                               });
  }).apply(sidemenu);
  Adv.sidemenu = sidemenu;
})(window, jQuery);
