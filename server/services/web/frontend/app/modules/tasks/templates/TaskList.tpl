<div class="col-sm-12">
    <div class="well"><%= t("tasks:listInfo") %></div>
    <div class="headerBar form-inline clearfix">
        <div class="form-group">
            <label><%= t("tasks:status") %>:&nbsp;</label>
            <span id="taskWorkerStatus" class="help-block" style="display: inline-block;"><%= t("tasks:statusQuerying") %></span>
            <span id="taskWorkerQueue" class="hidden">
                (<%= t("tasks:statusQueueLength", {count: '<span id="taskWorkerQueueLength"></span>'}) %>)
            </span>
        </div>
    </div>
    <div class="table-responsive"></div>
</div>
