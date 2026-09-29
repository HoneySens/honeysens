<div>
    <% if(enabled) { %>
        <span class="statusEnabled glyphicon glyphicon-play"></span>&nbsp;&nbsp;<%= t("events:filterEnabled") %><span></span>
    <% } else { %>
        <span class="statusDisabled glyphicon glyphicon-pause"></span>&nbsp;&nbsp;<%= t("events:filterDisabled") %>
    <% } %>
</div>
