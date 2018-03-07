$(function ($) {
  var mathsOnly = function (ch, event, value) {
    return '+-*/'.indexOf(ch) > -1 && !(ch == '-' && (value == '' || value == '0.00'));
  };
  Adv.o.wrapper.on('focus', ".amount", function () {
    var value = this.value//
      , $this = $(this);
    if ($this.hasClass('hasCalculator')) {
      return;
    }
    value = ((value[0] == '-') ? '-' : '') + value.replace(/[^0-9\.]/g, '');
    this.value = value;
    $this.calculator({
                       useThemeRoller: true, //
                       showOn:         'operator', //
                       isOperator:     mathsOnly, //
                       constrainInput: false, //
                       precision:      user.pdec
                     });
  })
});
