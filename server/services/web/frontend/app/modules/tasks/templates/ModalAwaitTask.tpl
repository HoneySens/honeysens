<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= t("tasks:awaitHeader") %></h4>
        </div>
        <div class="modal-body">
            <% if(status == TaskStatus.SCHEDULED || status == TaskStatus.RUNNING) { %>
                <div class="alert alert-info">
                    <div class="pull-left loadingInline"></div>&nbsp;<%= t("tasks:awaitStatusRunning") %></span>
                </div>
                <div class="well"><%= t("tasks:awaitStatusRunningInfo") %></div>
            <% } else if(status == TaskStatus.DONE) { %>
                <div class="alert alert-success"><%= t("tasks:awaitStatusDone") %></div>
            <% } else { %>
                <div class="alert alert-danger"><%= t("tasks:awaitStatusError") %></div>
            <% } %>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-block btn-default" data-dismiss="modal" autofocus><%= t("close") %></button>
        </div>
    </div>
</div>