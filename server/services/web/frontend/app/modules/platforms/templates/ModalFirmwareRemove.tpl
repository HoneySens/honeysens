<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= t("platforms:firmwareRemoveHeader") %></h4>
        </div>
        <div class="modal-body">
            <p><%= t("platforms:firmwareRemovePrompt", {name: `<strong>${name}</strong>`, version: `<strong>${version}</strong>`}) %></p>
            <% if(hasAffectedSensors()) { %>
                <p><strong><%= t("caution") %>:</strong> <%= t("platforms:firmwareRemoveSensorInfo") %></p>
                <p><%= t("platforms:firmwareRemoveSensorAffected") %>: <%- getAffectedSensors() %></p>
            <% } %>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><%= t("cancel") %></button>
            <button type="button" class="btn btn-primary" autofocus>
                <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<%= t("remove") %>
            </button>
        </div>
    </div>
</div>