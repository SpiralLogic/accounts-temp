<div class='center'>
{{$form._start}}
    <table class='{{$class}}'>
        <thead>
        <tr class='navibar'>
            <td colspan='{{$colspan}}' class='navibar'>
        <span>{{$records}} {{$inactive?}}<label><input {{$checked}} type='checkbox' name='_action' value='showInactive' onclick='JsHttpRequest.request(this)'>Show also
            inactive</label>{{/$inactive?}}</span><span class='floatright'>{{#navbuttons}}{{.}}{{/navbuttons}}</span></td>
        </tr>
        <tr class="naviheader">{{#$headers}}
            <th>{{.}}</th>
        {{/$headers}}</tr>
        </thead>
    {{#$rows}}
    {{$.group?}}
        <tr class='navigroup'>
            <th colspan={{$.colspan}}>{{$.group}}</th>
        </tr>
    {{/$.group?}}
    {{$.edit?}}
        <tr>
        {{#$form.cells}}
            <td {{$.tdclass}}>{{.}}</td>
        {{/$form.cells}}
            <td class='center'>{{$form.save[0]}}{{#$form.hidden}}{{.}}{{/$form.hidden}}</td>
            <td class='center'>{{$form.button[0]}}</td>
        </tr>
    {{/$.edit?}}
    {{#if !$.edit}}
        <tr {{$.attrs}}>
        {{#$.cells}}
            <td {{$.attrs}}>{{$.cell}}</td>
        {{/$.cells}}
        </tr>
    {{/if}}
    {{/$rows}}
        <tfoot>
        <tr class='navibar'>
            <td colspan='{{$colspan}}' class='navibar'><span>{{$records}} {{$inactive?}} <label><input {{$checked}} type='checkbox' name='_action' value='showInactive' onclick='JsHttpRequest.request(this)'>Show also
                inactive</label>{{/$inactive?}}</span><span class='floatright'>{{#navbuttonsbottom}}{{.}}{{/navbuttonsbottom}}</span></td>
        </tr>
        </tfoot>
    </table>
{{$form._end}}
</div>
