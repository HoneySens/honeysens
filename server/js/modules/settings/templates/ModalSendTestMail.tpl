<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Testnachricht versenden</h4>
        </div>
        <div class="modal-body">
            <form class="form-group">
                <div class="form-group has-feedback">
                    <label for="targetAddress" class="control-label">Empf&auml;nger-Adresse</label>
                    <input type="email" name="recipient" class="form-control" placeholder="E-Mail-Adresse" pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+" data-pattern-error="Bitte geben Sie eine E-Mail-Adresse ein." value="<%- getRecipient() %>" <% if(isDone()) { %>disabled<% } %> required />
                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                    <div class="help-block with-errors"></div>
                </div>
            </form>
            <div class="alert alert-info sendPending hidden">
                <div class="pull-left loadingInline"></div>&nbsp;Versende Testnachricht...
            </div>
            <% if(isDone()) { %>
                <% if(isError()) { %>
                    <div class="well well-sm sendError">
                        <p><strong>Fehler beim Senden:</strong></p>
                        <code><%- getError() %></code>
                    </div>
                <% } else { %>
                    <div class="alert alert-success sendSuccess">Nachricht erfolgreich versendet!</div>
                <% } %>
            <% } %>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default">Schlie&szlig;en</button>
            <% if(!isDone()) { %>
                <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;Abschicken</button>
            <% } %>
        </div>
    </div>
</div>