<div class="center">
  <div class="formbox formdiv">
    <h3>{{$title}}:</h3><br>
    {{$form._start}}
    {{#$form}}
    {{.}}
    {{/$form}}
    <br>
    {{#$form.buttons}}
    {{.}}
    {{/$form.buttons}}
    {{$form._end}}
  </div>
</div>
