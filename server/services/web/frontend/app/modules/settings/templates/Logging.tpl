<form class="form-group">
    <div class="form-group has-feedback">
        <label for="keepDays" class="control-label"><%= _.t("settings:loggingKeepDays") %></label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('settings:loggingKeepDaysInfo') %>">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input type="number" name="keepDays" class="form-control" value="<%- apiLogKeepDays %>" required min="0" max="65535" />
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %>
    </button>
</form>