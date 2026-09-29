<button type="button" class="showDetails btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('details') %>">
    <span class="glyphicon glyphicon-search"></span>
</button>
<% if(_.templateHelpers.isAllowed('services', 'update')) { %>
    <button type="button" class="removeService btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('remove') %>">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
<% } %>