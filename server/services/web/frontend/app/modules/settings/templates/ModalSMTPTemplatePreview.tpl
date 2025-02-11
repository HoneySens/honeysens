<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= _.t("settings:templatePreview") %></h4>
        </div>
        <div class="modal-body">
            <pre class="prewrap"><%- preview %></pre>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-block btn-default" data-dismiss="modal" autofocus><%= _.t("close") %></button>
        </div>
    </div>
</div>
