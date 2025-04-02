<form class="form-group">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="archivePrefer" <% if(archivePrefer) { %>checked<% } %>>
            <%= _.t("settings:archivePreselectRemoveEvents") %>
        </label>
    </div>
    <div class="form-group has-feedback">
        <label for="archiveMoveKays" class="control-label"><%= _.t("settings:archiveMoveDays") %></label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('settings:archiveMoveDaysInfo') %>">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input type="number" name="archiveMoveDays" class="form-control" value="<%- archiveMoveDays %>" required min="0" max="65535" />
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="archiveKeepDays" class="control-label"><%= _.t("settings:archiveKeepDays") %></label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('settings:archiveKeepDaysInfo') %>">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input type="number" name="archiveKeepDays" class="form-control" value="<%- archiveKeepDays %>" required min="0" max="65535" />
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %>
    </button>
</form>