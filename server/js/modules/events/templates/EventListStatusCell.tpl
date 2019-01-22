<button type="button" class="editStatus pull-right btn btn-default btn-xs">
    <span class="glyphicon glyphicon-pencil"></span>
</button>
<% if(status == _.templateHelpers.getModels().Event.status.UNEDITED) { %>Neu
<% } else if(status == _.templateHelpers.getModels().Event.status.BUSY) { %>In Bearbeitung
<% } else if(status == _.templateHelpers.getModels().Event.status.RESOLVED) { %>Erledigt
<% } else if(status == _.templateHelpers.getModels().Event.status.IGNORED) { %>Ignoriert
<% } else { %>Ung&uuml;ltig<% } %>
<div class="popover">
    <div class="popover-content">
        <form class="form-horizontal" role="form">
            <div class="form-group">
                <label for="statusCode" class="col-sm-4 control-label">Status</label>
                <div class="col-sm-5">
                    <select class="form-control statusCode" <% if(_.templateHelpers.isAllowed('events', 'update') == false) { %>disabled<% } %>>
                        <option value="<%- _.templateHelpers.getModels().Event.status.UNEDITED %>" <%- status == _.templateHelpers.getModels().Event.status.UNEDITED ? 'selected' : void 0 %>>Neu</option>
                        <option value="<%- _.templateHelpers.getModels().Event.status.BUSY %>" <%- status == _.templateHelpers.getModels().Event.status.BUSY ? 'selected' : void 0 %>>In Bearbeitung</option>
                        <option value="<%- _.templateHelpers.getModels().Event.status.RESOLVED %>" <%- status == _.templateHelpers.getModels().Event.status.RESOLVED ? 'selected' : void 0 %>>Erledigt</option>
                        <option value="<%- _.templateHelpers.getModels().Event.status.IGNORED %>" <%- status == _.templateHelpers.getModels().Event.status.IGNORED ? 'selected' : void 0 %>>Ignoriert</option>
                    </select>
                </div>
                <div class="col-sm-3">
                <% if(_.templateHelpers.isAllowed('events', 'update')) { %>
                    <button type="button" class="btn btn-primary btn-block">
                        <span class="glyphicon glyphicon-ok"></span>
                    </button>
                <% } %>
                </div>
            </div>
            <div class="form-group">
                <label for="commentText" class="col-sm-4 control-label">Kommentar</label>
                <div class="col-sm-8">
                    <textarea class="form-control" rows="2" <% if(_.templateHelpers.isAllowed('events', 'update') == false) { %>disabled<% } %>><%- comment %></textarea>
                </div>
            </div>
        </form>
    </div>
</div>