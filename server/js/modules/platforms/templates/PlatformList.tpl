<div class="col-sm-12">
    <div class="headerBar form-inline clearfix">
        <% if(_.templateHelpers.isAllowed('platforms', 'create')) { %>
        <div class="pull-right form-group">
            <button type="button" class="add btn btn-default btn-sm">
                <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Firmware Hinzuf&uuml;gen
            </button>
        </div>
        <% } %>
    </div>
    <div class="table-responsive"></div>
</div>