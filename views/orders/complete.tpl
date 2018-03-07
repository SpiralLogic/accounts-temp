<div class="center">
  {{$printtrans}}<br><br>
  {{$modifytrans}}<br> {{$viewtrans}}<br><br>
  {{$emailtrans}}<br><br>
{{#$buttons}}
    <a class="button" href="{{$.href}}" accesskey="{{$.accesskey}}" {{$.target?}}target="{{$.target}}"{{/$.target?}}>{{$.label}}</a>
    <br><br>
{{/$buttons}}
</div>
