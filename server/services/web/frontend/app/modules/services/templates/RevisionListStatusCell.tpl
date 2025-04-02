<% if(getStatus() == true) { %>
    <span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;<%= _.t("services:revisionStatusOk") %>
<% } else if(getStatus() == false) { %>
    <span class="glyphicon glyphicon-warning-sign"></span>&nbsp;&nbsp;<%= _.t("services:revisionStatusError") %>
<% } else { %>
    <span class="glyphicon glyphicon-hourglass"></span>&nbsp;&nbsp;<%= _.t("services:statusQuery") %>
<% } %>