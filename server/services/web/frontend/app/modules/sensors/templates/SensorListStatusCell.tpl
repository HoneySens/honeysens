<% if(last_status == SensorStatusFlag.TIMEOUT) { %>
    <span class="glyphicon glyphicon-warning-sign"></span><%= t("sensors:sensorStatusTimeout") %>
<% } else if(last_status == SensorStatusFlag.RUNNING) { %>
    <span class="glyphicon glyphicon-ok"></span><%= t("sensors:sensorStatusRunning") %>
<% } else if(last_status == SensorStatusFlag.UPDATING) { %>
    <span class="glyphicon glyphicon-arrow-up"></span><%= t("sensors:sensorStatusUpdating") %>
<% } else if(last_status == SensorStatusFlag.ERROR) { %>
    <span class="glyphicon glyphicon-remove"></span><%= t("sensors:sensorStatusError") %>
<% } else { %>
    <span class="glyphicon glyphicon-question-sign"></span><%= t("sensors:sensorStatusNew") %>
<% } %>
<% if(last_status_ts) { %>
    &nbsp;(<%- showLastStatusTS() %>)
<% } %>