/**
 * Created with JetBrains PhpStorm.
 * User: advanced
 * Date: 27/08/12
 * Time: 10:01 PM
 * To change this template use File | Settings | File Templates.
 */
Adv.extend({ Help:(function () {
  var $current //
    , indicatortimer //
    , $help_modal = $('#help_text_edit').modal({show:false}).appendTo('body')//
    , showHelp = function () {
      var content//
        , helpTitle //
        , data = {page:location.pathname + location.search, element:$current.attr('id')} //
        , page = data.page //
        , element = data.element//
        , editTextarea = $('#newhelp')//
        , editTitle = $('#newhelptitle')//
        , url = '/modules/help_texts'//
        , showEditor = function () {
          $current.popover('hide');
          $help_modal.modal('show').on('click', '.save',function () {
            var div = $('<div>'), text = div.html(editTextarea.val())[0].innerHTML, title = editTitle.val();
            $.post(url, {title:title, text:text, element:element, page:page, save:true}, makePopover, 'json');
            $help_modal.modal('hide');
          }).on('shown',function () {
                  editTextarea[0].focus();
                  editTextarea[0].select();
                }).on('hidden', function () {
                        $current[0].focus();
                        $current[0].select();
                      });
          editTextarea.empty().text(content);
          editTitle.empty().val(helpTitle);
        }//
        , makePopover = function (data) {
          content = data.text;
          helpTitle = data.title || 'Help';
          $(':input').popover('destroy');
          indicator.hide();
          $current.popover({title:helpTitle + "<i class='floatright help-edit font13 icon-edit'>&nbsp;</i>", html:true, dealy:{show:0, hide:300}, content:data.text || 'No Help Yet!' }).popover('show');
          $('.popover-title').on('click', '.help-edit', showEditor);
        };
      $.post(url, data, makePopover, 'json');
    } //
    , indicator = $('#help_indicator').on('click', showHelp)
    , startHide = function () {
      clearTimeout(indicatortimer);
      indicatortimer = setTimeout(function () {indicator.animate({opacity:0}, 300, function () {$(this).hide();})}, 500);
      $current.off('mouseleave.indicator');
      $(document).off('keydown.indicator');
    } //
    , showIndicator = function () {
      var $this = $(this);
      if ($this == $current || !$this.attr('id') || $this.is('.navibutton')) {return;}
      $current = $(this);
      $(':input').popover('destroy');
      $current.on('mouseleave.indicator blur.indicator', startHide);
      $(document).on('keydown.indicator', function (event) {
        if (event.keyCode === 112 && indicator.is(':visible')) {showHelp();}
        if (event.keyCode === 27) {
          $current.popover('hide');
          indicator.show();
        }
      });
      clearTimeout(indicatortimer);
      indicator.show().css('opacity', '1').position({my:"left top", at:"right top", of:$current})
    }; //
  Adv.o.wrapper.on('mouseenter.indicator focus.indicator', 'button,textarea,input,select', showIndicator);
  indicator.on('mouseenter',function () {
    clearTimeout(indicatortimer);
  }).on('mouseleave', startHide);
}())});
