<div class="headerBar">
    <% if(_.templateHelpers.isAllowed('divisions', 'create')) { %>
        <div class="pull-right">
            <button id="addDivision" type="button" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Hinzuf&uuml;gen</button>
        </div>
    <% } %>
    <h3>Gruppen</h3>
</div>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <th>Name</th>
        <th>Benutzer</th>
        <th>Sensoren</th>
        <th>Aktionen</th>
        </thead>
        <tbody></tbody>
    </table>
</div>