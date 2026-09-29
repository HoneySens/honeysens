<form class="form-group">
    <div class="form-group has-feedback">
        <label for="updateInterval" class="control-label"><%= t("settings:sensorsUpdateInterval") %></label>
        <input type="number" name="updateInterval" class="form-control" value="<%- sensorsUpdateInterval %>" required min="1" max="60" data-max-error="<%= t('intValidationError', {min: 1, max: 60}) %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="serviceNetwork" class="control-label"><%= t("settings:sensorsServiceNetwork") %></label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= t('sensors:sensorServiceNetworkInfo') %>">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$" data-pattern-error="<%= t('sensors:sensorServiceNetworkValidationError') %>" type="text" class="form-control" name="serviceNetwork" value="<%- sensorsServiceNetwork %>" required />
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="timeoutThreshold" class="control-label"><%= t("settings:sensorsTimeoutThreshold") %></label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= t('settings:sensorsTimeoutThresholdInfo') %>">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input type="number" name="timeoutThreshold" class="form-control" value="<%- sensorsTimeoutThreshold %>" required min="1" max="1440" data-min-error="<%= t('settings:sensorsTimeoutThresholdMinError') %>" data-max-error="<%= t('intValidationError', {min: 1, max: 1440}) %>" />
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= t("save") %>
    </button>
</form>