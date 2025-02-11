<% if(_.templateHelpers.isAllowed('platforms', 'download')) { %>
<a class="btn btn-default btn-xs" href="api/platforms/firmware/<%- id %>/raw" data-toggle="tooltip" title="<%= _.t('platforms:download') %>">
    <span class="glyphicon glyphicon-download-alt"></span>
</a>
<% } %>
<% if(nondef && _.templateHelpers.isAllowed('platforms', 'update')) { %>
<button type="button" class="setDefaultFirmware btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('platforms:makeDefault') %>">
    <span class="glyphicon glyphicon-arrow-up"></span>
</button>
<button type="button" class="removeFirmware btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('remove') %>">
    <span class="glyphicon glyphicon-remove"></span>
</button>
<% } %>
