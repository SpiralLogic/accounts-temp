<div class="center">
  <div class="formbox formdiv">
    {{$form._start}}
    <div class='formbox'>
      <div class='tablehead'>Decimals:</div>
      {{#$form.decimals}}
      {{.}}
      {{/$form.decimals}}
      <div class='tablehead'>Dates:</div>
      {{#$form.dates}}
      {{.}}
      {{/$form.dates}}
    </div>
    <div class='formbox'>
      <div class='tablehead'>Other:</div>
      {{#$form.other}}
      {{.}}
      {{/$form.other}}
      {{$form._end}}
    </div>
  </div>
</div>
