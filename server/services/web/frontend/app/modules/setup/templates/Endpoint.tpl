<h2><%= t("setup:serverEndpoint") %></h2>
<hr />
<form>
    <p><%= t("setup:serverPrompt") %></p>
    <p><%= t("setup:serverEndpointHint") %></p>
    <div class="form-group has-feedback">
        <label for="serverEndpoint"><%= t("setup:serverEndpoint") %></label>
        <input type="text" name="serverEndpoint" id="serverEndpoint" class="form-control" value="<%- showCertCN() %>" required minlength="1" maxlength="255" data-maxlength-error="<%= t('lengthValidationError', {min: 1, max: 255}) %>" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="btn btn-primary btn-block"><%= t("continue") %></button>
</form>