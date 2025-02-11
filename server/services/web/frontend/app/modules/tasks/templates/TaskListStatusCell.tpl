<% if(status == _.templateHelpers.getModels().Task.status.SCHEDULED) { %>
    <span class="glyphicon glypicon-time"></span>&nbsp;&nbsp;<%= _.t("tasks:statusScheduled") %>
<% } else if(status == _.templateHelpers.getModels().Task.status.RUNNING) { %>
    <span class="glyphicon glyphicon-cog"></span>&nbsp;&nbsp;<%= _.t("tasks:statusRunning") %>
<% } else if(status == _.templateHelpers.getModels().Task.status.DONE) { %>
    <span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;<%= _.t("tasks:statusDone") %>
<% } else if(status == _.templateHelpers.getModels().Task.status.ERROR) { %>
    <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<%= _.t("tasks:statusError") %>
<% } %>