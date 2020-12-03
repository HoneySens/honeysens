<% if(type == _.templateHelpers.getModels().Task.type.SENSORCFG_CREATOR) { %>
    <span class="glyphicon glyphicon-compressed"></span>&nbsp;&nbsp;Erzeugung einer Sensorkonfiguration
<% } else if(type == _.templateHelpers.getModels().Task.type.UPLOAD_VERIFIER) { %>
<span class="glyphicon glyphicon-upload"></span>&nbsp;&nbsp;Upload eines Dateiarchivs
<% } else if(type == _.templateHelpers.getModels().Task.type.REGISTRY_MANAGER) { %>
    <span class="glyphicon glyphicon-transfer"></span>&nbsp;&nbsp;Service-Upload in die Registry
<% } else if(type == _.templateHelpers.getModels().Task.type.EVENT_EXTRACTOR) { %>
    <span class="glyphicon glyphicon-export"></span>&nbsp;&nbsp;Ereignisexport
<% } else if(type == _.templateHelpers.getModels().Task.type.EVENT_FORWARDER) { %>
    <span class="glyphicon glyphicon-send"></span>&nbsp;&nbsp;Ereignisweiterleitung
<% } else if(type == _.templateHelpers.getModels().Task.type.EMAIL_EMITTER) { %>
    <span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;Versand einer Testnachricht
<% } %>