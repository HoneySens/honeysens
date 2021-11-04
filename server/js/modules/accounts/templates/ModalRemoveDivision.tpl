<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Gruppe entfernen</h4>
        </div>
        <div class="modal-body">
            <p>Soll die Gruppe <strong><%- name %></strong> mit all ihren assoziierten Sensoren und Ergebnissen wirklich entfernt werden?</p>
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