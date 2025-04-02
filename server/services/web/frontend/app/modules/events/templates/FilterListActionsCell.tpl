<button type="button" class="toggle btn btn-default btn-xs" data-toggle="tooltip" title="<% if(enabled) { %><%= _.t('events:filterDisable') %><% } else { %><%= _.t('events:filterEnable') %><% } %>">
    <span class="glyphicon glyphicon-<% if(enabled) { %>pause<% } else { %>play<% } %>"></span>
</button>
<button type="button" class="edit btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('update') %>">
    <span class="glyphicon glyphicon-pencil"></span>
</button>
<button type="button" class="remove btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('remove') %>">
    <span class="glyphicon glyphicon-remove"></span>
</button>