<div class="headerBar">
    <h3><%= _.t("settings:maintenance") %></h3>
</div>
<div class="panel-group" id="maintenance">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#evreset"><%= _.t("settings:removeAllEvents") %></a>
            </h4>
        </div>
        <div id="evreset" class="panel-collapse collapse">
            <div class="panel-body">
                <div class="pull-right">
                    <button type="button" class="removeEvents btn btn-primary btn-sm">
                        <%= _.t("remove") %>
                    </button>
                </div>
                <p><%= _.t("settings:removeAllEventsInfo") %></p>
            </div>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a class="collapsed" data-toggle="collapse" data-parent="#maintenance" href="#caupdate"><%= _.t("settings:internalCA") %></a>
            </h4>
        </div>
        <div id="caupdate" class="panel-collapse collapse">
            <div class="panel-body">
                <p><%= _.t("settings:internalCAInfo") %></p>
                <hr />
                <p>
                    <strong><%= _.t("settings:internalCAFingerprint") %>:</strong> <%- showCaFP() %><br />
                    <strong><%= _.t("settings:internalCAValidUntil") %>:</strong> <%- showCaExpire() %>
                </p>
                <hr />
                <p><strong><%= _.t("settings:internalCAWarning") %></strong></p>
                <button type="button" class="refreshCA btn btn-primary btn-block" >
                    <%= _.t("settings:internalCARenewCerts") %>
                </button>
            </div>
        </div>
    </div>
</div>
