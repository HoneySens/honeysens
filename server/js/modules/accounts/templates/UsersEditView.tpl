<div class="col-sm-12">
    <div class="headerBar">
        <div class="button-group text-right">
            <button type="button" class="save btn btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
            </button>
            <button type="button" class="cancel btn btn-default btn-sm">Abbrechen</button>
        </div>
        <h3>Benutzer <% if(isEdit()) { %>bearbeiten<% } else { %>hinzuf&uuml;gen<% } %></h3>
    </div>
    <form class="form-horizontal" role="form">
        <div class="form-group has-feedback">
            <label for="username" class="col-sm-1 control-label">Name</label>
            <div class="col-sm-8">
                <input type="text" name="username" class="form-control" placeholder="Benutzername" value="<%- name %>" required autocomplete="off" pattern="^[a-zA-Z0-9]+$" data-pattern-error="Nur Gro&szlig;-, Kleinbuchstaben und Zahlen erlaubt" minlength="1" maxlength="255" data-maxlength-error="Maximale L&auml;nge: 255 Zeichen" />
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div>
        <div class="form-group has-feedback">
            <label for="password" class="col-sm-1 control-label">Passwort</label>
            <div class="col-sm-8">
                <input type="password" name="password" id="password" class="form-control" placeholder="<% if(isEdit()) { %>Neues Passwort<% } else { %>Passwort<% } %>" value="<%- password %>" data-minlength="6" data-minlength-error="Passwortl&auml;nge zwischen 6 und 255 Zeichen" maxlength="255" /*data-match="#confirmPassword" data-match-error="Die Passw&ouml;rter stimmen nicht &uuml;berein"*/>
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div>
        <div class="form-group has-feedback">
            <label for="confirmPassword" class="col-sm-1 control-label">Passwort wiederholen</label>
            <div class="col-sm-8">
                <input type="password" class="form-control" id="confirmPassword" class="form-control" placeholder="<% if(isEdit()) { %>Passwort wiederholen<% } else { %>Passwort<% } %>" value="<%- password %>" data-match="#password" data-match-error="Die Passw&ouml;rter stimmen nicht &uuml;berein" >
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div>
        <div class="form-group has-feedback">
            <label for="email" class="col-sm-1 control-label">E-Mail</label>
            <div class="col-sm-8">
                <input type="email" name="email" class="form-control" placeholder="E-Mail-Adresse" value="<%- email %>" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" data-pattern-error="Bitte geben Sie eine E-Mail-Adresse ein." required />
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
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