<% if(getStatus() == true) { %>
    <span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;<%= t("services:revisionStatusOk") %>
<% } else if(getStatus() == false) { %>
    <span class="glyphicon glyphicon-warning-sign"></span>&nbsp;&nbsp;<%= t("services:revisionStatusError") %>
<% } else { %>
    <span class="glyphicon glyphicon-hourglass"></span>&nbsp;&nbsp;<%= t("services:statusQuery") %>
<% } %>