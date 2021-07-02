<h2>Passwort&auml;nderung</h2>
<hr />
<form>
    <p>Um mit der Anmeldung fortzufahren, vergeben Sie bitte zun&auml;chst f&uuml;r Ihren Account ein neues Passwort.</p>
    <div class="form-group has-feedback">
        <label for="userPassword">Passwort</label>
        <input type="password" name="userPassword" id="userPassword" class="form-control" required minlength="6" data-minlength-error="Das Passwort muss zwischen 6 und 255 Zeichen lang sein" maxlength="255"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="userPasswordRepeat">Wiederholung</label>
        <input type="password" name="userPasswordRepeat" id="userPasswordRepeat" class="form-control" required data-match="#userPassword" data-match-error="Die Passw&ouml;rter stimmen nicht &uuml;berein"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <p>Sie werden im Anschluss dazu aufgefordert, sich mit den neuen Zugangsdaten erneut anzumelden.</p>
    <button type="button" class="btn btn-default btn-block">Abbrechen</button>
    <button type="submit" class="btn btn-primary btn-block">Weiter</button>
</form>