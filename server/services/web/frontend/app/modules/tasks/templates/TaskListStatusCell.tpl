<% if(status == TaskStatus.SCHEDULED) { %>
    <span class="glyphicon glypicon-time"></span><%= t("tasks:statusScheduled") %>
<% } else if(status == TaskStatus.RUNNING) { %>
    <span class="glyphicon glyphicon-cog"></span><%= t("tasks:statusRunning") %>
<% } else if(status == TaskStatus.DONE) { %>
    <span class="glyphicon glyphicon-ok"></span><%= t("tasks:statusDone") %>
<% } else if(status == TaskStatus.ERROR) { %>
    <span class="glyphicon glyphicon-remove"></span><%= t("tasks:statusError") %>
<% } %>