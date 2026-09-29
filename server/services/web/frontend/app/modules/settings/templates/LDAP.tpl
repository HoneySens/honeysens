<form class="form-group">
    <div class="form-group has-feedback">
        <label for="ldapServer" class="control-label"><%= t("server") %></label>
        <input type="text" name="ldapServer" class="form-control" value="<%- ldapServer %>" placeholder="<%= t('serverPlaceholder') %>" <% if(ldapEnabled) { %>required<% } %> />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="ldapPort" class="control-label"><%= t("port") %></label>
        <input type="number" name="ldapPort" class="form-control" placeholder="389" value="<%- ldapPort %>" required min="0" max="65535" data-max-error="<%= t('intValidationError', {min: 0, max: 65535}) %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group">
        <label for="ldapEncryption" class="control-label"><%= t("settings:encryption") %></label>
        <select name="ldapEncryption" class="form-control">
            <option value="<%- ChannelEncryption.NONE %>"><%= t("settings:encryptionNone") %></option>
            <option value="<%- ChannelEncryption.STARTTLS %>"><%= t("settings:encryptionSTARTTLS") %></option>
            <option value="<%- ChannelEncryption.TLS %>"><%= t("settings:encryptionTLS") %></option>
        </select>
    </div>
    <div class="form-group has-feedback">
        <label for="ldapTemplate" class="control-label"><%= t("settings:ldapTemplate") %></label>
        <div class="input-group">
            <input type="text" name="ldapTemplate" class="form-control" placeholder="%s" value="<%- ldapTemplate %>" <% if(ldapEnabled) { %> required <% } %> />
            <div class="input-group-addon">
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            </div>
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= t('settings:ldapTemplateInfo') %>">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
        </div>
        <div class="help-block with-errors"></div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= t("save") %>
            </button>
        </div>
        <div class="col-sm-6">
            <button type="button" class="reset btn btn-block btn-default btn-sm">
                <span class="glyphicon glyphicon-repeat"></span>&nbsp;&nbsp;<%= t("reset") %>
            </button>
        </div>
    </div>
</form>