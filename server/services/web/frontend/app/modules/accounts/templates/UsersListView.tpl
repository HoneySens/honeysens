<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('users', 'create')) { %>
        <div class="pull-right">
            <button id="addUser" type="button" class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= t("add") %>
            </button>
        </div>
    <% } %>
    <h3><%= t("users") %></h3>
</div>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <th><%= t("id") %></th>
        <th><%= t("accounts:userLogin") %></th>
        <th><%= t("accounts:userEMail") %></th>
        <th><%= t("accounts:role") %></th>
        <% if(_.templateHelpers.isAllowed('users', 'update')) { %><th><%= t("actions") %></th><% } %>
        </thead>
        <tbody></tbody>
    </table>
</div>