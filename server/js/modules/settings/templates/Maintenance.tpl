<div class="headerBar">
    <h3>Wartung</h3>
</div>
<div class="panel-group" id="maintenance">
    <!-- DISABLED UNTIL SERVER-SIDE RESET IS FIXED
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#dbreset">Datenbank zur&uuml;cksetzen</a>
            </h4>
        </div>
        <div id="dbreset" class="panel-collapse collapse">
            <div class="panel-body">
                <div class="pull-right">
                    <button type="button" class="resetDB btn btn-primary btn-sm" data-loading-text="In Arbeit...">Zur&uuml;cksetzen</button>
                </div>
                Achtung, wenn Sie diese Aktion durchf&uuml;hren, werden <strong>ALLE Datenbankinhalte unwiderruflich
                verloren gehen!</strong> Das System wird anschlie&szlig;end neu initialisiert.
            </div>
        </div>
    </div>
    -->
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#evreset">Ereignisse entfernen</a>
            </h4>
        </div>
        <div id="evreset" class="panel-collapse collapse">
            <div class="panel-body">
                <div class="pull-right">
                    <button type="button" class="removeEvents btn btn-primary btn-sm" data-loading-text="Entfernen...">
                        Entfernen
                    </button>
                </div>
                Achtung, wenn Sie diese Aktion durchf&uuml;hren, werden <strong>ALLE gespeicherten Ereignisse unwiderruflich
                entfernt!</strong>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#caupdate">Certificate Authority</a>
            </h4>
        </div>
        <div id="caupdate" class="panel-collapse collapse">
            <div class="panel-body">
                <p>Mit dieser Funktion wird ein neues CA-Zertifikat generiert, was eine Aktualisierung aller Zertifikate
                    dieser HoneySens-Installation nach sich zieht. Dies wird erforderlich, wenn sich das derzeit genutzte
                    Zertifikat seinem Ablaufdatum annähert. Nach dem Start des Prozesses wird die Webanwendung automatisch
                    neu geladen.
                </p>
                <hr />
                <p>
                    <strong>SHA1-Fingerprint des aktuellen Zertifikats:</strong> <%- showCaFP() %><br />
                    <strong>Gültigkeit bis:</strong> <%- showCaExpire() %>
                </p>
                <hr />
                <p><strong>Achtung: Dieser Vorgang kann nicht r&uuml;ckg&auml;ngig gemacht werden!</strong></p>
                <button type="button" class="refreshCA btn btn-primary btn-block" data-loading-text="In Bearbeitung...">
                    Zertifikate verl&auml;ngern
                </button>
            </div>
        </div>
    </div>
</div>
