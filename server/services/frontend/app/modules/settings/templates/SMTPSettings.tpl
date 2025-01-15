<form class="form-group">
    <div class="form-group has-feedback">
        <label for="smtpServer" class="control-label">Server</label>
        <input type="text" name="smtpServer" class="form-control" value="<%- smtpServer %>" placeholder="Hostname oder IP-Adresse" <% if(smtpEnabled) { %>required<% } %> />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="smtpPort" class="control-label">Port</label>
        <input type="number" name="smtpPort" class="form-control" placeholder="25/587" value="<%- smtpPort %>" required min="0" max="65535" data-max-error="Der Port muss zwischen 0 und 65535 liegen" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group">
        <label for="smtpEncryption" class="control-label">Verschl&uuml;sselung</label>
        <select name="smtpEncryption" class="form-control">
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.NONE %>">Keine</option>
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.STARTTLS %>">STARTTLS</option>
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.TLS %>">TLS</option>
        </select>
    </div>
    <div class="form-group has-feedback">
        <label class="control-label">Absender</label>
        <input type="email" name="smtpFrom" class="form-control" value="<%- smtpFrom %>" placeholder="user@example.com" data-pattern-error="Bitte geben Sie eine E-Mail-Adresse ein." <% if(smtpEnabled) { %>required<% } %> />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label class="control-label">Benutzer</label>
        <input type="text" name="smtpUser" class="form-control" value="<%- smtpUser %>" placeholder="optional" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label class="control-label">Passwort</label>
        <input type="password" name="smtpPassword" class="form-control" value="<%- smtpPassword %>" placeholder="optional" autocomplete="new-password" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <button type="submit" class="saveSettings btn btn-block col-lg-6 btn-primary btn-sm"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern</button>
        </div>
        <div class="col-sm-4">
            <button type="button" class="sendTestMail btn btn-block col-lg-6 btn-default btn-sm"><span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;Testmail versenden</button>
        </div>
        <div class="col-sm-4">
            <button type="button" class="reset btn btn-block btn-default btn-sm">
                <span class="glyphicon glyphicon-repeat"></span>&nbsp;&nbsp;&Auml;nderungen zur&uuml;cksetzen
            </button>
        </div>
    </div>
</form>
