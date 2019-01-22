<td><%- name %></td>
<td><%- getUserCount() %></td>
<td><%- getSensorCount() %></td>
<td>
    <% if(_.templateHelpers.isAllowed('divisions', 'update')) { %>
        <button type="button" class="edit btn btn-default btn-xs" data-toggle="tooltip" title="Bearbeiten">
            <span class="glyphicon glyphicon-pencil"></span>
        </button>
    <% } %>
    <% if(_.templateHelpers.isAllowed('divisions', 'delete')) { %>
        <button type="button" class="remove btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen">
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    <% } %>
</td>