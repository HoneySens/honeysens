<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= t("services:revisionRemoveHeader") %></h4>
        </div>
        <div class="modal-body">
            <p><%= t("services:revisionRemovePrompt", {revision: `<strong>${revision}</strong>`}) %></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><%= t("cancel") %></button>
            <button type="button" class="btn btn-primary" autofocus>
                <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<%= t("remove") %>
            </button>
        </div>
    </div>
</div>