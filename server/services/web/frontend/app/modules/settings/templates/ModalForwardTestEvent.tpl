<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= _.t("settings:syslogTestSentHeader") %></h4>
        </div>
        <div class="modal-body">
            <dl class="dl-horizontal label-left">
                <dt><%= _.t("id") %></dt>
                <dd><%- id %></dd>
                <dt><%= _.t("timestamp") %></dt>
                <dd><%- showTimestamp() %></dd>
                <dt><%= _.t("sensor") %></dt>
                <dd><%- sensor_name %> (<%= _.t("id") %> <%- sensor_id %>)</dd>
                <dt><%= _.t("settings:syslogTestSentSource") %></dt>
                <dd><%- source %></dd>
                <dt><%= _.t("details") %></dt>
                <dd><%- summary %></dd>
            </dl>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal" autofocus><%= _.t("close") %></button>
        </div>
    </div>
</div>