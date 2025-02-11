<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-pencil"></span>&nbsp;
            <% if(isMultiEdit()) { %><%= _.t("events:eventUpdateMulti") %><% } else { %><%= _.t("events:eventUpdateSingle", {id: id}) %><% } %>
        </h1>
        <form>
            <% if(isMultiEdit()) { %>
                <div class="form-group">
                    <div class="alert alert-info">
                        <%= _.t("events:eventSelectionCounter", {count: `<strong>${total}</strong>`}) %>
                    </div>
                </div>
            <% } %>
            <div class="form-group">
                <label for="statusCode" class="control-label"><%= _.t("events:eventStatus") %></label>
                <select class="form-control" name="statusCode">
                    <% if(isMultiEdit()) { %><option value="-1" selected>(<%= _.t("events:eventStatusNoChange") %>)</option><% } %>
                    <option value="<%- _.templateHelpers.getModels().Event.status.UNEDITED %>"><%= _.t("events:eventStatusUnedited") %></option>
                    <option value="<%- _.templateHelpers.getModels().Event.status.BUSY %>"><%= _.t("events:eventStatusBusy") %></option>
                    <option value="<%- _.templateHelpers.getModels().Event.status.RESOLVED %>"><%= _.t("events:eventStatusResolved") %></option>
                    <option value="<%- _.templateHelpers.getModels().Event.status.IGNORED %>"><%= _.t("events:eventStatusIgnored") %></option>
                </select>
            </div>
            <div class="form-group">
                <label for="comment" class="control-label"><%= _.t("events:eventComment") %></label>
                <textarea rows="10" class="form-control" name="comment" maxlength="65535" autofocus style="resize: none;" <% if(isMultiEdit()) { %>placeholder="<%= _.t('events:eventCommentPlaceholder') %>"<% } %>></textarea>
            </div>
            <p><%= _.t("events:eventLastModificationTime") %>: <strong><%- showLastModificationTime() %></strong></p>
            <hr />
            <div class="form-group">
                <div class="btn-group btn-group-justified">
                    <div class="btn-group">
                        <button type="button" class="cancel btn btn-default" data-dismiss="modal"><%= _.t("cancel") %></button>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>