<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Ereignis(se) entfernen</h4>
        </div>
        <div class="modal-body">
            <p>Sollen die ausgew&auml;hlten <strong><%- total %> Ereignisse</strong> wirklich entfernt werden?</p>
            <% if(!archived) { %>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="archive">Ereignisse archivieren
                    </label>
                </div>
            <% } %>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;Entfernen</button>
        </div>
    </div>
</div>