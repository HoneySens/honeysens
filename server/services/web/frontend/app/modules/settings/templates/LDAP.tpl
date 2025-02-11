<form class="form-group">
    <div class="form-group has-feedback">
        <label for="ldapServer" class="control-label"><%= _.t("server") %></label>
        <input type="text" name="ldapServer" class="form-control" value="<%- ldapServer %>" placeholder="<%= _.t('serverPlaceholder') %>" <% if(ldapEnabled) { %>required<% } %> />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="ldapPort" class="control-label"><%= _.t("port") %></label>
        <input type="number" name="ldapPort" class="form-control" placeholder="389" value="<%- ldapPort %>" required min="0" max="65535" data-max-error="<%= _.t('intValidationError', {min: 0, max: 65535}) %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group">
        <label for="ldapEncryption" class="control-label"><%= _.t("settings:encryption") %></label>
        <select name="ldapEncryption" class="form-control">
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.NONE %>"><%= _.t("settings:encryptionNone") %></option>
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.STARTTLS %>"><%= _.t("settings:encryptionSTARTTLS") %></option>
            <option value="<%- _.templateHelpers.getModels().Settings.encryption.TLS %>"><%= _.t("settings:encryptionTLS") %></option>
        </select>
    </div>
    <div class="form-group has-feedback">
        <label for="ldapTemplate" class="control-label"><%= _.t("settings:ldapTemplate") %></label>
        <div class="input-group">
            <input type="text" name="ldapTemplate" class="form-control" placeholder="%s" value="<%- ldapTemplate %>" <% if(ldapEnabled) { %> required <% } %> />
            <div class="input-group-addon">
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            </div>
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('settings:ldapTemplateInfo') %>">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
        </div>
        <div class="help-block with-errors"></div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %>
            </button>
        </div>
        <div class="col-sm-6">
            <button type="button" class="reset btn btn-block btn-default btn-sm">
                <span class="glyphicon glyphicon-repeat"></span>&nbsp;&nbsp;<%= _.t("reset") %>
            </button>
        </div>
    </div>
</form>