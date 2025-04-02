<div class="panel-heading">
    <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#detailLists" href="#packetList"><%= _.t("events:eventPacketHeader") %> (<%- showModelCount() %>)</a>
    </h4>
</div>
<div id="packetList" class="panel-collapse collapse">
    <div class="panel-body">
        <table class="table table-striped">
            <thead>
            <th><%= _.t("time") %></th>
            <th><%= _.t("protocol") %></th>
            <th><%= _.t("port") %></th>
            <th><%= _.t("events:eventPacketFlags") %></th>
            <th><%= _.t("events:eventPacketPayload") %></th>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
