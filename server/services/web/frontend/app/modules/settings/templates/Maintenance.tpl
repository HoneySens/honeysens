<div class="headerBar">
    <h3><%= t("settings:maintenance") %></h3>
</div>
<div class="panel-group" id="maintenance">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#evreset"><%= t("settings:removeAllEvents") %></a>
            </h4>
        </div>
        <div id="evreset" class="panel-collapse collapse">
            <div class="panel-body">
                <div class="pull-right">
                    <button type="button" class="removeEvents btn btn-primary btn-sm">
                        <%= t("remove") %>
                    </button>
                </div>
                <p><%= t("settings:removeAllEventsInfo") %></p>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#caupdate"><%= t("settings:internalCA") %></a>
            </h4>
        </div>
        <div id="caupdate" class="panel-collapse collapse">
            <div class="panel-body">
                <p><%= t("settings:internalCAInfo") %></p>
                <hr />
                <p>
                    <strong><%= t("settings:internalCAFingerprint") %>:</strong> <%- showCaFP() %><br />
                    <strong><%= t("settings:internalCAValidUntil") %>:</strong> <%- showCaExpire() %>
                </p>
                <hr />
                <p><strong><%= t("settings:internalCAWarning") %></strong></p>
                <button type="button" class="refreshCA btn btn-primary btn-block" >
                    <%= t("settings:internalCARenewCerts") %>
                </button>
            </div>
        </div>
    </div>
</div>
