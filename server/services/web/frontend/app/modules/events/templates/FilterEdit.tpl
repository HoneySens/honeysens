<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;<% if(isNew()) { %><%= _.t("events:filterAddHeader") %><% } else { %><%= _.t("events:filterUpdateHeader") %><% } %></h1>
        <form role="form">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group has-feedback">
                        <label for="filtername" class="control-label"><%= _.t("name") %></label>
                        <input pattern="^[a-zA-Z0-9._\- ]+$" data-pattern-error="<%= _.t('nameAltValidationError') %>" data-maxlength-error="<% _.t('lengthValidationError', {min: 1, max: 255}) %>" maxlength="255" minlength="1" type="text" class="form-control" name="filtername" placeholder="<%= _.t('events:filterNamePlaceholder') %>" value="<%- name %>" required autofocus />
                        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                        <div class="help-block with-errors"></div>
                    </div>
                    <div class="form-group">
                        <label for="division" class="control-label"><%= _.t("division") %></label>
                        <select class="form-control" name="division">
                            <% _(divisions).each(function(d) { %>
                            <option value="<%- d.id %>" <%- d.id === division ? 'selected' : void 0 %>><%- d.name %></option>
                            <% }); %>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="description" class="control-label"><%= _.t("events:filterDescriptionHeader") %></label>
                        <textarea class="form-control" name="description" style="height: 160px; resize:none;" maxlength="65535" <% if(requireFilterDescription()) { %>required<% } %>></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="col-sm-12">
        <div class="conditionList"></div>
        <hr />
        <div class="form-group">
            <div class="btn-group btn-group-justified">
                <div class="btn-group">
                    <button type="button" class="cancel btn btn-default"><%= _.t("cancel") %></button>
                </div>
                <div class="btn-group">
                    <button type="button" class="save btn btn-primary">
                        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
