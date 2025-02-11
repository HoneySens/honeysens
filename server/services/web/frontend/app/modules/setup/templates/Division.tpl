<h2><%= _.t("division") %></h2>
<hr />
<form>
    <p><%= _.t("setup:divisionPrompt") %></p>
    <div class="form-group has-feedback">
        <label for="divisionName"><%= _.t("setup:divisionName") %></label>
        <input type="text" name="divisionName" id="divisionName" class="form-control" required pattern="^[a-zA-Z0-9]+$" data-pattern-error="<%= _.t('nameValidationError') %>"  minlength="1" maxlength="255" data-maxlength-error="<%= _.t('lengthValidationError', {min: 1, max: 255}) %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    
    <button type="submit" class="btn btn-primary btn-block"><%= _.t("continue") %></button>
</form>