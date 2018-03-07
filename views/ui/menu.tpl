<div class='width90 center ui-tabs ui-widget ui-widget-content ui-corner-all tabs' id='{{$id}}'>
    <ul class='ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all'>
    {{#foreach $items as $key => $item}}
        <li {{HTML::attr($item->liattrs)}}>
            <a {{HTML::attr($item->attrs)}}><span>{{$item->label}}</span></a></li>
    {{/foreach}}
    </ul>
{{#$tabs}}
    <div {{HTML::attr($.attrs)}}>
    {{$.contents}}
    </div>
{{/$tabs}}
</div>
