<h2><%= _.t("setup:adminHeader") %></h2>
<hr />
<form>
    <p><%= _.t("setup:adminPrompt") %></p>
    <div class="form-group has-feedback">
        <label for="adminPassword"><%= _.t("setup:adminPassword") %></label>
        <input type="password" name="adminPassword" id="adminPassword" class="form-control" required minlength="6" data-minlength-error="<%= _.t('lengthValidationError', {min: 6, max: 255}) %>" maxlength="255"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="adminPassword"><%= _.t("setup:passwordRepeat") %></label>
        <input type="password" name="adminPasswordRepeat" id="adminPasswordRepeat" class="form-control" required data-match="#adminPassword" data-match-error="<%= _.t('setup:passwordMismatch') %>"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="adminEmail"><%= _.t("setup:adminEMail") %></label>
        <input type="email" name="adminEmail" id="adminEmail" class="form-control" placeholder="E-Mail" required />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="btn btn-primary btn-block"><%= _.t("continue") %></button>
</form>
