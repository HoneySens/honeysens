<div class="col-sm-12">
    <div class="headerBar">
        <div class="pull-right">
            <button type="button" class="save btn btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
            </button>
            <button type="button" class="cancel btn btn-default btn-sm">Abbrechen</button>
        </div>
        <h3>Benutzer <% if(isEdit()) { %>bearbeiten<% } else { %>hinzuf&uuml;gen<% } %></h3>
    </div>
    <form class="form-horizontal" role="form">
        <div class="form-group">
            <label for="username" class="col-sm-1 control-label">Name</label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="username" placeholder="Benutzername" value="<%- name %>" autocomplete="off" />
            </div>
        </div>
        <div class="form-group">
            <label for="password" class="col-sm-1 control-label">Passwort</label>
            <div class="col-sm-8">
                <input type="password" class="form-control" name="password" placeholder="<% if(isEdit()) { %>Neues Passwort<% } else { %>Passwort<% } %>" value="<%- password %>" autocomplete="new-password"/>
            </div>
        </div>
        <div class="form-group">
            <label for="confirmPassword" class="col-sm-1 control-label">Passwort wiederholen</label>
            <div class="col-sm-8">
                <input type="password" class="form-control" name="confirmPassword" placeholder="<% if(isEdit()) { %>Passwort wiederholen<% } else { %>Passwort<% } %>" value="<%- password %>" autocomplete="new-password"/>
            </div>
        </div>
        <div class="form-group">
            <label for="email" class="col-sm-1 control-label">E-Mail</label>
            <div class="col-sm-8">
                <input type="email" class="form-control" name="email" placeholder="E-Mail-Adresse" value="<%- email %>" />
            </div>
        </div>
        <div class="form-group">
            <label for="role" class="col-sm-1 control-label">Rolle</label>
            <div class="col-sm-8">
                <select class="form-control" name="role" <% if(isEdit() && id == 1) { %>disabled<% } %>>
                    <option value="<%- _.templateHelpers.getModels().User.role.OBSERVER %>" <%- role === _.templateHelpers.getModels().User.role.OBSERVER ? 'selected' : void 0 %>>Beobachter</option>
                    <option value="<%- _.templateHelpers.getModels().User.role.MANAGER %>" <%- role === _.templateHelpers.getModels().User.role.MANAGER ? 'selected' : void 0 %>>Manager</option>
                    <option value="<%- _.templateHelpers.getModels().User.role.ADMIN %>" <%- role === _.templateHelpers.getModels().User.role.ADMIN ? 'selected' : void 0 %>>Administrator</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <dl class="col-sm-offset-1 col-sm-10">
                <dt>Beobachter</dt>
                <dd>Kann Ereignisse, Sensoren und deren Konfiguration einsehen, aber nicht ver&auml;ndern.</dd>
                <dt>Manager</dt>
                <dd>Kann Ereignisse entfernen, Sensoren und deren Konfiguration einsehen und bearbeiten.</dd>
                <dt>Administrator</dt>
                <dd>Hat zus&auml;tzlich zu den Rechten des Managers Zugriff auf die Benutzerverwaltung.</dd>
            </dl>
        </div>
    </form>
</div>
