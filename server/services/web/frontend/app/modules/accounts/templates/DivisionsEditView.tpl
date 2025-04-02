<div class="col-sm-12">
    <div class="headerBar">
        <h3><%= _.t("accounts:updateDivision") %></h3>
    </div>
    <form class="form-horizontal" role="form">
        <div class="form-group has-feedback">
            <label for="divisionname" class="col-sm-1 control-label"><%= _.t("name") %></label>
            <div class="col-sm-5">
                <input pattern="^[a-zA-Z0-9]+$" data-pattern-error="<%= _.t('nameValidationError')%>" data-maxlength-error="<%= _.t('lengthValidationError', { min: 1, max: 255 }) %>" maxlength="255" minlength="1" type="text" class="form-control" name="divisionname" placeholder="<%= _.t('name') %>" value="<%- name %>" required />
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div> 
    </form>
    <div class="userList"></div>
    <div class="contactList"></div>
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