<% if(type == TaskType.SENSORCFG_CREATOR) { %>
    <span class="glyphicon glyphicon-compressed"></span>&nbsp;&nbsp;<%= t("tasks:typeSensorCfgCreator") %>
<% } else if(type == TaskType.UPLOAD_VERIFIER) { %>
<span class="glyphicon glyphicon-upload"></span>&nbsp;&nbsp;<%= t("tasks:typeUploadVerifier") %>
<% } else if(type == TaskType.REGISTRY_MANAGER) { %>
    <span class="glyphicon glyphicon-transfer"></span>&nbsp;&nbsp;<%= t("tasks:typeRegistryManager") %>
<% } else if(type == TaskType.EVENT_EXTRACTOR) { %>
    <span class="glyphicon glyphicon-export"></span>&nbsp;&nbsp;<%= t("tasks:typeEventExtractor") %>
<% } else if(type == TaskType.EVENT_FORWARDER) { %>
    <span class="glyphicon glyphicon-send"></span>&nbsp;&nbsp;<%= t("tasks:typeEventForwarder") %>
<% } else if(type == TaskType.EMAIL_EMITTER) { %>
    <span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;<%= t("tasks:typeEmailEmitter") %>
<% } %>