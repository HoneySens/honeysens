<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('users', 'create')) { %>
        <div class="pull-right">
            <button id="addUser" type="button" class="btn btn-default btn-sm">
                <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Hinzuf&uuml;gen
            </button>
        </div>
    <% } %>
    <h3>Benutzer</h3>
</div>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <th>ID</th>
        <th>Name</th>
        <th>Rolle</th>
        <% if(_.templateHelpers.isAllowed('users', 'update')) { %><th>Aktionen</th><% } %>
        </thead>
        <tbody></tbody>
    </table>
</div>