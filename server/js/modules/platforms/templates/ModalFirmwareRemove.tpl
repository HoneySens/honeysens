<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Firmware entfernen</h4>
        </div>
        <div class="modal-body">
            <p>Soll die Firmware <strong><%- name %> (Version <%- version %>)</strong> wirklich entfernt werden?</p>
            <p><strong>Achtung:</strong> Sensoren, für die individuelle Firmware konfiguriert ist, werden auf die
                systemweite Standardrevision zurückgesetzt.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;Entfernen</button>
        </div>
    </div>
</div>