<div class="headerBar">
    <div class="pull-right">
        <button type="button" class="add btn btn-default btn-sm">
            <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<%= _.t("add") %>
        </button>
    </div>
    <h3><%= _.t("events:filterConditionsHeader") %></h3>
</div>
<div class="table-responsive">
    <table class="table">
        <thead>
            <th><%= _.t("attribute") %></th>
            <th><%= _.t("type") %></th>
            <th><%= _.t("value") %></th>
            <th><%= _.t("actions") %></th>
        </thead>
        <tbody></tbody>
    </table>
</div>