<div class="col-sm-12">
    <div class="headerBar">
        <div class="button-group text-right">
            <button type="button" class="save btn btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= t("save") %>
            </button>
            <button type="button" class="cancel btn btn-default btn-sm"><%= t("cancel") %></button>
        </div>
        <h3><% if(isEdit()) { %><%= t("accounts:userUpdateHeader") %><% } else { %><%= t("accounts:userAddHeader") %><% } %></h3>
    </div>
    <form class="form-group">
        <div class="form-group has-feedback">
            <label for="username" class="control-label"><%= t("accounts:userLogin") %></label>
            <input type="text" name="username" class="form-control" placeholder="<%= t('accounts:userLoginPlaceholder') %>" value="<%- name %>" required autocomplete="off" pattern="^[a-zA-Z0-9]+$" data-pattern-error="<%= t('nameValidationError') %>" minlength="1" maxlength="255" data-maxlength-error="<%= t('lengthValidationError', {min: 1, max: 255}) %>" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group">
            <label for="domain" class="control-label"><%= t("accounts:userAuth") %></label>
            <select class="form-control" name="domain" <% if(isEdit() && id == 1) { %>disabled<% } %>>
                <option value="<%- UserDomain.LOCAL %>" <%- domain === UserDomain.LOCAL ? 'selected' : void 0 %>><%= t("accounts:userAuthLocal") %></option>
                <option value="<%- UserDomain.LDAP %>" <%- domain === UserDomain.LDAP ? 'selected' : void 0 %>><%= t("accounts:userAuthLDAP") %></option>
            </select>
        </div>
        <div class="form-group has-feedback password">
            <label for="password" class="control-label"><%= t("accounts:userPassword") %></label>
            <input type="password" name="password" id="password" class="form-control" placeholder="<% if(isEdit()) { %><%= t('accounts:userPasswordNew') %><% } else { %><%= t('accounts:userPassword') %><% } %>" value="<%- password %>" data-minlength="6" data-minlength-error="<%= t('lengthValidationError', {min: 6, max: 255}) %>" maxlength="255" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group has-feedback password">
            <label for="confirmPassword" class="control-label"><%= t("accounts:userPasswordRepeat") %></label>
            <input type="password" class="form-control" id="confirmPassword" class="form-control" placeholder="<%= t('accounts:userPasswordRepeat') %>" value="<%- password %>" data-match="#password" data-match-error="<%= t('accounts:passwordMatchValidationError') %>" />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="checkbox requirePasswordChange">
            <label>
                <input type="checkbox" name="requirePasswordChange" <% if(require_password_change) { %>checked<% } %>>
                <%= t("accounts:userForcePasswordChange") %>
            </label>
        </div>
        <div class="form-group has-feedback">
            <label for="fullName" class="control-label"><%= t("accounts:userFullName") %></label>
            <input type="text" name="fullName" class="form-control" value="<%- full_name %>" placeholder="<%= t('accounts:userFullNamePlaceholder') %>" />
        </div>
        <div class="form-group has-feedback">
            <label for="email" class="control-label"><%= t("accounts:userEMail") %></label>
            <input type="email" name="email" class="form-control" placeholder="<%= t('accounts:userEMailPlaceholder') %>" value="<%- email %>" required />
            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
            <div class="help-block with-errors"></div>
        </div>
        <div class="form-group">
            <label for="role" class="control-label"><%= t("accounts:role") %></label>
            <select class="form-control" name="role" <% if(isEdit() && id == 1) { %>disabled<% } %>>
                <option value="<%- UserRole.OBSERVER %>" <%- role === UserRole.OBSERVER ? 'selected' : void 0 %>><%= t("accounts:roleObserver") %></option>
                <option value="<%- UserRole.MANAGER %>" <%- role === UserRole.MANAGER ? 'selected' : void 0 %>><%= t("accounts:roleManager") %></option>
                <option value="<%- UserRole.ADMIN %>" <%- role === UserRole.ADMIN ? 'selected' : void 0 %>><%= t("accounts:roleAdmin") %></option>
            </select>
        </div>
        <div class="form-group">
            <dl>
                <dt><%= t("accounts:roleObserver") %></dt>
                <dd><%= t("accounts:roleObserverDesc") %></dd>
                <dt><%= t("accounts:roleManager") %></dt>
                <dd><%= t("accounts:roleManagerDesc") %></dd>
                <dt><%= t("accounts:roleAdmin") %></dt>
                <dd><%= t("accounts:roleAdminDesc") %></dd>
            </dl>
        </div>
        <fieldset>
            <legend><%= t("accounts:userNotificationsHeader") %></legend>
            <p><%= t("accounts:userNotificationsDesc") %></p>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="notifyOnSystemState" <% if(notify_on_system_state) { %>checked<% } %>>
                    <%= t("accounts:userNotificationsSystem") %>
                </label>
            </div>
        </fieldset>
        <fieldset>
            <legend><%= t("accounts:divisionsHeader") %></legend>
            <% if(divisions.length == 0) { %>
            <p><%= t("accounts:userDivisionsNone") %></p>
            <% } else { %>
                <p><%= t("accounts:userDivisionsHeader") %></p>
                <ul><%= getDivisionList() %></ul>
                <p><%= t("accounts:userDivisionsInfo") %></p>
            <% } %>
        </fieldset>
    </form>
</div>
