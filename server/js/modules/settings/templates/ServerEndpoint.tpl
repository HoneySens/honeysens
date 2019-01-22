<form class="form-group">
    <div class="form-group">
        <label for="serverHost" class="control-label">Host</label>
        <input type="text" name="serverHost" class="form-control" placeholder="Hostname oder IP-Adresse" value="<%- serverHost %>" />
    </div>
    <div class="form-group">
        <label for="serverPortHTTPS" class="control-label">HTTPS-Port (API)</label>
        <input type="text" name="serverPortHTTPS" class="form-control" placeholder="Standard: 443" value="<%- serverPortHTTPS %>" />
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
    </button>
</form>