<button type="button" class="showEvent btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('events:eventShowDetails') %>">
    <span class="glyphicon glyphicon-list-alt"></span>
</button>
<% if(_.templateHelpers.isAllowed('events', 'delete') || (!archived && _.templateHelpers.isAllowed('events', 'archive'))) { %>
<button type="button" class="removeEvent btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('remove') %>">
    <span class="glyphicon glyphicon-remove"></span>
</button>
<% } %>