<div class="col-sm-12">
    <div class="headerBar filters form-inline">
        <div class="pull-right form-group">
            <button type="button" class="add btn btn-default btn-sm" <% if(!hasDivision()) { %>disabled<% } %>>
                <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= t("add") %>
            </button>
        </div>
        <div class="form-group">
            <label><%= t("events:filterListFilterDivision") %>:&nbsp;</label>
            <div class="groupFilter" style="display: inline-block;"></div>
        </div>
    </div>
    <div class="table-responsive"></div>
</div>
