{{#if !$frame}}
<div id='companysearch' class='center bold font13 pad20'>
  <label for='customer'>Search Customer:&nbsp;<input name='customer' placeholder='Customer' id='customer' type='text' autofocus></label>
</div>
{{/if}}
<div>
  {{$form->start('company')}}
  {#$menu->startTab('Details', 'Customer Details','active')#}
  <div id="companyIDs" class='pad5'>
    <label for="name">Customer name:</label><input id="name" placeholder='Customer' name="name" class="big">
    <label for="id">Customer ID:</label><input id="id" readonly class="small" value='{{$debtor_id}}' name='id'>
  </div>
  <div class='formbox'>
    <div class='tablehead'>Shipping Details</div>
    <div id="branchSelect" class="center">{{$branchlist}}
      <div id="branchMenu" class="btn-group alignleft inline" style="display:none;">
        <button class="btn dropdown-toggle auto" style="float:none;" data-toggle="dropdown">Add Branch <span class="icon-caret-down"></span></button>
        <ul class="dropdown-menu">
          <li>
            <a href="#" class="addBranchBtn">Add</a></li>
          <li>
            <a href="#" class="delBranchBtn">Delete</a></li>
        </ul>
      </div>
    </div>
    {{#$form.shipping_details}}
    {{.}}
    {{/$form.shipping_details}}
    {{#$branch_postcode}}
    {{.}}
    {{/$branch_postcode}}
  </div>
  <div class='formbox'>
    <div class='tablehead'>
      Accounts Details
    </div>
    <div class='center'>
      <button id="useShipAddress" name="useShipAddress" class="button">Use shipping details</button>
    </div>
    {{#$form.accounts_details}}
    {{.}}
    {{/$form.accounts_details}}
    {{#$accounts_postcode}}
    {{.}}
    {{/$accounts_postcode}}
  </div>
  {#$menu->endTab()->startTab('Accounts', 'Accounts')#}
  <div class='formbox'>
    <div class='tablehead'>
      Accounts Details
      {{$form.accounts_id}}
    </div>
    {{#$form.accounts}}
    {{.}}
    {{/$form.accounts}}
  </div>
  <div class='formbox width35'>
    <div class='tablehead'>
      Contact Log:
    </div>
    <div class='center'>
      <button id="addLog" name="_action" value="addLog" class="button">Add log entry</button>
    </div>
    {{$form.messageLog}}
  </div>
  {#$menu->endTab()->startTab('Customer Contacts', 'Customer Contacts')#}
  {{>contacts/contact}}
  {#$menu->endTab()->startTab('Extra Shipping Info', 'Extra Info')#}
  <div class='formbox'>
    <div class='tablehead'>
      Accounts Details
      {{$form.branch_id}}
    </div>
    {{$form.branch-salesman}}
    {{$form.branch-area}}
    {{$form.branch-group_no}}
    {{$form.branch-default_location}}
    {{$form.branch-default_ship_via}}
    {{$form.branch-tax_group_id}}
    {{$form.branch-disable_trans}}
    {{$form.webid}}
  </div>
  <div class='formbox'>
    <div class='tablehead'>
      GL Accounts
    </div>
    {{$form.branch-sales_account}}
    {{$form.branch-sales_discount_account}}
    {{$form.branch-receivables_account}}
    {{$form.branch-payment_discount_account}}
    {{$form.branch_notes}}
  </div>
  {#$menu->endTab()->startTab('Invoices', 'Invoices')#}
  {{$form.frame}}
  <div id='invoiceFrame' data-src='/sales/inquiry/customer_allocation_inquiry.php?debtor_id={{$debtor_id}}'></div>
  {{$form._focus}}
  {#$menu->endTab()->render()#}
  {{$form->end()}}
</div>
<div class='center clearleft pad20'>
  <button id="btnNew" name="_action" value="{{ADD}}" type="submit" class="btn btn-primary">New</button>
  <button id="btnCancel" name="_action" value="{{CANCEL}}" type="submit" class="btn btn-danger ui-helper-hidden"><i class="{{ICON_TRASH}}"></i> Cancel</button>
  <button id="btnConfirm" name="_action" value="{{SAVE}}" type="submit" class="btn btn-success ui-helper-hidden"><i class="{{ICON_OK}}"></i> Save</button>
</div>
<div id="shortcuts" class="center">{{#$shortcuts}}
  <button {{HTML::attr($.attrs)}} data-url="{{$.data}}">{{$.caption}}</button>
  {{/$shortcuts}}</div>
{{$contact_form->start('contact')}}
<div id="contactLog" class='ui-helper-hidden center'>
  <div class="formbox marginauto ">
    {{$contact_form.contact_name}}<br>
    {{$contact_form.message}}
    {{$contact_form.type}}
  </div>
</div>
{{$contact_form->end()}}
