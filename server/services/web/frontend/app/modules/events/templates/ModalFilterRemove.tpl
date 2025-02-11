<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Filter entfernen</h4>
        </div>
        <div class="modal-body">
            <p><%= _.t("events:filterRemovePrompt", {filter: `<strong>${name}</strong>`}) %></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><%= _.t("cancel") %></button>
            <button type="button" class="btn btn-primary" autofocus>
                <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<%= _.t("remove") %>
            </button>
        </div>
    </div>
</div>