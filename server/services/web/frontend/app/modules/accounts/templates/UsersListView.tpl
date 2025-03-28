<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('users', 'create')) { %>
        <div class="pull-right">
            <button id="addUser" type="button" class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= _.t("add") %>
            </button>
        </div>
    <% } %>
    <h3><%= _.t("users") %></h3>
</div>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <th><%= _.t("id") %></th>
        <th><%= _.t("accounts:userLogin") %></th>
        <th><%= _.t("accounts:userEMail") %></th>
        <th><%= _.t("accounts:role") %></th>
        <% if(_.templateHelpers.isAllowed('users', 'update')) { %><th><%= _.t("actions") %></th><% } %>
        </thead>
        <tbody></tbody>
    </table>
</div>