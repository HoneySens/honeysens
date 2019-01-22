<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Benutzer <% if(isEdit()) { %>bearbeiten<% } else { %>hinzuf&uuml;gen<% } %></h4>
        </div>
        <div class="modal-body">
            <form class="form-horizontal" role="form">
                <div class="form-group">
                    <label for="username" class="col-sm-2 control-label">Name</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="username" placeholder="Benutzername" value="<%- name %>" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="password" class="col-sm-2 control-label">Passwort</label>
                    <div class="col-sm-5">
                        <input type="password" class="form-control" name="password" placeholder="<% if(isEdit()) { %>Neues Passwort<% } else { %>Passwort<% } %>" value="<%- password %>" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword" class="col-sm-1 control-label">Passwort wiederholen</label>
                    <div class="col-sm-8">
                        <input type="password" class="form-control" name="password" placeholder="<% if(isEdit()) { %>Neues Passwort<% } else { %>Passwort<% } %>" value="<%- password %>" /></div>
                </div>
                <div class="form-group">
                    <label for="role" class="col-sm-2 control-label">Rolle</label>
                    <div class="col-sm-5">
                        <select class="form-control" name="role" <% if(id == 1) { %>disabled<% } %>>
                            <option value="<%- _.templateHelpers.getModels().User.role.OBSERVER %>" <%- role === _.templateHelpers.getModels().User.role.OBSERVER ? 'selected' : void 0 %>>Beobachter</option>
                            <option value="<%- _.templateHelpers.getModels().User.role.MANAGER %>" <%- role === _.templateHelpers.getModels().User.role.MANAGER ? 'selected' : void 0 %>>Manager</option>
                            <option value="<%- _.templateHelpers.getModels().User.role.ADMIN %>" <%- role === _.templateHelpers.getModels().User.role.ADMIN ? 'selected' : void 0 %>>Administrator</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <dl class="col-sm-offset-2 col-sm-10">
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
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern</button>
        </div>
    </div>
</div>