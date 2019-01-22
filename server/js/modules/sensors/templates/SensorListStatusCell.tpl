<% if(isTimedOut()) { %>
    <span class="glyphicon glyphicon-warning-sign"></span>&nbsp;&nbsp;Timeout
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.RUNNING) { %>
    <span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;Online
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.UPDATE_PHASE1) { %>
    <span class="glyphicon glyphicon-arrow-up"></span>&nbsp;&nbsp;Update, Phase 1
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.INSTALL_PHASE1) { %>
    <span class="glyphicon glyphicon-arrow-up"></span>&nbsp;&nbsp;Install, Phase 1
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.UPDATEINSTALL_PHASE2) { %>
    <span class="glyphicon glyphicon-arrow-up"></span>&nbsp;&nbsp;Install/Update, Phase 2
<% } else if(last_status == _.templateHelpers.getModels().SensorStatus.status.ERROR) { %>
    <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;Fehler
<% } %>
<% if(last_status_ts) { %>
    &nbsp;(<%- showLastStatusTS() %>)
<% } %>