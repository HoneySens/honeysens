<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= t("sensors:lastSensorStatus") %></h4>
        </div>
        <div class="modal-body">
            <table class="table table-striped">
                <thead>
                    <th><%= t("timestamp") %></th>
                    <th><%= t("sensors:sensorStatusVersion") %></th>
                    <th><%= t("sensors:sensorStatusFreeRAM") %></th>
                    <th><%= t("sensors:sensorStatusDiskUsed") %></th>
                    <th><%= t("sensors:sensorStatusDiskMax") %></th>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="modal-footer" style="clear: both">
            <button type="button" class="btn btn-block btn-default" data-dismiss="modal" autofocus><%= t("close") %></button>
        </div>
    </div>
</div>