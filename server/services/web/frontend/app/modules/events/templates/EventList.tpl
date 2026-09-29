<div class="filters form-inline">
    <div class="form-group">
        <label><%= t("events:eventListFilterDataset") %></label>
        <div class="sourceFilter"></div>
    </div>
    <div class="form-group">
        <label><%= t("events:eventListFilterDivision") %></label>
        <div class="groupFilter"></div>
    </div>
    <div class="form-group">
        <label><%= t("events:eventListFilterSensor") %></label>
        <div class="sensorFilter"></div>
    </div>
    <div class="form-group">
        <label><%= t("events:eventListFilterClassification") %></label>
        <div class="classificationFilter"></div>
    </div>
    <div class="form-group">
      <label><%= t("events:eventListFilterStatus") %></label>
      <div class="statusFilter"></div>
    </div>
    <div class="form-group">
        <label><%= t("events:eventListFilterTimePeriod") %></label>
        <div class="dateFilter"></div>
    </div>
    <div class="form-group">
        <label><%= t("events:eventListFilterSearch") %></label>
        <div class="eventFilter"></div>
    </div>
    <div class="form-group pull-right">
        <label style="visibility: hidden;">X</label>
        <div class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                <span class="glyphicon glyphicon-option-vertical"></span>&nbsp;<span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li class="dropdown-header"><span class="glyphicon glyphicon-export"></span>&nbsp;<%= t("export") %></li>
                <li><a class="exportPage"><%= t("events:eventCurrentPage") %></a></li>
                <li><a class="exportAll"><%= t("events:eventAllPages") %></a></li>
                <% if(_.templateHelpers.isAllowed('events', 'update')) { %>
                    <li role="separator" class="divider groupEditElement"></li>
                    <li class="dropdown-header groupEditElement"><span class="glyphicon glyphicon-pencil"></span>&nbsp;<%= t("update") %></li>
                    <li><a class="editPage groupEditElement"><%= t("events:eventCurrentPage") %></a></li>
                    <li><a class="editAll groupEditElement"><%= t("events:eventAllPages") %></a></li>
                <% } %>
                <% if(_.templateHelpers.isAllowed('events', 'delete')) { %>
                    <li role="separator" class="divider"></li>
                    <li class="dropdown-header"><span class="glyphicon glyphicon-remove"></span>&nbsp;<%= t("remove") %></li>
                    <li><a class="removePage"><%= t("events:eventCurrentPage") %></a></li>
                    <li><a class="removeAll"><%= t("events:eventAllPages") %></a></li>
                <% } %>
            </ul>
        </div>
    </div>
</div>
<div class="table-responsive clear"></div>
<div class="selectionOptions pull-left hidden">
    <strong><%= t("events:eventSelectedEvents") %> (<span class="selectionCounter"></span>):</strong>&nbsp;&nbsp;
    <button type="button" class="massExport btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('export') %>"><span class="glyphicon glyphicon-export"></span></button>
    <% if(_.templateHelpers.isAllowed('events', 'update')) { %>
        <button type="button" class="massEdit btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('update') %>"><span class="glyphicon glyphicon-pencil"></span></button>
    <% } %>
    <% if(_.templateHelpers.isAllowed('events', 'delete') || _.templateHelpers.isAllowed('events', 'archive')) { %>
        <button type="button" class="massDelete btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('remove') %>"><span class="glyphicon glyphicon-remove"></span></button>
    <% } %>
</div>
<div class="paginator pull-right"></div>
