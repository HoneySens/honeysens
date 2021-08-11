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
    <form class="form-group">
        <div class="form-group has-feedback">
            <label for="username" class="control-label">Login</label>
            <input type="text" name="username" class="form-control" placeholder="Benutzername" value="<%- name %>" required autocomplete="off" pattern="^[a-zA-Z0-9]+$" data-pattern-error="Nur Gro&szlig;-, Kleinbuchstaben und Zahlen sind erlaubt" minlength="1" maxlength="255" data-maxlength-error="Der Benutzername darf maximal 255 Zeichen lang sein" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group">
            <label for="domain" class="control-label">Authentifizierung</label>
            <select class="form-control" name="domain" <% if(isEdit() && id == 1) { %>disabled<% } %>>
                <option value="<%- _.templateHelpers.getModels().User.domain.LOCAL %>" <%- domain === _.templateHelpers.getModels().User.domain.LOCAL ? 'selected' : void 0 %>>Lokal</option>
                <option value="<%- _.templateHelpers.getModels().User.domain.LDAP %>" <%- domain === _.templateHelpers.getModels().User.domain.LDAP ? 'selected' : void 0 %>>LDAP</option>
            </select>
        </div>
        <div class="form-group has-feedback password">
            <label for="password" class="control-label">Passwort</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="<% if(isEdit()) { %>Neues Passwort<% } else { %>Passwort<% } %>" value="<%- password %>" data-minlength="6" data-minlength-error="Das Passwort muss zwischen 6 und 255 Zeichen lang sein" maxlength="255" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group has-feedback password">
            <label for="confirmPassword" class="control-label">Passwort wiederholen</label>
            <input type="password" class="form-control" id="confirmPassword" class="form-control" placeholder="<% if(isEdit()) { %>Passwort wiederholen<% } else { %>Passwort<% } %>" value="<%- password %>" data-match="#password" data-match-error="Die Passw&ouml;rter stimmen nicht &uuml;berein" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="checkbox requirePasswordChange">
            <label>
                <input type="checkbox" name="requirePasswordChange" <% if(require_password_change) { %>checked<% } %>>
                Passwort&auml;nderung bei n&auml;chstem Login erzwingen
            </label>
        </div>
        <div class="form-group has-feedback">
            <label for="fullName" class="control-label">Vollst&auml;ndiger Name</label>
            <input type="text" name="fullName" class="form-control" value="<%- full_name %>" placeholder="Name oder Beschreibung" />
        </div>
        <div class="form-group has-feedback">
            <label for="email" class="control-label">E-Mail</label>
            <input type="email" name="email" class="form-control" placeholder="E-Mail-Adresse" value="<%- email %>" pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+$" data-pattern-error="Bitte geben Sie eine E-Mail-Adresse ein." required />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group">
            <label for="role" class="control-label">Rolle</label>
            <select class="form-control" name="role" <% if(isEdit() && id == 1) { %>disabled<% } %>>
                <option value="<%- _.templateHelpers.getModels().User.role.OBSERVER %>" <%- role === _.templateHelpers.getModels().User.role.OBSERVER ? 'selected' : void 0 %>>Beobachter</option>
                <option value="<%- _.templateHelpers.getModels().User.role.MANAGER %>" <%- role === _.templateHelpers.getModels().User.role.MANAGER ? 'selected' : void 0 %>>Manager</option>
                <option value="<%- _.templateHelpers.getModels().User.role.ADMIN %>" <%- role === _.templateHelpers.getModels().User.role.ADMIN ? 'selected' : void 0 %>>Administrator</option>
            </select>
        </div>
        <div class="form-group">
            <dl>
                <dt>Beobachter</dt>
                <dd>Kann Ereignisse, Sensoren und deren Konfiguration einsehen, aber nicht ver&auml;ndern.</dd>
                <dt>Manager</dt>
                <dd>Kann Ereignisse entfernen, Sensoren und deren Konfiguration einsehen und bearbeiten.</dd>
                <dt>Administrator</dt>
                <dd>Hat zus&auml;tzlich zu den Rechten des Managers Zugriff auf Systemkonfiguration und die Benutzerverwaltung.</dd>
            </dl>
        </div>
        <fieldset>
            <legend>Benachrichtigungsoptionen</legend>
                <p>Das Aktivieren der Optionen in diesem Abschnitt zieht den Versand von E-Mails über diesen Server nach sich.
                   Damit dies wie erwartet funktioniert, muss für diesen Nutzer eine gültige E-Mail-Adresse hinterlegt und
                    der Versand von E-Mails in den globalen Einstellungen aktiviert werden.</p>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="notifyOnCAExpiration" <% if(notify_on_ca_expiration) { %>checked<% } %>>
                        Über den Ablauf des internen CA-Zertifikats informieren
                    </label>
                </div>
        </fieldset>
    </form>
</div>