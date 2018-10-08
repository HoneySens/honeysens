<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Testmail versenden</h4>
        </div>
        <div class="modal-body">
            <form class="form-group">
                <div class="form-group has-feedback">
                    <label for="targetAddress" class="control-label">Empfänger-Adresse</label>
                    <input type="email" name="recipient" class="form-control" placeholder="E-Mail-Adresse" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" data-pattern-error="Bitte geben Sie eine E-Mail-Adresse ein." required />
                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                    <div class="help-block with-errors"></div>
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