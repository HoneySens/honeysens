<form class="form-group">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="archivePrefer" <% if(archivePrefer) { %>checked<% } %>>
            Beim L&ouml;schen von Ereignissen ist "archivieren" vorausgew&auml;hlt
        </label>
    </div>
    <div class="form-group has-feedback">
        <label for="archiveMoveKays" class="control-label">Erledigte und ignorierte Ereignisse automatisch archivieren nach (Tagen)</label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Legt fest, nach wie vielen Tagen ohne Änderungen erledigte und ignorierte Ereignisse automatisch archiviert werden sollen. Ein Wert von '0' deaktiviert das automatische Archivieren.">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input type="number" name="archiveMoveDays" class="form-control" value="<%- archiveMoveDays %>" required min="0" max="65535" />
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="archiveKeepDays" class="control-label">Aufbewahrungszeitraum für archivierte Ereignisse in Tagen</label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Legt fest, nach wie vielen Tagen bereits archivierte Ereignisse automatisch gelöscht werden sollen. Ein Wert von '0' deaktiviert das automatische Löschen.">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input type="number" name="archiveKeepDays" class="form-control" value="<%- archiveKeepDays %>" required min="0" max="65535" />
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
    </button>
</form>