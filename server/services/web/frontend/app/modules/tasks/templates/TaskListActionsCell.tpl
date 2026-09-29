<% if(status === TaskStatus.SCHEDULED) { %>
    <button type="button" class="removeTask btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('cancel') %>">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
<% } %>
<% if(status == TaskStatus.ERROR) { %>
    <button type="button" class="removeTask btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('hide') %>">
        <span class="glyphicon glyphicon-ok"></span>
    </button>
<% } %>
<% if(status === TaskStatus.DONE) { %>
    <% if(type === TaskType.UPLOAD_VERIFIER) { %>
        <button type="button" class="inspectUpload btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('show') %>">
            <span class="glyphicon glyphicon-search"></span>
        </button>
    <% } else if(type === TaskType.EMAIL_EMITTER) { %>
        <button type="button" class="inspectTestMail btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('show') %>">
            <span class="glyphicon glyphicon-search"></span>
        </button>
    <% } %>
    <button type="button" class="removeTask btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('hide') %>">
        <span class="glyphicon <% if(type === TaskType.UPLOAD_VERIFIER) { %>glyphicon-remove<% } else { %>glyphicon-ok<% } %>"></span>
    </button>
<% } %>
<% if(isDownloadable()) { %>
    <button type="button" class="downloadTaskResult btn btn-default btn-xs" data-toggle="tooltip" title="<%= t('tasks:downloadResult') %>">
        <span class="glyphicon glyphicon-download-alt"></span>
    </button>
<% } %>
