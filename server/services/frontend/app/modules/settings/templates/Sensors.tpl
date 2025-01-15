<form class="form-group">
    <div class="form-group has-feedback">
        <label for="updateInterval" class="control-label">Update-Intervall in Minuten</label>
        <input type="number" name="updateInterval" class="form-control" value="<%- sensorsUpdateInterval %>" required min="1" max="60" data-max-error="Das Intervall muss minimal 1 und maximal 60 Minuten betragen"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="serviceNetwork" class="control-label">Interner Netzbereich f&uuml;r Honeypot-Services</label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Spezifiziert den IP-Adressbereich, den Sensordienste zur internen Adressierung nutzen. Falls dieser mit lokalen Adressbereichen im Konflikt steht, ist hier ein freier und ungenutzter Adessraum anzugeben.">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$" data-pattern-error="Netzbereich bitte als IP-Adresse mit Netzmaske (z.B. 192.168.1.0/24) spezifizieren" type="text" class="form-control" name="serviceNetwork" value="<%- sensorsServiceNetwork %>" required />
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="timeoutThreshold" class="control-label">Timeout-Schwelle in Minuten</label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Bestimmt, nach wie vielen Minuten Verzögerung zusätzlich zum Update-Intervall ein Sensor als 'offline' gemeldet wird. In instabilen Netzen und bei häufigen kurzzeitigen Timeouts sollte dieser Wert über den Standardwert von '1' hinaus angehoben werden.">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input type="number" name="timeoutThreshold" class="form-control" value="<%- sensorsTimeoutThreshold %>" required min="1" max="1440" data-min-error="Der kleinstmögliche Schwellwert liegt bei einer Minute" data-max-error="Der Schwellwert muss zwischen 1 und maximal 1440 Minuten liegen"/>
        </div>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
    </button>
</form>