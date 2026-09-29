<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= t("services:serviceRemoveHeader") %></h4>
        </div>
        <div class="modal-body">
            <p><%= t("services:serviceRemovePromptP1", {name: name}) %></p>
            <p><%= t("services:serviceRemovePromptP2") %></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><%= t("cancel") %></button>
            <button type="button" class="btn btn-primary" autofocus>
                <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<%= t("remove") %>
            </button>
        </div>
    </div>
</div>