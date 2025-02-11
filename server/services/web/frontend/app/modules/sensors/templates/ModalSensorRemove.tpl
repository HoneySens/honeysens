<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= _.t("sensors:sensorRemoveHeader") %></h4>
        </div>
        <div class="modal-body">
            <p><%= _.t("sensors:sensorRemovePrompt", {name: `<strong>${name}</strong>`}) %></p>
            <p><%= _.t("sensors:sensorRemoveHint") %></p>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="archive" <% if(!_.templateHelpers.isAllowed('events', 'delete')) { %>disabled="disabled"<% } %><% if(archivePrefer() || !_.templateHelpers.isAllowed('events', 'delete')) { %>checked<% } %>><%= _.t("accounts:archivePrompt") %>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><%= _.t("cancel") %></button>
            <button type="button" class="btn btn-primary" autofocus>
                <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<%= _.t("remove") %>
            </button>
        </div>
    </div>
</div>