<div class='center'>
  <div id="Contacts" style='min-height:200px'>
    <script id="contact_tmpl" type='text/x-jquery-tmpl'>
      <table id="contact-${_k}" style="display:inline-block">
        <tr>
          <td class="tablehead" colspan="2">${name}</td>
        </tr>
        <tr>
          <td class='label'><label for='contact[name-${_k}]'>Name:</label></td>
          <td><input type="text" name="contact[name-${_k}]" id="contact[name-${_k}]" size='35' maxlength="40" value="${name}"></td>
        </tr>
        <tr>
          <td class='label'><label for='contact[phone1-${_k}]'>Phone:</label></td>
          <td><input type="text" name="contact[phone1-${_k}]" id="contact[phone1-${_k}]" size='35' maxlength="40" value="${phone1}"></td>
        </tr>
        <tr>
          <td class='label'><label for='contact[phone2-${_k}]'>Phone2:</label></td>
          <td><input type="text" name="contact[phone2-${_k}]" id="contact[phone2-${_k}]" size='35' maxlength="40" value="${phone2}"></td>
        </tr>
        <tr>
          <td class='label'><label for='contact[email-${_k}]'>Email:</label></td>
          <td><input type="text" name="contact[email-${_k}]" id="contact[email-${_k}]" size='35' maxlength="40" value="${email}"></td>
        </tr>
        <tr>
          <td class='label'><label for='contact[department-${_k}]'>Dept:</label></td>
          <td><input type="text" name="contact[department-${_k}]" id="contact[department-${_k}]" size='35' maxlength="40" value="${department}"></td>
        </tr>
      </table>
    </script>
  </div>
</div>
