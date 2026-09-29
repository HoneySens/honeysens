<div class="panel-heading">
    <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#detailLists" href="#packetList"><%= t("events:eventPacketHeader") %> (<%- showModelCount() %>)</a>
    </h4>
</div>
<div id="packetList" class="panel-collapse collapse">
    <div class="panel-body">
        <table class="table table-striped">
            <thead>
            <th><%= t("time") %></th>
            <th><%= t("protocol") %></th>
            <th><%= t("port") %></th>
            <th><%= t("events:eventPacketFlags") %></th>
            <th><%= t("events:eventPacketPayload") %></th>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
