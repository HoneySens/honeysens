<div class="filters form-inline">
    <div class="form-group">
        <label>Gruppe:&nbsp;</label>
        <div class="groupFilter" style="display: inline-block;"></div>
    </div>
    <div class="form-group">
        <label>Sensor:&nbsp;</label>
        <div class="sensorFilter" style="display: inline-block;"></div>
    </div>
    <div class="form-group">
        <label>Klassifikation:&nbsp;</label>
        <div class="classificationFilter" style="display: inline-block;"></div>
    </div>
    <div class="form-group">
        <label>Zeitraum:&nbsp;</label>
        <div class="dateFilter" style="display: inline-block;"></div>
    </div>
    <div class="form-group">
        <div class="eventFilter" style="display: inline-block;"></div>
    </div>
    <div class="form-group pull-right">
        <div class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                <span class="glyphicon glyphicon-export"></span>&nbsp;<span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li><a class="exportPage">Seite exportieren</a></li>
                <li><a class="exportAll">Alle exportieren</a></li>
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
    <% if(_.templateHelpers.isAllowed('events', 'delete')) { %>
        <button type="button" class="massDelete btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen"><span class="glyphicon glyphicon-remove"></span></button>
    <% } %>
</div>
<div class="paginator pull-right"></div>
