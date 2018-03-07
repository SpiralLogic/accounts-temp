<!DOCTYPE HTML>
<html lang='en' dir='{{$rtl}}'>
<meta charset='utf-8'>
<head><title>{{$title}}</title>
  <link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>
  <link href='/themes/{{$theme}}/default.css' rel='stylesheet'>
</head>
<body class='loginscreen'>
<div id="login">
  <div class='login'>
    <a target='_blank' href='{{POWERED_URL}}'><img width=440 height=64 src='/themes/{{$theme}}/images/logo-advaccounts.png' alt='ADVAccounts'/></a>
    {{#if $timeout}}
    <br><span class='font10'>Authorization timeout</span><br>You were idle for: {{$idletime}}
    {{/if}}
    <div class='tablehead'>
      Version {{VERSION}} - Login
    </div>
    <div class='formdiv'>
      <p class='center pad5'>{{$login_text}}</p>
      {{$form._start}}
      <hr>
      {{#$form}}
      {{.}}
      <hr>
      {{/$form}}
      <div class='center'>
        {{#$form.buttons}}
        {{.}}
        {{/$form.buttons}}
        {{#$form.hidden}}
        {{.}}
        {{/$form.hidden}}
      </div>
      {{$form._end}}
    </div>
  </div>
  <div class='pad20'><a target='_blank' href='{{POWERED_URL}}' tabindex='-1'>{{APP_TITLE}} {{VERSION}} - Theme: {{$theme}} </a>| {{$date}}
    <p><a target='_blank' href='{{POWERED_URL}}' tabindex='-1'>{{POWERED_BY}}</a></p></div>
</div>
<script src="/js/libs/aes.js"></script>
<script src="/js/libs/jquery.min.js"></script>
<script>$(function () {
    $('form').each(function () {
      if (this.password_iv) {
        var form = this, password_iv = this.password_iv, iv = password_iv.value;
        password_iv.parentNode.removeChild(password_iv);
        this._action.addEventListener('click', function () {
          $('form').find(':password').each(function () {
            this.value = Base64.encode(Aes.Ctr.encrypt(this.value, iv, 256));
          });
          form.submit();
          return false;
        });
      }
    });
    var form = document.forms.login_form;
    {{#if $timeout}}form.login_company.value = '{{$company}}';
    {{/if}}
    form.user_name.select();
    form.user_name.focus();
  });</script>
</body>
</html>
