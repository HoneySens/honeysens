<form class="serverConfig form-group">
    <div class="form-group">
        <label for="smtpServer" class="control-label">Server</label>
        <input type="text" name="smtpServer" class="form-control" value="<%- smtpServer %>" placeholder="Hostname oder IP-Adresse" />
    </div>
    <div class="form-group">
        <label class="control-label">Absender</label>
        <input type="text" name="smtpFrom" class="form-control" value="<%- smtpFrom %>" placeholder="user@example.com" />
    </div>
    <div class="form-group">
        <label class="control-label">Benutzer</label>
        <input type="text" name="smtpUser" class="form-control" value="<%- smtpUser %>" placeholder="optional" />
    </div>
    <div class="form-group">
        <label class="control-label">Passwort</label>
        <input type="password" name="smtpPassword" class="form-control" value="<%- smtpPassword %>" placeholder="optional" autocomplete="new-password" />
    </div>
    <div class="row">
        <div class="col-sm-6">
            <button type="button" class="sendTestMail btn btn-block col-lg-6 btn-default btn-sm"><span class="glyphicon glyphicon-envelope"></span>&nbsp;&nbsp;Testmail versenden</button>
        </div>
        <div class="col-sm-6">
            <button type="submit" class="saveSettings btn btn-block col-lg-6 btn-primary btn-sm"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern</button>
        </div>
    </div>
</form>
