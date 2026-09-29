<div class="panel-heading">
    <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#detailLists" href="#interactionList"><%= t("events:eventInteractionHeader") %> (<%- showModelCount() %>)</a>
    </h4>
</div>
<div id="interactionList" class="panel-collapse collapse">
    <div class="panel-body">
        <table class="table table-striped">
            <thead>
            <th><%= t("time") %></th>
            <th><%= t("actions") %></th>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
