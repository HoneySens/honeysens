<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('divisions', 'create')) { %>
        <div class="pull-right">
            <button id="addDivision" type="button" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= t('add') %></button>
        </div>
    <% } %>
    <h3><%= t("accounts:divisionsHeader") %></h3>
</div>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <th><%= t("name") %></th>
        <th><%= t("users") %></th>
        <th><%= t("sensors") %></th>
        <th><%= t("actions") %></th>
        </thead>
        <tbody></tbody>
    </table>
</div>