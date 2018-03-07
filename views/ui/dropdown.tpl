<div class="btn-group {{$auto}}">
  {{#if $split}}
    <button class="btn btn-xs btn-primary btn-split">{{$title}}</button>
    <button class='btn btn-xs btn-primary dropdown-toggle {{$auto}}' data-toggle="dropdown"><span class="icon-caret-down"></span></button>
  {{else}}
    <button class="btn btn-xs btn-primary dropdown-toggle {{$auto}}" data-toggle="dropdown">{{$title}}&nbsp;<span class="icon-caret-down"></span></button>
  {{/if}}
  <ul class="dropdown-menu">
    {{#$items}}
      <li>
        <a {{#$.attr}} {{!}}="{{.}}"{{/$.attr}}>{{$.label}}</a></li>
    {{/$items}}
  </ul>
</div>
