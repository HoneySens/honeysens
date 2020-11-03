<button type="button" class="editStatus pull-right btn btn-default btn-xs"
    <% if(!_.templateHelpers.isAllowed('events', 'update')) { %>disabled="disabled"<% } %>>
    <span class="glyphicon glyphicon-pencil"></span>
</button>
<% if(status == _.templateHelpers.getModels().Event.status.UNEDITED) { %>Neu
<% } else if(status == _.templateHelpers.getModels().Event.status.BUSY) { %>In Bearbeitung
<% } else if(status == _.templateHelpers.getModels().Event.status.RESOLVED) { %>Erledigt
<% } else if(status == _.templateHelpers.getModels().Event.status.IGNORED) { %>Ignoriert
<% } else { %>Ung&uuml;ltig<% } %>
<div class="popover">
    <div class="popover-content">
        <strong>Status:</strong>&nbsp;
        <span>
            <% if(status == _.templateHelpers.getModels().Event.status.UNEDITED) { %>Neu<% } %>
            <% if(status == _.templateHelpers.getModels().Event.status.BUSY) { %>In Bearbeitung<% } %>
            <% if(status == _.templateHelpers.getModels().Event.status.RESOLVED) { %>Erledigt<% } %>
            <% if(status == _.templateHelpers.getModels().Event.status.IGNORED) { %>Ignoriert<% } %>
        </span>
        <br />
        <strong>Kommentar:</strong>&nbsp;<p><%- comment %></p>
    </div>
</div>