<% if(status === _.templateHelpers.getModels().Task.status.SCHEDULED) { %>
    <button type="button" class="removeTask btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('cancel') %>">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
<% } %>
<% if(status == _.templateHelpers.getModels().Task.status.ERROR) { %>
    <button type="button" class="removeTask btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('hide') %>">
        <span class="glyphicon glyphicon-ok"></span>
    </button>
<% } %>
<% if(status === _.templateHelpers.getModels().Task.status.DONE) { %>
    <% if(type === _.templateHelpers.getModels().Task.type.UPLOAD_VERIFIER) { %>
        <button type="button" class="inspectUpload btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('show') %>">
            <span class="glyphicon glyphicon-search"></span>
        </button>
    <% } else if(type === _.templateHelpers.getModels().Task.type.EMAIL_EMITTER) { %>
        <button type="button" class="inspectTestMail btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('show') %>">
            <span class="glyphicon glyphicon-search"></span>
        </button>
    <% } %>
    <button type="button" class="removeTask btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('hide') %>">
        <span class="glyphicon <% if(type === _.templateHelpers.getModels().Task.type.UPLOAD_VERIFIER) { %>glyphicon-remove<% } else { %>glyphicon-ok<% } %>"></span>
    </button>
<% } %>
<% if(isDownloadable()) { %>
    <button type="button" class="downloadTaskResult btn btn-default btn-xs" data-toggle="tooltip" title="<%= _.t('tasks:downloadResult') %>">
        <span class="glyphicon glyphicon-download-alt"></span>
    </button>
<% } %>
