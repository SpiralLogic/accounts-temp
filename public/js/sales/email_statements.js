$(function () {
  $("#send").click(sendstatements);
});
var tosend = document.getElementsByClassName('email');
tosend = toArray(tosend);
sendstatements = function () {
  var v = tosend.pop(), postVars = {
    REP_ID:  108,
    PARAM_0: 0,
    PARAM_1: '',
    PARAM_2: 1,
    PARAM_3: '',
    PARAM_4: 0,
    PARAM_5: 0,
    PARAM_6: 0,
    PARAM_7: 0
  };
  if (!v) {
    return;
  }
  if (!v.checked) {
    sendstatements();
  }
  postVars['PARAM_0'] = v.value;
  $.post('/reporting/prn_redirect.php', postVars, function (data) {
    $('#table').after(data);
    sendstatements();
  });
}
function toArray(obj) {
  var array = [];
  // iterate backwards ensuring that length is an UInt32
  for (var i = obj.length >>> 0; i--;) {
    array[i] = obj[i];
  }
  return array;
}
