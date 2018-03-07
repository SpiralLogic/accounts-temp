<div id="itemSearch" class='bold pad10 center'>
{{$searchBox}}
</div>
 {{$form._start}}
{#$menu->startTab('Items', 'Items','active')#}
<div id="Items" class="width40 formbox">

{{#$form.items}}
  {{.}}
  {{/$form.items}}
</div>
<div class="center">
    <table id="stockLevels" class="marginauto grid width80" style="display:none">
        <thead>
        <tr>
            <th>Location</th>
            <th>QOH</th>
            <th>Shelf Primary</th>
            <th>Shelf Secondary</th>
            <th>Reorder Level</th>
            <th>On SalesOrder</th>
            <th>Available</th>
            <th>On PurchaseOrder</th>
        </tr>
        </thead>
        <tbody id="stockRow">
        <tr>
            <td>${location_name}</td>
            <td>${qty}</td>
            <td>${shelf_primary}</td>
            <td>${shelf_secondary}</td>
            <td>${reorder_level}</td>
            <td>${demand}</td>
            <td>${available}</td>
            <td>${onorder}</td>
        </tr>
        </tbody>
    </table>
</div>
{#$menu->endTab()->startTab('Accounts', 'Accounts','default')#}
<div id="Accounts" class="formbox width40">
{{#$form.accounts}}
  {{.}}
  {{/$form.accounts}}

</div>

{#$menu->endTab()->startTab('Selling', 'Selling')#}
{{$sellprices}}
{#$menu->endTab()->startTab('Purchasing', 'Purchasing')#}
{{$buyprices}}
{#$menu->endTab()->startTab('Locations', 'Locations')#}
{{$reorderlevels}}
{#$menu->endTab()->startTab('Website', 'Website')#}
<iframe id='webFrame' data-srcpre='{{Config::_get('modules.webstore')['product_url']}}' data-srcpost='{{Config::_get('modules.webstore')['url_extension']}}'
        style='width:100%'
        height='500' frameborder='0'></iframe>
{#$menu->endTab()->render()#}
<div class="center">
{{#$form.buttons}}
  {{.}}
  {{/$form.buttons}}
</div>
{{$form._end}}
