<button type="button" class="editStatus pull-right btn btn-default btn-xs"
    <% if(!_.templateHelpers.isAllowed('events', 'update') || archived) { %>disabled="disabled"<% } %>>
    <span class="glyphicon glyphicon-pencil"></span>
</button>
<% if(status == EventStatus.UNEDITED) { %><%= t("events:eventStatusUnedited") %>
<% } else if(status == EventStatus.BUSY) { %><%= t("events:eventStatusBusy") %>
<% } else if(status == EventStatus.RESOLVED) { %><%= t("events:eventStatusResolved") %>
<% } else if(status == EventStatus.IGNORED) { %><%= t("events:eventStatusIgnored") %>
<% } else { %><%= t("events:eventStatusInvalid") %><% } %>
<div class="popover">
    <div class="popover-content">
        <strong><%= t("events:eventStatus") %>:</strong>&nbsp;
        <span>
            <% if(status == EventStatus.UNEDITED) { %><%= t("events:eventStatusUnedited") %><% } %>
            <% if(status == EventStatus.BUSY) { %><%= t("events:eventStatusBusy") %><% } %>
            <% if(status == EventStatus.RESOLVED) { %><%= t("events:eventStatusResolved") %><% } %>
            <% if(status == EventStatus.IGNORED) { %><%= t("events:eventStatusIgnored") %><% } %>
        </span>
        <br />
        <strong><%= t("events:eventComment") %>:</strong>&nbsp;<p><%- comment %></p>
    </div>
</div>