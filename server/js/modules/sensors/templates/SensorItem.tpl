<td><%- id %></td>
<td><%- name %></td>
<td><%- location %></td>
<td>
    <% if(sw_version) { %>
    <%- sw_version %>
    <% } else { %>
    N.A.
    <% } %>
</td>
<td>
    <% if(last_ip) { %>
    <%- last_ip %>
    <% } else { %>
    N.A.
    <% } %>
</td>
<td <% if(isTimedOut() || last_status == _.templateHelpers.getModels().SensorStatus.status.ERROR) { %>class="danger"<% }
else if(last_status == _.templateHelpers.getModels().SensorStatus.status.UPDATE_PHASE1
|| last_status == _.templateHelpers.getModels().SensorStatus.status.INSTALL_PHASE1
|| last_status == _.templateHelpers.getModels().SensorStatus.status.UPDATEINSTALL_PHASE2) { %>class="info"<% }
else if(last_status == _.templateHelpers.getModels().SensorStatus.status.RUNNING) { %>class="success"<% } %>>
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
</td>
<% if(_.templateHelpers.isAllowed('certs', 'get')) { %>
<td class="showCert">
    <button type="button" class="showCert btn btn-default btn-xs">
        <span class="glyphicon glyphicon-ok"></span> Anzeigen
    </button>
</td>
<% } %>
<td>
    <button type="button" class="showStatus btn btn-default btn-xs" data-toggle="tooltip" title="Letzte Statusmeldungen">
        <span class="glyphicon glyphicon-list"></span>
    </button>
    <% if(_.templateHelpers.isAllowed('sensors', 'update')) { %>
    <button type="button" class="editSensor btn btn-default btn-xs" data-toggle="tooltip" title="Bearbeiten">
        <span class="glyphicon glyphicon-pencil"></span>
    </button>
    <button type="button" class="removeSensor btn btn-default btn-xs" data-toggle="tooltip" title="Entfernen">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
    <% } %>
</td>