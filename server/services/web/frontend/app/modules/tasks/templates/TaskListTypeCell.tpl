<% if(type == _.templateHelpers.getModels().Task.type.SENSORCFG_CREATOR) { %>
    <span class="glyphicon glyphicon-compressed"></span>&nbsp;&nbsp;<%= _.t("tasks:typeSensorCfgCreator") %>
<% } else if(type == _.templateHelpers.getModels().Task.type.UPLOAD_VERIFIER) { %>
<span class="glyphicon glyphicon-upload"></span>&nbsp;&nbsp;<%= _.t("tasks:typeUploadVerifier") %>
<% } else if(type == _.templateHelpers.getModels().Task.type.REGISTRY_MANAGER) { %>
    <span class="glyphicon glyphicon-transfer"></span>&nbsp;&nbsp;<%= _.t("tasks:typeRegistryManager") %>
<% } else if(type == _.templateHelpers.getModels().Task.type.EVENT_EXTRACTOR) { %>
    <span class="glyphicon glyphicon-export"></span>&nbsp;&nbsp;<%= _.t("tasks:typeEventExtractor") %>
<% } else if(type == _.templateHelpers.getModels().Task.type.EVENT_FORWARDER) { %>
    <span class="glyphicon glyphicon-send"></span>&nbsp;&nbsp;<%= _.t("tasks:typeEventForwarder") %>
<% } else if(type == _.templateHelpers.getModels().Task.type.EMAIL_EMITTER) { %>
    <span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;<%= _.t("tasks:typeEmailEmitter") %>
<% } %>