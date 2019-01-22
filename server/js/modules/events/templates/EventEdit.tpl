<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-pencil"></span>&nbsp;Ereignisse bearbeiten</h1>
        <div class="form-group">
            <div class="alert alert-danger">
                <strong><%- items.length %></strong>&nbsp;Ereignis(se) ausgewählt
            </div>
        </div>
        <div class="form-group">
            <label for="statusCode" class="control-label">Status</label>
            <select class="form-control" name="statusCode">
                <option value="-1" selected>(unver&auml;ndert)</option>
                <option value="<%- _.templateHelpers.getModels().Event.status.UNEDITED %>">Neu</option>
                <option value="<%- _.templateHelpers.getModels().Event.status.BUSY %>">In Bearbeitung</option>
                <option value="<%- _.templateHelpers.getModels().Event.status.RESOLVED %>">Erledigt</option>
                <option value="<%- _.templateHelpers.getModels().Event.status.IGNORED %>">Ignoriert</option>
            </select>
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