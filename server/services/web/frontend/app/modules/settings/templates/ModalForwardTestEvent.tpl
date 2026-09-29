<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= t("settings:syslogTestSentHeader") %></h4>
        </div>
        <div class="modal-body">
            <dl class="dl-horizontal label-left">
                <dt><%= t("id") %></dt>
                <dd><%- id %></dd>
                <dt><%= t("timestamp") %></dt>
                <dd><%- showTimestamp() %></dd>
                <dt><%= t("sensor") %></dt>
                <dd><%- sensor_name %> (<%= t("id") %> <%- sensor_id %>)</dd>
                <dt><%= t("settings:syslogTestSentSource") %></dt>
                <dd><%- source %></dd>
                <dt><%= t("details") %></dt>
                <dd><%- summary %></dd>
            </dl>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal" autofocus><%= t("close") %></button>
        </div>
    </div>
</div>