<form class="form-group">
    <div class="form-group">
        <label for="updateInterval" class="control-label">Update-Intervall in Minuten</label>
        <input type="text" name="updateInterval" class="form-control" value="<%- sensorsUpdateInterval %>" />
    </div>
    <div class="form-group">
        <label for="serviceNetwork" class="control-label">Interner Netzbereich f&uuml;r Honeypot-Services</label>
        <div class="input-group">
            <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Spezifiziert den IP-Adressbereich, den Sensordienste zur internen Adressierung nutzen. Falls dieser mit lokalen Adressbereichen im Konflikt steht, ist hier ein freier und ungenutzter Adessraum anzugeben. ">
                <span class="glyphicon glyphicon-question-sign"></span>
            </div>
            <input type="text" name="serviceNetwork" class="form-control" value="<%- sensorsServiceNetwork %>" />
        </div>
    </div>
    <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
        <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
    </button>
</form>