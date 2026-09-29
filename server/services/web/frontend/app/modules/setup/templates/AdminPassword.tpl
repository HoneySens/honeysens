<h2><%= t("setup:adminHeader") %></h2>
<hr />
<form>
    <p><%= t("setup:adminPrompt") %></p>
    <div class="form-group has-feedback">
        <label for="adminPassword"><%= t("setup:adminPassword") %></label>
        <input type="password" name="adminPassword" id="adminPassword" class="form-control" required minlength="6" data-minlength-error="<%= t('lengthValidationError', {min: 6, max: 255}) %>" maxlength="255"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="adminPassword"><%= t("setup:passwordRepeat") %></label>
        <input type="password" name="adminPasswordRepeat" id="adminPasswordRepeat" class="form-control" required data-match="#adminPassword" data-match-error="<%= t('setup:passwordMismatch') %>"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="adminEmail"><%= t("setup:adminEMail") %></label>
        <input type="email" name="adminEmail" id="adminEmail" class="form-control" placeholder="E-Mail" required />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="btn btn-primary btn-block"><%= t("continue") %></button>
</form>
