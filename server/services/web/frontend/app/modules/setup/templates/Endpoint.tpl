<h2><%= _.t("setup:serverEndpoint") %></h2>
<hr />
<form>
    <p><%= _.t("setup:serverPrompt") %></p>
    <p><%= _.t("setup:serverEndpointHint") %></p>
    <div class="form-group has-feedback">
        <label for="serverEndpoint"><%= _.t("setup:serverEndpoint") %></label>
        <input type="text" name="serverEndpoint" id="serverEndpoint" class="form-control" value="<%- showCertCN() %>" required minlength="1" maxlength="255" data-maxlength-error="<%= _.t('lengthValidationError', {min: 1, max: 255}) %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="btn btn-primary btn-block"><%= _.t("continue") %></button>
</form>