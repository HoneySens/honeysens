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
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#dbupdate">Schema-Update</a>
            </h4>
        </div>
        <div id="dbupdate" class="panel-collapse collapse">
            <div class="panel-body">
                <div class="pull-right">
                    <button type="button" class="updateDB btn btn-primary btn-sm" data-loading-text="Update...">
                        Update
                    </button>
                </div>
                Mit dieser Funktion wird das interne Datenbankschema aktualisiert. Nutzen Sie diese Funktion nach dem Aufspielen
                einer neuen Serverrevision, um den bestehenden Datenbestand in das neue Format zu konvertieren.
            </div>
        </div>
    </div>
</div>
