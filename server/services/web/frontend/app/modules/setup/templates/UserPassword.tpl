<h2><%= _.t("userPasswordHeader") %></h2>
<hr />
<form>
    <p><%= _.t("setup:userPasswordIntro") %></p>
    <div class="form-group has-feedback">
        <label for="userPassword"><%= _.t("setup:userPassword") %></label>
        <input type="password" name="userPassword" id="userPassword" class="form-control" required minlength="6" data-minlength-error="<%= _.t('lengthValidationError', {min: 6, max: 255}) %>" maxlength="255"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="userPasswordRepeat"><%= _.t("setup:passwordRepeat") %></label>
        <input type="password" name="userPasswordRepeat" id="userPasswordRepeat" class="form-control" required data-match="#userPassword" data-match-error="<%= _.t('setup:passwordMismatch') %>"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <p><%= _.t("setup:userPasswordInfo") %></p>
    <button type="button" class="btn btn-default btn-block"><%= _.t("cancel") %></button>
    <button type="submit" class="btn btn-primary btn-block"><%= _.t("continue") %></button>
</form>