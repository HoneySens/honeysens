<h2>Administrator-Zugang</h2>
<hr />
<form>
    <p>Dieser Assistent wird Sie beim Einrichten eines neuen HoneySens-Servers begleiten.</p>
    <p>Bitte legen Sie zun&auml;chst ein Passwort für den Administrator-Account fest.</p>
    <div class="form-group has-feedback">
        <label for="adminPassword">Administrator-Passwort</label>
        <input type="password" name="adminPassword" id="adminPassword" class="form-control" required minlength="6" data-minlength-error="Passwortl&auml;nge zwischen 6 und 255 Zeichen" maxlength="255"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="adminPassword">Wiederholung</label>
        <input type="password" name="adminPasswordRepeat" id="adminPasswordRepeat" class="form-control" required data-match="#adminPassword" data-match-error="Die Passw&ouml;rter stimmen nicht &uuml;berein"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Weiter</button>
</form>
