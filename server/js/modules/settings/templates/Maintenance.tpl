<div class="headerBar">
    <h3>Wartung</h3>
</div>
<div class="panel-group" id="maintenance">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#evreset">Ereignisse entfernen</a>
            </h4>
        </div>
        <div id="evreset" class="panel-collapse collapse">
            <div class="panel-body">
                <div class="pull-right">
                    <button type="button" class="removeEvents btn btn-primary btn-sm">
                        Entfernen
                    </button>
                </div>
                <p>Diese Funktion entfernt <strong>ALLE</strong> derzeit gespeicherten Ereignisse und bereinigt zus&auml;tzlich das Ereignis-Archiv. Dies kann nicht r&uuml;ckg&auml;ngig gemacht werden!</p>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#caupdate">Interne Certificate Authority</a>
            </h4>
        </div>
        <div id="caupdate" class="panel-collapse collapse">
            <div class="panel-body">
                <p>Mit dieser Funktion wird ein neues internes CA-Zertifikat generiert, was eine Aktualisierung des
                    selbstsignierten TLS-Zertifikats dieser HoneySens-Installation nach sich zieht. Dies wird erforderlich,
                    wenn sich das derzeit genutzte Zertifikat seinem Ablaufdatum annähert.
                    Nach dem Start des Prozesses wird die Webanwendung automatisch neu geladen.
                </p>
                <hr />
                <p>
                    <strong>SHA1-Fingerprint des aktuellen Zertifikats:</strong> <%- showCaFP() %><br />
                    <strong>Gültigkeit bis:</strong> <%- showCaExpire() %>
                </p>
                <hr />
                <p><strong>Achtung: Dieser Vorgang kann nicht r&uuml;ckg&auml;ngig gemacht werden!</strong></p>
                <button type="button" class="refreshCA btn btn-primary btn-block" >
                    Zertifikate verl&auml;ngern
                </button>
            </div>
        </div>
    </div>
</div>
