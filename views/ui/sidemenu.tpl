<div id="sidemenu" class="ui-widget-shadow ui-corner-all">
    <h3><a href="#">Search</a></h3>

    <div id="search">
        <ul>
            <li id="orders" data-href="/sales/search/orders?">Orders/Quotes</li>
            <li id="invoices" data-href="/sales/search/transactions?">Invoice/Delivery</li>
            <li id="purchaseOrders" data-href="/purchases/search/completed?">Purchase Order</li>
            <li id="supplierInvoices" data-href="/purchases/search/transactions?">Supplier Invoices</li>
        </ul>
    </div>
    <h3><a href="#">Create</a></h3>

    <div>
        <ul>
            <li><a href="/sales/order?add=0&amp;type={{ ST_SALESQUOTE }}">Quote</a></li>
            <li><a href="/sales/order?add=0&amp;type={{ ST_SALESORDER }}">Order</a></li>
            <li><a href="/sales/order?add=0&amp;type={{ ST_SALESINVOICE }}">Direct Invoice</a></li>
            <li><a href="/purchases/order?New=0">Purchase Order</a></li>
        </ul>
    </div>
{{#if $bank}}
    <h3><a href="#">Banking</a></h3>

    <div>
        <ul>
            <li><a href="/banking/banking?NewPayment=Yes">Payment</a></li>
            <li><a href="/banking/banking?NewDeposit=Yes">Deposit</a></li>
            <li><a href="/banking/reconcile?">Reconcile</a></li>
        </ul>
    </div>
{{/if}}
    <h3><a href="#">Customer Search</a></h3>

    <div>
        <input class="width85" id="quickCustomer"/>
    </div>
    <h3><a href="#">Supplier Search</a></h3>

    <div>
        <input class="width85" id="quickSupplier"/>
    </div>
    <!-- end sidemenu div-->
</div>
