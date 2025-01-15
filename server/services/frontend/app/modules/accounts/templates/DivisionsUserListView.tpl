<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('users', 'create')) { %>
    <div class="pull-right dropdown">
        <button id="addUser" type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>&nbsp;&nbsp;Hinzuf&uuml;gen
        </button>
        <ul class="dropdown-menu"></ul>
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