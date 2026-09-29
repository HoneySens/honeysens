<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('contacts', 'create')) { %>
    <div class="pull-right">
        <button type="button" class="add btn btn-default btn-sm">
            <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= t("add") %>
        </button>
    </div>
    <% } %>
    <h3><%= t("accounts:emailNotifications") %></h3>
</div>
<div class="table-responsive">
    <table class="table">
        <thead>
            <th><%= t("type") %></th>
            <th><%= t("contact") %></th>
            <th></th>
            <% if(_.templateHelpers.isAllowed('contacts', 'update')) { %><th><%= t("actions") %></th><% } %>
        </thead>
        <tbody></tbody>
    </table>
</div>