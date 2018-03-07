{{#if $backlink}}
  <div class='center margin20'>
    <a class='button' href='javascript:(window.history.length === 1) ? window.close() : window.history.go(-1);'>{{ $backlink }}</a>
  </div>
{{/if}}
<!-- end page body div -->
{{$page_body}}
<!-- end wrapper div-->
</div>
{{#if $footer}}
  <div id='footer'>
    {{#if $user}}
      <span class='power'><i class='icon-share'> </i><a target='_blank' href='{{POWERED_URL}}'>{{POWERED_BY}}</a></span>
      <span class='date'>{{$today}} | {{$now}}</span>
      <span> </span>| <span>mem/peak: {{$mem}} </span><span>|</span><span> load time: {{$loaeed_time}}</span>
    {{/if}}
    <!-- end footer div-->
  </div>
{{/if}}
{{#if !REQUEST_AJAX}}
  {{>ui/help_modal}}
{{/if}}
<!-- end content div-->
</div>
{{#if !REQUEST_AJAX}}
  {{>ui/sidemenu}}
{{/if}}
{{$messages}}
<script>{{$beforescripts}}
</script>  {#$JS->render()#}
{{#if !REQUEST_AJAX}}
  </body>
  </html>
{{/if}}
