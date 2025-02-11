<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('users', 'create')) { %>
    <div class="pull-right dropdown">
        <button id="addUser" type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>&nbsp;&nbsp;<%= _.t("add") %>
        </button>
        <ul class="dropdown-menu"></ul>
    </div>
    <% } %>
    <h3><%= _.t("users") %></h3>
</div>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <th><%= _.t("id") %></th>
            <th><%= _.t("name") %></th>
            <th><%= _.t("accounts:role") %></th>
            <% if(_.templateHelpers.isAllowed('users', 'update')) { %><th><%= _.t("actions") %></th><% } %>
        </thead>
        <tbody></tbody>
    </table>
</div>