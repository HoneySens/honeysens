<form class="form-group">
    <div class="form-group has-feedback">
        <label for="smtpServer" class="control-label"><%= _.t("server") %></label>
        <input type="text" name="smtpServer" class="form-control" value="<%- smtpServer %>" placeholder="<%= _.t('serverPlaceholder') %>" <% if(smtpEnabled) { %>required<% } %> />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="smtpPort" class="control-label"><%= _.t("port") %></label>
        <input type="number" name="smtpPort" class="form-control" placeholder="25/587" value="<%- smtpPort %>" required min="0" max="65535" data-max-error="<%= _.t('intValidationError', {min: 0, max: 65535}) %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group">
        <label for="smtpEncryption" class="control-label"><%= _.t("settings:encryption") %></label>
        <select name="smtpEncryption" class="form-control">
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.NONE %>"><%= _.t("settings:encryptionNone") %></option>
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.STARTTLS %>"><%= _.t("settings:encryptionSTARTTLS") %></option>
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.TLS %>"><%= _.t("settings:encryptionTLS") %></option>
        </select>
    </div>
    <div class="form-group has-feedback">
        <label class="control-label"><%= _.t("settings:emailSender") %></label>
        <input type="email" name="smtpFrom" class="form-control" value="<%- smtpFrom %>" placeholder="user@example.com" <% if(smtpEnabled) { %>required<% } %> />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label class="control-label"><%= _.t("settings:emailSMTPUser") %></label>
        <input type="text" name="smtpUser" class="form-control" value="<%- smtpUser %>" placeholder="<%= _.t('optional') %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label class="control-label"><%= _.t("settings:emailSMTPPassword") %></label>
        <input type="password" name="smtpPassword" class="form-control" value="<%- smtpPassword %>" placeholder="<%= _.t('optional') %>" autocomplete="new-password" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <button type="submit" class="saveSettings btn btn-block col-lg-6 btn-primary btn-sm"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %></button>
        </div>
        <div class="col-sm-4">
            <button type="button" class="sendTestMail btn btn-block col-lg-6 btn-default btn-sm"><span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;<%= _.t("settings:emailTest") %></button>
        </div>
        <div class="col-sm-4">
            <button type="button" class="reset btn btn-block btn-default btn-sm">
                <span class="glyphicon glyphicon-repeat"></span>&nbsp;&nbsp;<%= _.t("reset") %>
            </button>
        </div>
    </div>
</form>
