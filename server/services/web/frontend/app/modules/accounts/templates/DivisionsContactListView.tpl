<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('contacts', 'create')) { %>
    <div class="pull-right">
        <button type="button" class="add btn btn-default btn-sm">
            <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= _.t("add") %>
        </button>
    </div>
    <% } %>
    <h3><%= _.t("accounts:emailNotifications") %></h3>
</div>
<div class="table-responsive">
    <table class="table">
        <thead>
            <th><%= _.t("type") %></th>
            <th><%= _.t("contact") %></th>
            <th></th>
            <% if(_.templateHelpers.isAllowed('contacts', 'update')) { %><th><%= _.t("actions") %></th><% } %>
        </thead>
        <tbody></tbody>
    </table>
</div>