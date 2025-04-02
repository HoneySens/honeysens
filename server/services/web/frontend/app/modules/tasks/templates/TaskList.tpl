<div class="col-sm-12">
    <div class="well"><%= _.t("tasks:listInfo") %></div>
    <div class="headerBar form-inline clearfix">
        <div class="form-group">
            <label><%= _.t("tasks:status") %>:&nbsp;</label>
            <span id="taskWorkerStatus" class="help-block" style="display: inline-block;"><%= _.t("tasks:statusQuerying") %></span>
            <span id="taskWorkerQueue" class="hidden">
                (<%= _.t("tasks:statusQueueLength", {count: '<span id="taskWorkerQueueLength"></span>'}) %>)
            </span>
        </div>
    </div>
    <div class="table-responsive"></div>
</div>
