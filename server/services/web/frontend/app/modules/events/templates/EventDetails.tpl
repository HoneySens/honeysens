<div class="row eventDetails">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-list-alt"></span>&nbsp;<%= _.t("events:eventDetailsHeader") %></h1>
        <div class="row">
            <p class="col-sm-2"><strong><%= _.t("timestamp") %></strong></p>
            <p class="col-sm-9"><%- showTimestamp() %></p>
        </div>
        <div class="row">
            <p class="col-sm-2"><strong><%= _.t("sensor") %></strong></p>
            <p class="col-sm-9"><%- showSensor() %></p>
        </div>
        <div class="row">
            <p class="col-sm-2"><strong><%= _.t("events:eventClassification") %></strong></p>
            <p class="col-sm-9"><%- showClassification() %></p>
        </div>
        <div class="row">
            <p class="col-sm-2"><strong><%= _.t("events:eventSource") %></strong></p>
            <p class="col-sm-9"><%- source %></p>
        </div>
        <div class="row">
            <p class="col-sm-2"><strong><%= _.t("details") %></strong></p>
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
            <button type="button" class="btn btn-block btn-default" autofocus>
                <span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;<%= _.t("close") %>
            </button>
        </div>
    </div>
</div>