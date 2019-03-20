<% if(isTimedOut()) { %>
    <span class="glyphicon glyphicon-warning-sign"></span>&nbsp;&nbsp;Timeout
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.RUNNING) { %>
    <span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;Online
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.UPDATING) { %>
    <span class="glyphicon glyphicon-arrow-up"></span>&nbsp;&nbsp;Update
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.ERROR) { %>
    <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;Fehler
<% } %>
<% if(last_status_ts) { %>
    &nbsp;(<%- showLastStatusTS() %>)
<% } %>