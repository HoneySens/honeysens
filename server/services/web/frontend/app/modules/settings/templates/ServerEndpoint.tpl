<form class="form-group">
    <div class="form-group has-feedback">
        <label for="serverHost" class="control-label"><%= _.t("host") %></label>
        <input type="text" name="serverHost" class="form-control" disabled="disabled" placeholder="<%= _.t('serverPlaceholder') %>" value="<%- serverHost %>" required/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="serverPortHTTPS" class="control-label"><%= _.t("settings:serverPort") %></label>
        <input type="number" name="serverPortHTTPS" class="form-control" placeholder="<%= _.t('settings:serverPortPlaceholder') %>" value="<%- serverPortHTTPS %>" required min="0" max="65535" data-max-error="<%= _.t('intValidationError', {min: 0, max: 65535}) %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %>
    </button>
</form>
