<form class="form-group">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="preventEventDeletionByManagers" <% if(preventEventDeletionByManagers) { %>checked<% } %>>
            <%= _.t("settings:permissionsPreventEventDeletion") %>
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="preventSensorDeletionByManagers" <% if(preventSensorDeletionByManagers) { %>checked<% } %>>
            <%= _.t("settings:permissionsPreventSensorDeletion") %>
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="requireEventComment" <% if(requireEventComment) { %>checked<% } %>>
            <%= _.t("settings:permissionsRequireEventComment") %>
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="requireFilterDescription" <% if(requireFilterDescription) { %>checked<% } %>>
            <%= _.t("settings:permissionsRequireFilterDescription") %>
        </label>
    </div>
</form>