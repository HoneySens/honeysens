<div class="row eventDetails">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-list-alt"></span>&nbsp;Ereignisdetails</h1>
        <div class="row">
            <p class="col-sm-2"><strong>Zeitpunkt</strong></p>
            <p class="col-sm-9"><%- showTimestamp() %></p>
        </div>
        <div class="row">
            <p class="col-sm-2"><strong>Sensor</strong></p>
            <p class="col-sm-9"><%- showSensor() %></p>
        </div>
        <div class="row">
            <p class="col-sm-2"><strong>Klassifikation</strong></p>
            <p class="col-sm-9"><%- showClassification() %></p>
        </div>
        <div class="row">
            <p class="col-sm-2"><strong>Quelle</strong></p>
            <p class="col-sm-9"><%- source %></p>
        </div>
        <div class="row">
            <p class="col-sm-2"><strong>Details</strong></p>
            <p class="col-sm-9"><%- summary %></p>
        </div>
        <div class="row">
            <div id="detailLists" class="panel-group col-sm-12">
                <div class="detailsDataList"></div>
                <div class="detailsInteractionList"></div>
                <div class="packetList"></div>
            </div>
        </div>
        <hr />
        <div class="form-group">
            <button type="button" class="btn btn-block btn-default"><span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;Schlie&szlig;en</button>
        </div>
    </div>
</div>