<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;<% if(isNew()) { %><%= t("accounts:divisionAddHeader") %><% } else { %><%= t("accounts:divisionUpdateHeader") %><% } %></h1>
        <form role="form">
            <div class="form-group has-feedback">
                <label for="divisionname" class="control-label"><%= t("name") %></label>
                <input pattern="^[a-zA-Z0-9]+$" data-pattern-error="<%= t('nameValidationError')%>" data-maxlength-error="<%= t('lengthValidationError', { min: 1, max: 255 }) %>" maxlength="255" minlength="1" type="text" class="form-control" name="divisionname" placeholder="<%= t('name') %>" value="<%- name %>" required />
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </form>
        <div class="userList"></div>
        <div class="contactList"></div>
        <hr />
        <div class="form-group">
            <div class="btn-group btn-group-justified">
                <div class="btn-group">
                    <button type="button" class="cancel btn btn-default"><%= t("cancel") %></button>
                </div>
                <div class="btn-group">
                    <button type="button" class="save btn btn-primary">
                        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= t("save") %>
                    </button>
                </div>
            </div>
        </div>
    </div>
 </div>