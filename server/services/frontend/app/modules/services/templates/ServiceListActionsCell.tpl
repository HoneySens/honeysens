<button type="button" class="showDetails btn btn-default btn-xs" data-toggle="tooltip" title="Details">
    <span class="glyphicon glyphicon-search"></span>
</button>
<% if(_.templateHelpers.isAllowed('services', 'update')) { %>
    <button type="button" class="removeService btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
<% } %>