<div class="col-sm-12">
    <div class="headerBar filters form-inline">
        <% if(_.templateHelpers.isAllowed('sensors', 'create')) { %>
        <div class="pull-right form-group">
            <button type="button" class="toggleServiceEdit btn btn-default btn-sm" <% if(_.templateHelpers.isAllowed('sensors', 'update') == false) { %>disabled<% } %>>
            <span class="glyphicon glyphicon-lock"></span>&nbsp;&nbsp;Dienste bearbeiten
            </button>
            <button type="button" class="add btn btn-default btn-sm" <% if(!hasDivision()) { %>disabled<% } %>>
                <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Hinzuf&uuml;gen
            </button>
        </div>
        <% } %>
        <div class="form-group">
            <label>Gruppe:&nbsp;</label>
            <div class="groupFilter" style="display: inline-block;"></div>
        </div>
    </div>
    <div class="table-responsive"></div>
</div>