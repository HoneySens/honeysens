<div class="filters form-inline">
    <div class="form-group">
        <label>Datensatz</label>
        <div class="sourceFilter"></div>
    </div>
    <div class="form-group">
        <label>Gruppe</label>
        <div class="groupFilter"></div>
    </div>
    <div class="form-group">
        <label>Sensor</label>
        <div class="sensorFilter"></div>
    </div>
    <div class="form-group">
        <label>Klassifikation</label>
        <div class="classificationFilter"></div>
    </div>
    <div class="form-group">
      <label>Status</label>
      <div class="statusFilter"></div>
    </div>
    <div class="form-group">
        <label>Zeitraum</label>
        <div class="dateFilter"></div>
    </div>
    <div class="form-group">
        <label>Suche</label>
        <div class="eventFilter"></div>
    </div>
    <div class="form-group pull-right">
        <label style="visibility: hidden;">X</label>
        <div class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                <span class="glyphicon glyphicon-option-vertical"></span>&nbsp;<span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li class="dropdown-header"><span class="glyphicon glyphicon-export"></span>&nbsp;Export</li>
                <li><a class="exportPage">Aktuelle Seite</a></li>
                <li><a class="exportAll">Alle Seiten</a></li>
                <% if(_.templateHelpers.isAllowed('events', 'update')) { %>
                    <li role="separator" class="divider groupEditElement"></li>
                    <li class="dropdown-header groupEditElement"><span class="glyphicon glyphicon-pencil"></span>&nbsp;Bearbeiten</li>
                    <li><a class="editPage groupEditElement">Aktuelle Seite</a></li>
                    <li><a class="editAll groupEditElement">Alle Seiten</a></li>
                <% } %>
                <% if(_.templateHelpers.isAllowed('events', 'delete')) { %>
                    <li role="separator" class="divider"></li>
                    <li class="dropdown-header"><span class="glyphicon glyphicon-remove"></span>&nbsp;Entfernen</li>
                    <li><a class="removePage">Aktuelle Seite</a></li>
                    <li><a class="removeAll">Alle Seiten</a></li>
                <% } %>
            </ul>
        </div>
    </div>
</div>
<div class="table-responsive clear"></div>
<div class="selectionOptions pull-left hidden">
    <strong>Markierte Ereignisse (<span class="selectionCounter"></span>):</strong>&nbsp;&nbsp;
    <button type="button" class="massExport btn btn-default btn-xs" data-toggle="tooltip" title="Exportieren"><span class="glyphicon glyphicon-export"></span></button>
    <% if(_.templateHelpers.isAllowed('events', 'update')) { %>
        <button type="button" class="massEdit btn btn-default btn-xs" data-toggle="tooltip" title="Bearbeiten"><span class="glyphicon glyphicon-pencil"></span></button>
    <% } %>
    <% if(_.templateHelpers.isAllowed('events', 'delete') || _.templateHelpers.isAllowed('events', 'archive')) { %>
        <button type="button" class="massDelete btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen"><span class="glyphicon glyphicon-remove"></span></button>
    <% } %>
</div>
<div class="paginator pull-right"></div>
