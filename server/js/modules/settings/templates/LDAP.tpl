<form class="form-group">
    <div class="form-group has-feedback">
        <label for="ldapServer" class="control-label">Server</label>
        <input type="text" name="ldapServer" class="form-control" value="<%- ldapServer %>" placeholder="Hostname oder IP-Adresse" <% if(ldapEnabled) { %>required<% } %> />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="ldapPort" class="control-label">Port</label>
        <input type="number" name="ldapPort" class="form-control" placeholder="389" value="<%- ldapPort %>" <% if(ldapEnabled) { %>required<% } %> min="0" max="65535" data-max-error="Der Port muss zwischen 0 und 65535 liegen" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group">
        <label for="ldapEncryption" class="control-label">Verschl&uuml;sselung</label>
        <select name="ldapEncryption" class="form-control">
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.NONE %>">Keine</option>
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.STARTTLS %>">STARTTLS</option>
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.TLS %>">TLS</option>
        </select>
    </div>
    <div class="form-group has-feedback">
        <label for="ldapTemplate" class="control-label">Template</label>
        <div class="input-group">
            <input type="text" name="ldapTemplate" class="form-control" placeholder="%s" value="<%- ldapTemplate %>" <% if(ldapEnabled) { %> required <% } %> />
            <div class="input-group-addon">
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            </div>
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Benutzerstring, der an den Server übermittelt wird. Das Token %s im String wird dann durch den Login-Benutzernamen ersetzt.">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
        </div>
        <div class="help-block with-errors"></div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
            </button>
        </div>
        <div class="col-sm-6">
            <button type="button" class="reset btn btn-block btn-default btn-sm">
                <span class="glyphicon glyphicon-repeat"></span>&nbsp;&nbsp;Zur&uuml;cksetzen
            </button>
        </div>
    </div>
</form>