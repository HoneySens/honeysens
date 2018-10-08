<form class="form-group">
    <div class="form-group has-feedback">
        <label for="serverHost" class="control-label">Host</label>
        <input type="text" name="serverHost" class="form-control" placeholder="Hostname oder IP-Adresse" value="<%- serverHost %>" required/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="serverPortHTTPS" class="control-label">HTTPS-Port (API)</label>
        <input type="number" name="serverPortHTTPS" class="form-control" placeholder="Standard: 443" value="<%- serverPortHTTPS %>" required min="0" max="65535" data-max-error="Der Port muss zwischen 0 und 65535 liegen" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
    </button>
</form>