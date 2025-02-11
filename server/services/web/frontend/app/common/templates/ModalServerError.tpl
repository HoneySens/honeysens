<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= _.t("genericServerError") %></h4>
        </div>
        <div class="modal-body">
            <p><%- getMessage() %></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal"><%= _.t("close") %></button>
        </div>
    </div>
</div>