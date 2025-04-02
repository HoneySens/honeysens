<td><%- id %></td>
<td><%- name %></td>
<td><%- email %></td>
<td><%- showRole() %></td>
<% if(_.templateHelpers.isAllowed('users', 'update')) { %>
<td>
    <button type="button" class="editUser btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('update') %>">
        <span class="glyphicon glyphicon-pencil"></span>
    </button>
    <% if(id != 1 && !isLoggedIn()) { %>
    <button type="button" class="removeUser btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('remove') %>">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
    <% } %>
</td>
<% } %>