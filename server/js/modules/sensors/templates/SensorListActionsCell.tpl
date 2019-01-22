<button type="button" class="showStatus btn btn-default btn-xs" data-toggle="tooltip" title="Letzte Statusmeldungen">
    <span class="glyphicon glyphicon-list"></span>
</button>
<% if(_.templateHelpers.isAllowed('sensors', 'update')) { %>
    <button type="button" class="editSensor btn btn-default btn-xs" data-toggle="tooltip" title="Bearbeiten">
        <span class="glyphicon glyphicon-pencil"></span>
    </button>
    <button type="button" class="removeSensor btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
<% } %>