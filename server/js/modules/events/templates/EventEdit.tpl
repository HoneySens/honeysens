<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-pencil"></span>&nbsp;
            <% if(getEventCount() > 1) { %>Ereignisse bearbeiten<% } else { %> Ereignis <%- id %> bearbeiten<% } %>
        </h1>
        <% if(getEventCount() > 1) { %>
            <div class="form-group">
                <div class="alert alert-info">
                    <strong><%- items.length %></strong>&nbsp;Ereignis(se) ausgewählt
                </div>
            </div>
        <% } %>
        <div class="form-group">
            <label for="statusCode" class="control-label">Status</label>
            <select class="form-control" name="statusCode">
                <% if(getEventCount() > 1) { %><option value="-1" selected>(unver&auml;ndert)</option><% } %>
                <option value="<%- _.templateHelpers.getModels().Event.status.UNEDITED %>">Neu</option>
                <option value="<%- _.templateHelpers.getModels().Event.status.BUSY %>">In Bearbeitung</option>
                <option value="<%- _.templateHelpers.getModels().Event.status.RESOLVED %>">Erledigt</option>
                <option value="<%- _.templateHelpers.getModels().Event.status.IGNORED %>">Ignoriert</option>
            </select>
        </div>
        <div class="form-group">
            <label for="comment" class="control-label">Kommentar</label>
            <textarea rows="10" class="form-control" name="comment" <% if(getEventCount() > 1) { %>placeholder="Hier eingegebener Text wird alle Einzelkommentare überschreiben"<% } %>></textarea>
        </div>
        <hr />
        <div class="form-group">
            <div class="btn-group btn-group-justified">
                <div class="btn-group">
                    <button type="button" class="cancel btn btn-default" data-dismiss="modal">Abbrechen</button>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern</button>
                </div>
            </div>
        </div>
    </div>
</div>