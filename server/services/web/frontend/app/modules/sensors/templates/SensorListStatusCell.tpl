<% if(last_status == _.templateHelpers.getModels().SensorStatus.status.TIMEOUT) { %>
    <span class="glyphicon glyphicon-warning-sign"></span><%= _.t("sensors:sensorStatusTimeout") %>
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.RUNNING) { %>
    <span class="glyphicon glyphicon-ok"></span><%= _.t("sensors:sensorStatusRunning") %>
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.UPDATING) { %>
    <span class="glyphicon glyphicon-arrow-up"></span><%= _.t("sensors:sensorStatusUpdating") %>
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.ERROR) { %>
    <span class="glyphicon glyphicon-remove"></span><%= _.t("sensors:sensorStatusError") %>
<% } else { %>
    <span class="glyphicon glyphicon-question-sign"></span><%= _.t("sensors:sensorStatusNew") %>
<% } %>
<% if(last_status_ts) { %>
    &nbsp;(<%- showLastStatusTS() %>)
<% } %>