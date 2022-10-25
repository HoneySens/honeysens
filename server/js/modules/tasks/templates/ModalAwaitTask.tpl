<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Prozessstatus</h4>
        </div>
        <div class="modal-body">
            <% if(status == _.templateHelpers.getModels().Task.status.SCHEDULED || status == _.templateHelpers.getModels().Task.status.RUNNING) { %>
                <div class="alert alert-info">
                    <div class="pull-left loadingInline"></div>&nbsp;Prozess wird bearbeitet</span>
                </div>
                <div class="well">
                    Dieser Vorgang kann je nach Auslastung des Servers einige Zeit in Anspruch nehmen.
                    Sie k&ouml;nnen diesen Dialog oder Tab jederzeit schlie&szlig;en. Das Ergebnis kann nach Fertigstellung
                    im Abschnitt <em>"Prozesse"</em> in der Sidebar heruntergeladen werden.
                </div>
            <% } else if(status == _.templateHelpers.getModels().Task.status.DONE) { %>
                <div class="alert alert-success">
                    Vorgang erfolgreich abschlossen.
                </div>
            <% } else { %>
                <div class="alert alert-danger">
                    Bei der Bearbeitung des Vorgangs ist ein Fehler aufgetreten.
                </div>
            <% } %>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal" autofocus>Schlie&szlig;en</button>
        </div>
    </div>
</div>