<button type="button" class="showEvent btn btn-default btn-xs" data-toggle="tooltip" title="Details anzeigen">
    <span class="glyphicon glyphicon-list-alt"></span>
</button>
<% if(_.templateHelpers.isAllowed('events', 'delete')) { %>
<button type="button" class="removeEvent btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen">
    <span class="glyphicon glyphicon-remove"></span>
</button>
<% } %>