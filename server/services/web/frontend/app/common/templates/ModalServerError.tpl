<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= t("genericServerError") %></h4>
        </div>
        <div class="modal-body">
            <p><%- getMessage() %></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-block btn-default" data-dismiss="modal"><%= t("close") %></button>
        </div>
    </div>
</div>