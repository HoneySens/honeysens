<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Testereignis versendet</h4>
        </div>
        <div class="modal-body">
            <dl class="dl-horizontal label-left">
                <dt>ID</dt>
                <dd><%- id %></dd>
                <dt>Zeitstempel</dt>
                <dd><%- showTimestamp() %></dd>
                <dt>Sensor</dt>
                <dd><%- sensor_name %> (ID <%- sensor_id %>)</dd>
                <dt>Quelle</dt>
                <dd><%- source %></dd>
                <dt>Details</dt>
                <dd><%- summary %></dd>
            </dl>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal" autofocus>Schlie&szlig;en</button>
        </div>
    </div>
</div>