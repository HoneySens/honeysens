<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('contacts', 'create')) { %>
    <div class="pull-right">
        <button type="button" class="add btn btn-default btn-sm">
            <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Hinzuf&uuml;gen
        </button>
    </div>
    <% } %>
    <h3>Kontakte bei Ereignissen</h3>
</div>
<div class="table-responsive">
    <table class="table">
        <thead>
            <th>Typ</th>
            <th>Kontakt</th>
            <th>Nachricht f&uuml;r</th>
            <% if(_.templateHelpers.isAllowed('contacts', 'update')) { %><th>Aktionen</th><% } %>
        </thead>
        <tbody></tbody>
    </table>
</div>