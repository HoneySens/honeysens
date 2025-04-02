<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('divisions', 'create')) { %>
        <div class="pull-right">
            <button id="addDivision" type="button" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= _.t('add') %></button>
        </div>
    <% } %>
    <h3><%= _.t("accounts:divisionsHeader") %></h3>
</div>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <th><%= _.t("name") %></th>
        <th><%= _.t("users") %></th>
        <th><%= _.t("sensors") %></th>
        <th><%= _.t("actions") %></th>
        </thead>
        <tbody></tbody>
    </table>
</div>