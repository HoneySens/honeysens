<div class="col-sm-12">
    <div class="headerBar filters form-inline clearfix">
        <% if(_.templateHelpers.isAllowed('services', 'create')) { %>
        <div class="pull-right form-group">
            <button type="button" class="add btn btn-default btn-sm">
                <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= _.t("add") %>
            </button>
        </div>
        <div class="form-group">
            <label><%= _.t("services:serviceStatus") %>:&nbsp;</label>
            <span class="help-block" style="display: inline-block;"><%= _.t("services:statusQuery") %></span>
        </div>
        <% } %>
    </div>
    <div class="table-responsive"></div>
</div>