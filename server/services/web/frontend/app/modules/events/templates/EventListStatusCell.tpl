<button type="button" class="editStatus pull-right btn btn-default btn-xs"
    <% if(!_.templateHelpers.isAllowed('events', 'update') || archived) { %>disabled="disabled"<% } %>>
    <span class="glyphicon glyphicon-pencil"></span>
</button>
<% if(status == _.templateHelpers.getModels().Event.status.UNEDITED) { %><%= _.t("events:eventStatusUnedited") %>
<% } else if(status == _.templateHelpers.getModels().Event.status.BUSY) { %><%= _.t("events:eventStatusBusy") %>
<% } else if(status == _.templateHelpers.getModels().Event.status.RESOLVED) { %><%= _.t("events:eventStatusResolved") %>
<% } else if(status == _.templateHelpers.getModels().Event.status.IGNORED) { %><%= _.t("events:eventStatusIgnored") %>
<% } else { %><%= _.t("events:eventStatusInvalid") %><% } %>
<div class="popover">
    <div class="popover-content">
        <strong><%= _.t("events:eventStatus") %>:</strong>&nbsp;
        <span>
            <% if(status == _.templateHelpers.getModels().Event.status.UNEDITED) { %><%= _.t("events:eventStatusUnedited") %><% } %>
            <% if(status == _.templateHelpers.getModels().Event.status.BUSY) { %><%= _.t("events:eventStatusBusy") %><% } %>
            <% if(status == _.templateHelpers.getModels().Event.status.RESOLVED) { %><%= _.t("events:eventStatusResolved") %><% } %>
            <% if(status == _.templateHelpers.getModels().Event.status.IGNORED) { %><%= _.t("events:eventStatusIgnored") %><% } %>
        </span>
        <br />
        <strong><%= _.t("events:eventComment") %>:</strong>&nbsp;<p><%- comment %></p>
    </div>
</div>