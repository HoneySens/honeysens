<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= _.t("confirmationRequired") %></h4>
        </div>
        <div class="modal-body">
            <p><%= msg %></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><%= _.t("cancel") %></button>
            <button type="button" class="btn btn-primary"><%= _.t("continue") %></button>
        </div>
    </div>
</div>