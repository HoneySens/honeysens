<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Sensor entfernen</h4>
        </div>
        <div class="modal-body">
            <p>Soll der Sensor <strong><%- name %></strong> wirklich entfernt werden?</p>
            <p>Alle von diesem aufgezeichneten Ereignisse werden gel&ouml;scht.</p>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="archive">Ereignisse zuvor in Archiv verschieben
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;Entfernen</button>
        </div>
    </div>
</div>