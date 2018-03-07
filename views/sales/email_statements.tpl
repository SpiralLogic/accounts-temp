<pre><table id='table' class='grid center pad2'>
  <tr>
    <th>
      <button id='all'>All</button>
    </th>
    <th>Name</th>
    <th>Phone</th>
    <th>Balance</th>
    <th>1-30</th>
    <th>31-60</th>
    <th>61-90</th>
    <th>90+</th>
  </tr>
  {{#foreach $rows as $row}}
  <tr>
    <td class='aligncenter'><input class='email' type='checkbox' value='{{$row.debtor}}' checked/></td>
    <td class='left'><span class='bold'>{{$row.name}}</span>({{$row.email}})
    </td>
    <td>{{$row.phone}}</td>
    <td>{{$row.Balance}}</td>
    <td {{#if $row.Balance - $row.Due >0}}class="overduebg"{{/if}}>{{$row.Balance - $row.Due}}</td>
    <td {{#if $row.Due - $row.Overdue1 >0}}class="overduebg"{{/if}}>{{$row.Due - $row.Overdue1}}</td>
    <td {{#if $row.Overdue1 - $row.Overdue2 >0}}class="overduebg"{{/if}}>{{$row.Overdue1 - $row.Overdue2}}</td>
    <td {{#if $row.Overdue2 > 0}}class="overduebg"{{/if}}>{{$row.Overdue2}}</td>


  </tr>
  {{/foreach}}
  <tfoot class='bold pad5'>
  <tr>
    <td>Totals:</td>
    <td colspan=2></td>
    <td>{{$totals.balance}}</td>
    <td>{{$totals.due}}</td>
    <td>{{$totals.overdue1}}</td>
    <td>{{$totals.overdue2}}</td>
    <td>{{$totals.overdue3}}</td>
  </tr>
  </tfoot>
</table><div class='center'>
  <button id='send'>Send Emails</button>
</div>

