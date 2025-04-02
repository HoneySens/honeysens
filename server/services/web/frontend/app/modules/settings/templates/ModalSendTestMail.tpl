<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4><%= _.t("settings:emailTest") %></h4>
        </div>
        <div class="modal-body">
            <form class="form-group">
                <div class="form-group has-feedback">
                    <label for="targetAddress" class="control-label"><%= _.t("settings:emailTestRecipient") %></label>
                    <input type="email" name="recipient" class="form-control" placeholder="<%= _.t('emailAddress') %>" value="<%- getRecipient() %>" <% if(isDone()) { %>disabled<% } %> required />
                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                    <div class="help-block with-errors"></div>
                </div>
            </form>
            <div class="alert alert-info sendPending hidden">
                <div class="pull-left loadingInline"></div>&nbsp;<%= _.t("settings:emailTestSending") %>
            </div>
            <% if(isDone()) { %>
                <% if(isError()) { %>
                    <div class="well well-sm sendError">
                        <p><strong><%= _.t("settings:emailTestError") %>:</strong></p>
                        <code><%- getError() %></code>
                    </div>
                <% } else { %>
                    <div class="alert alert-success sendSuccess"><%= _.t("settings:emailTestSuccess") %></div>
                <% } %>
            <% } %>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default"><%= _.t("close") %></button>
            <% if(!isDone()) { %>
                <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;<%= _.t("settings:emailTestButton") %></button>
            <% } %>
        </div>
    </div>
</div>
