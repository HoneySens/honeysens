<div class="headerBar">
    <h3><%= t("settings:configuration") %></h3>
</div>
<div class="panel-group" id="settings">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-endpoint"><%= t("settings:configServerEndpoint") %></a>
            </h4>
        </div>
        <div id="settings-endpoint" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-sensors"><%= t("settings:configSensorConfig") %></a>
            </h4>
        </div>
        <div id="settings-sensors" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-archive"><%= t("settings:configEventArchive") %></a>
            </h4>
        </div>
        <div id="settings-archive" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-permissions"><%= t("settings:configPermissions") %></a>
            </h4>
        </div>
        <div id="settings-permissions" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default <% if(!_.templateHelpers.isAllowed('logs', 'get')) { %>hidden<% } %>">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-logging"><%= t("settings:configLogging") %></a>
            </h4>
        </div>
        <div id="settings-logging" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default ldapSettings">
        <div class="panel-heading">
            <button type="button" class="toggle btn btn-primary btn-xs pull-right" data-inactive-text="<%= t('disabled') %>" data-active-text="<%= t('enabled') %>"><%= t("disabled") %></button>
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-ldap"><%= t("settings:configLDAP") %></a>
            </h4>
        </div>
        <div id="settings-ldap" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default smtpSettings">
        <div class="panel-heading">
            <button type="button" class="toggle btn btn-primary btn-xs pull-right" data-inactive-text="<%= t('disabled') %>" data-active-text="<%= t('enabled') %>"><%= t("disabled") %></button>
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-smtp"><%= t("settings:configEMail") %></a>
            </h4>
        </div>
        <div id="settings-smtp" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-smtp-templates"><%= t("settings:configTemplates") %></a>
            </h4>
        </div>
        <div id="settings-smtp-templates" class="panel-collapse collapse"></div>
    </div>
    <div class="panel panel-default evforwardSettings">
        <div class="panel-heading">
            <button type="button" class="toggle btn btn-primary btn-xs pull-right" data-inactive-text="<%= t('disabled') %>" data-active-text="<%= t('enabled') %>"><%= t("disabled") %></button>
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#settings" href="#settings-evforward"><%= t("settings:configSyslog") %></a>
            </h4>
        </div>
        <div id="settings-evforward" class="panel-collapse collapse"></div>
    </div>
</div>