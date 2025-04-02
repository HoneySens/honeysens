<div>
    <% if(enabled) { %>
        <span class="statusEnabled glyphicon glyphicon-play"></span>&nbsp;&nbsp;<%= _.t("events:filterEnabled") %><span></span>
    <% } else { %>
        <span class="statusDisabled glyphicon glyphicon-pause"></span>&nbsp;&nbsp;<%= _.t("events:filterDisabled") %>
    <% } %>
</div>
