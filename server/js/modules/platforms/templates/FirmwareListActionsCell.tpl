<a class="btn btn-default btn-xs" href="api/platforms/firmware/<%- id %>/raw" data-toggle="tooltip" title="Download">
    <span class="glyphicon glyphicon-download-alt"></span>
</a>
<% if(nondef && _.templateHelpers.isAllowed('platforms', 'update')) { %>
<button type="button" class="setDefaultFirmware btn btn-default btn-xs" data-toggle="tooltip" title="Als Standard festlegen">
    <span class="glyphicon glyphicon-arrow-up"></span>
</button>
<button type="button" class="removeFirmware btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen">
    <span class="glyphicon glyphicon-remove"></span>
</button>
<% } %>
