<% if(getStatus() == true) { %>
    <span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;Ok
<% } else if(getStatus() == false) { %>
    <span class="glyphicon glyphicon-warning-sign"></span>&nbsp;&nbsp;Nicht verf&uuml;gbar
<% } else { %>
    <span class="glyphicon glyphicon-hourglass"></span>&nbsp;&nbsp;Abfrage läuft
<% } %>