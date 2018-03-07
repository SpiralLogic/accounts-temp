{{#if !$frame}}
<div id='companysearch' class='center bold font13 pad20'>
  <label for='supplier'>Search Supplier:&nbsp;<input name='supplier' id='supplier' type='text' autofocus></label>
</div>
{{/if}}
<div>{{$form->start()}}
  {#$menu->startTab('Details', 'Supplier Details','active')#}
  <div id="companyIDs">
    <label for="name">Supplier name:</label><input id="name" name="name" class="big">
    <label for="id">Supplier ID:</label><input id="id" name="id" class="small" maxlength="7">
  </div>
  <div class='formbox '>
    <div class='tablehead'>
      Shipping Details
    </div>
    {{$form.contact}}
    {{$form.phone}}
    {{$form.fax}}
    {{$form.email}}
    {{$form.address}}
    {{#$postcode}}
    {{.}}
    {{/$postcode}}
  </div>
  <div class='formbox '>
    <div class='tablehead'>
      Accounts Details
    </div>
    <div class='center'>
      <button id="useShipAddress" name="useShipAddress" class="button">Use shipping details</button>
    </div>
    {{$form.supp_phone}}
    {{$form.supp_address}}
    {{#$supp_postcode}}
    {{.}}
    {{/$supp_postcode}}
  </div>
  {#$menu->endTab()->startTab('Accounts', 'Accounts')#}
  <div class='formbox '>
    <div class='tablehead'>
      Accounts Details
    </div>
    {{$form.payment_discount}}
    {{$form.credit_limit}}
    {{$form.account_no}}
    {{$form.tax_id}}
    {{$form.tax_group_id}}
    {{$form.inactive}}
    {{$form.curr_code}}
    {{$form.payment_terms}}
    {{$form.payable_account}}
    {{$form.payment_discount_account}}
  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Contact Log:
    </div>
    <div class='center'>
      <button id="addLog" name="addLog" class="button">Add log entry</button>
      <br> {{$form.messageLog}}
    </div>
  </div>
  {#$menu->endTab()->startTab('Supplier Contacts', 'Supplier Contacts')#}
  {{>contacts/contact}}
  {#$menu->endTab()->startTab('Invoices', 'Invoices')#}
  <div id='invoiceFrame' data-src='/purchases/search/allocations?creditor_id={{$creditor_id}}'></div>
  {{$form.frame}}
  {#$menu->endTab()->render()#}
  {{$form->end()}}
</div>
<div class='center clearleft pad20'>
  <button id="btnNew" name="_action" value="{{ADD}}" type="submit" class="btn btn-primary">New</button>
  <button id="btnCancel" name="_action" value="{{CANCEL}}" type="submit" class="btn btn-danger ui-helper-hidden"><i class="{{ICON_TRASH}}"></i> Cancel</button>
  <button id="btnConfirm" name="_action" value="{{SAVE}}" type="submit" class="btn btn-success ui-helper-hidden"><i class="{{ICON_OK}}"></i> Save</button>
</div>
<div id="shortcuts" class="center">{{#$shortcuts}}
  <button class="btn" data-url="{{$.data}}">{{$.caption}}</button>
  {{/$shortcuts}}</div>
{{$contact_form->start()}}
<div id="contactLog" class='ui-helper-hidden center'>
  <div class="formbox marginauto ">
    {{$contact_form.contact_name}}<br>
    {{$contact_form.message}}
    {{$contact_form.type}}
  </div>
</div>
{{$contact_form->end()}}
