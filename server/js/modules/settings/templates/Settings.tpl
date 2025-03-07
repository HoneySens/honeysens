<div class="headerBar">
    <h3>Konfiguration</h3>
</div>
<div class="panel-group" id="settings">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-endpoint">Server-Endpunkt</a>
            </h4>
        </div>
        <div id="settings-endpoint" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-sensors">Sensor-Standardkonfiguration</a>
            </h4>
        </div>
        <div id="settings-sensors" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-archive">Ereignis-Archiv</a>
            </h4>
        </div>
        <div id="settings-archive" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-permissions">Rechteverwaltung</a>
            </h4>
        </div>
        <div id="settings-permissions" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default <% if(!_.templateHelpers.isAllowed('logs', 'get')) { %>hidden<% } %>">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-logging">Logdaten</a>
            </h4>
        </div>
        <div id="settings-logging" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default ldapSettings">
        <div class="panel-heading">
            <button type="button" class="toggle btn btn-primary btn-xs pull-right" data-inactive-text="Deaktiviert" data-active-text="Aktiviert">Deaktiviert</button>
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-ldap">LDAP-Verzeichnisdienst</a>
            </h4>
        </div>
        <div id="settings-ldap" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default smtpSettings">
        <div class="panel-heading">
            <button type="button" class="toggle btn btn-primary btn-xs pull-right" data-inactive-text="Deaktiviert" data-active-text="Aktiviert">Deaktiviert</button>
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-smtp">E-Mail-Versand</a>
            </h4>
        </div>
        <div id="settings-smtp" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-smtp-templates">E-Mail-Templates</a>
            </h4>
        </div>
        <div id="settings-smtp-templates" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default evforwardSettings">
        <div class="panel-heading">
            <button type="button" class="toggle btn btn-primary btn-xs pull-right" data-inactive-text="Deaktiviert" data-active-text="Aktiviert">Deaktiviert</button>
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-evforward">Ereignisweiterleitung</a>
            </h4>
        </div>
        <div id="settings-evforward" class="panel-collapse collapse"></div>
    </div>
</div>