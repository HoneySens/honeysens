<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= _.t("sensors:lastSensorStatus") %></h4>
        </div>
        <div class="modal-body">
            <table class="table table-striped">
                <thead>
                    <th><%= _.t("timestamp") %></th>
                    <th><%= _.t("sensors:sensorStatusVersion") %></th>
                    <th><%= _.t("sensors:sensorStatusFreeRAM") %></th>
                    <th><%= _.t("sensors:sensorStatusDiskUsed") %></th>
                    <th><%= _.t("sensors:sensorStatusDiskMax") %></th>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="modal-footer" style="clear: both">
            <button type="button" class="btn btn-block btn-default" data-dismiss="modal" autofocus><%= _.t("close") %></button>
        </div>
    </div>
</div>