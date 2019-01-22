<div class="col-sm-12">
    <div class="headerBar filters form-inline clearfix">
        <% if(_.templateHelpers.isAllowed('services', 'create')) { %>
        <div class="pull-right form-group">
            <button type="button" class="add btn btn-default btn-sm">
                <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Hinzuf&uuml;gen
            </button>
        </div>
        <div class="form-group">
            <label>Service-Registry:&nbsp;</label>
            <span class="help-block" style="display: inline-block;">Abfrage l&auml;uft...</span>
        </div>
        <% } %>
    </div>
    <div class="table-responsive"></div>
</div>