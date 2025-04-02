<p><strong><%= _.t("settings:templateVars") %></strong></p>
<ul>
    <% _(variables).each(function(description, varname) { %>
        <li><code>{{<%- varname %>}}</code> <%- description %></li>
    <% }); %>
</ul>
<div class="form-group">
    <label>
        <input type="checkbox" name="hasOverlay" <% if(hasOverlay()) { %>checked<% } %>>
        <%= _.t("settings:templateUseCustom") %>
    </label>
</div>
<div class="form-group">
    <textarea class="form-control" name="templateContent" style="height: 300px; resize: none;" required <% if(!hasOverlay()) { %>disabled="disabled"<% } %>></textarea>
</div>
<div class="row">
    <div class="col-sm-6">
        <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
            <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %>
        </button>
    </div>
    <div class="col-sm-6">
        <button type="button" class="preview btn btn-block btn-default btn-sm">
            <span class="glyphicon glyphicon-search"></span>&nbsp;&nbsp;<%= _.t("settings:templatePreview") %>
        </button>
    </div>
</div>