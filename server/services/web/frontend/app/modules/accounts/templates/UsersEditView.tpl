<div class="col-sm-12">
    <div class="headerBar">
        <div class="button-group text-right">
            <button type="button" class="save btn btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %>
            </button>
            <button type="button" class="cancel btn btn-default btn-sm"><%= _.t("cancel") %></button>
        </div>
        <h3><% if(isEdit()) { %><%= _.t("accounts:userUpdateHeader") %><% } else { %><%= _.t("accounts:userAddHeader") %><% } %></h3>
    </div>
    <form class="form-group">
        <div class="form-group has-feedback">
            <label for="username" class="control-label"><%= _.t("accounts:userLogin") %></label>
            <input type="text" name="username" class="form-control" placeholder="<%= _.t('accounts:userLoginPlaceholder') %>" value="<%- name %>" required autocomplete="off" pattern="^[a-zA-Z0-9]+$" data-pattern-error="<%= _.t('nameValidationError') %>" minlength="1" maxlength="255" data-maxlength-error="<%= _.t('lengthValidationError', {min: 1, max: 255}) %>" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group">
            <label for="domain" class="control-label"><%= _.t("accounts:userAuth") %></label>
            <select class="form-control" name="domain" <% if(isEdit() && id == 1) { %>disabled<% } %>>
                <option value="<%- _.templateHelpers.getModels().User.domain.LOCAL %>" <%- domain === _.templateHelpers.getModels().User.domain.LOCAL ? 'selected' : void 0 %>><%= _.t("accounts:userAuthLocal") %></option>
                <option value="<%- _.templateHelpers.getModels().User.domain.LDAP %>" <%- domain === _.templateHelpers.getModels().User.domain.LDAP ? 'selected' : void 0 %>><%= _.t("accounts:userAuthLDAP") %></option>
            </select>
        </div>
        <div class="form-group has-feedback password">
            <label for="password" class="control-label"><%= _.t("accounts:userPassword") %></label>
            <input type="password" name="password" id="password" class="form-control" placeholder="<% if(isEdit()) { %><%= _.t('accounts:userPasswordNew') %><% } else { %><%= _.t('accounts:userPassword') %><% } %>" value="<%- password %>" data-minlength="6" data-minlength-error="<%= _.t('lengthValidationError', {min: 6, max: 255}) %>" maxlength="255" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group has-feedback password">
            <label for="confirmPassword" class="control-label"><%= _.t("accounts:userPasswordRepeat") %></label>
            <input type="password" class="form-control" id="confirmPassword" class="form-control" placeholder="<%= _.t('accounts:userPasswordRepeat') %>" value="<%- password %>" data-match="#password" data-match-error="<%= _.t('accounts:passwordMatchValidationError') %>" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="checkbox requirePasswordChange">
            <label>
                <input type="checkbox" name="requirePasswordChange" <% if(require_password_change) { %>checked<% } %>>
                <%= _.t("accounts:userForcePasswordChange") %>
            </label>
        </div>
        <div class="form-group has-feedback">
            <label for="fullName" class="control-label"><%= _.t("accounts:userFullName") %></label>
            <input type="text" name="fullName" class="form-control" value="<%- full_name %>" placeholder="<%= _.t('accounts:userFullNamePlaceholder') %>" />
        </div>
        <div class="form-group has-feedback">
            <label for="email" class="control-label"><%= _.t("accounts:userEMail") %></label>
            <input type="email" name="email" class="form-control" placeholder="<%= _.t('accounts:userEMailPlaceholder') %>" value="<%- email %>" required />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group">
            <label for="role" class="control-label"><%= _.t("accounts:role") %></label>
            <select class="form-control" name="role" <% if(isEdit() && id == 1) { %>disabled<% } %>>
                <option value="<%- _.templateHelpers.getModels().User.role.OBSERVER %>" <%- role === _.templateHelpers.getModels().User.role.OBSERVER ? 'selected' : void 0 %>><%= _.t("accounts:roleObserver") %></option>
                <option value="<%- _.templateHelpers.getModels().User.role.MANAGER %>" <%- role === _.templateHelpers.getModels().User.role.MANAGER ? 'selected' : void 0 %>><%= _.t("accounts:roleManager") %></option>
                <option value="<%- _.templateHelpers.getModels().User.role.ADMIN %>" <%- role === _.templateHelpers.getModels().User.role.ADMIN ? 'selected' : void 0 %>><%= _.t("accounts:roleAdmin") %></option>
            </select>
        </div>
        <div class="form-group">
            <dl>
                <dt><%= _.t("accounts:roleObserver") %></dt>
                <dd><%= _.t("accounts:roleObserverDesc") %></dd>
                <dt><%= _.t("accounts:roleManager") %></dt>
                <dd><%= _.t("accounts:roleManagerDesc") %></dd>
                <dt><%= _.t("accounts:roleAdmin") %></dt>
                <dd><%= _.t("accounts:roleAdminDesc") %></dd>
            </dl>
        </div>
        <fieldset>
            <legend><%= _.t("accounts:userNotificationsHeader") %></legend>
            <p><%= _.t("accounts:userNotificationsDesc") %></p>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="notifyOnSystemState" <% if(notify_on_system_state) { %>checked<% } %>>
                    <%= _.t("accounts:userNotificationsSystem") %>
                </label>
            </div>
        </fieldset>
        <fieldset>
            <legend><%= _.t("accounts:divisionsHeader") %></legend>
            <% if(divisions.length == 0) { %>
            <p><%= _.t("accounts:userDivisionsNone") %></p>
            <% } else { %>
                <p><%= _.t("accounts:userDivisionsHeader") %></p>
                <ul><%= getDivisionList() %></ul>
                <p><%= _.t("accounts:userDivisionsInfo") %></p>
            <% } %>
        </fieldset>
    </form>
</div>
