<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= _.t("tasks:awaitHeader") %></h4>
        </div>
        <div class="modal-body">
            <% if(status == _.templateHelpers.getModels().Task.status.SCHEDULED || status == _.templateHelpers.getModels().Task.status.RUNNING) { %>
                <div class="alert alert-info">
                    <div class="pull-left loadingInline"></div>&nbsp;<%= _.t("tasks:awaitStatusRunning") %></span>
                </div>
                <div class="well"><%= _.t("tasks:awaitStatusRunningInfo") %></div>
            <% } else if(status == _.templateHelpers.getModels().Task.status.DONE) { %>
                <div class="alert alert-success"><%= _.t("tasks:awaitStatusDone") %></div>
            <% } else { %>
                <div class="alert alert-danger"><%= _.t("tasks:awaitStatusError") %></div>
            <% } %>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-block btn-default" data-dismiss="modal" autofocus><%= _.t("close") %></button>
        </div>
    </div>
</div>