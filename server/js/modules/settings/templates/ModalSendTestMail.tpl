<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Testmail versenden</span>
        </div>
        <div class="modal-body">
            <form class="form-group">
                <div class="form-group">
                    <label for="targetAddress" class="control-label">Empfänger-Adresse</label>
                    <input type="text" name="recipient" class="form-control" placeholder="E-Mail-Adresse" />
                </div>
            </form>
            <div class="well well-sm sendError hidden">
                <p><strong>Fehler beim Senden:</strong></p>
                <code></code>
            </div>
            <div class="alert alert-success sendSuccess hidden">Nachricht erfolgreich versendet!</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Schlie&szlig;en</button>
            <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;Abschicken</button>
        </div>
    </div>
</div>