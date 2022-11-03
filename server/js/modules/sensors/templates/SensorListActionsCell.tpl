<% if(_.templateHelpers.isAllowed('sensors', 'update')) { %>
    <button type="button" class="editSensor btn btn-default btn-xs" data-toggle="tooltip" title="Bearbeiten">
        <span class="glyphicon glyphicon-pencil"></span>
    </button>
<% } %>
<% if(_.templateHelpers.isAllowed('sensors', 'delete')) { %>
    <button type="button" class="removeSensor btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
<% } %>
<div class="dropdown" style="display: inline-block">
    <button class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown">
        <span class="glyphicon glyphicon-option-vertical"></span>&nbsp;<span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <li><a class="showStatus"><span class="glyphicon glyphicon-list"></span>&nbsp;Letzte Statusmeldungen</a></li>
        <% if(_.templateHelpers.isAllowed('sensors', 'downloadConfig')) { %>
            <li><a class="downloadConfig"><span class="glyphicon glyphicon-download"></span>&nbsp;Konfiguration herunterladen</a></li>
        <% } %>
    </ul>
</div>
