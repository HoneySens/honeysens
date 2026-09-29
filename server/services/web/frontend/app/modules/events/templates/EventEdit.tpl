<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-pencil"></span>&nbsp;
            <% if(isMultiEdit()) { %><%= t("events:eventUpdateMulti") %><% } else { %><%= t("events:eventUpdateSingle", {id: id}) %><% } %>
        </h1>
        <form>
            <% if(isMultiEdit()) { %>
                <div class="form-group">
                    <div class="alert alert-info">
                        <%= t("events:eventSelectionCounter", {count: `<strong>${total}</strong>`}) %>
                    </div>
                </div>
            <% } %>
            <div class="form-group">
                <label for="statusCode" class="control-label"><%= t("events:eventStatus") %></label>
                <select class="form-control" name="statusCode">
                    <% if(isMultiEdit()) { %><option value="-1" selected>(<%= t("events:eventStatusNoChange") %>)</option><% } %>
                    <option value="<%- EventStatus.UNEDITED %>"><%= t("events:eventStatusUnedited") %></option>
                    <option value="<%- EventStatus.BUSY %>"><%= t("events:eventStatusBusy") %></option>
                    <option value="<%- EventStatus.RESOLVED %>"><%= t("events:eventStatusResolved") %></option>
                    <option value="<%- EventStatus.IGNORED %>"><%= t("events:eventStatusIgnored") %></option>
                </select>
            </div>
            <div class="form-group">
                <label for="comment" class="control-label"><%= t("events:eventComment") %></label>
                <textarea rows="10" class="form-control" name="comment" maxlength="1000" autofocus style="resize: none;" <% if(isMultiEdit()) { %>placeholder="<%= t('events:eventCommentPlaceholder') %>"<% } %>></textarea>
            </div>
            <p><%= t("events:eventLastModificationTime") %>: <strong><%- showLastModificationTime() %></strong></p>
            <hr />
            <div class="form-group">
                <div class="btn-group btn-group-justified">
                    <div class="btn-group">
                        <button type="button" class="cancel btn btn-default" data-dismiss="modal"><%= t("cancel") %></button>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= t("save") %></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>