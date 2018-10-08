<h2>Gruppe</h2>
<hr />
<form>
    <p>Geben Sie nun einen Namen f&uuml;r die erste, automatisch eingerichtete Gruppe ein. Gruppen fassen mehrere Sensoren
    zusammen. Jeder Benutzer kann einer oder mehreren Gruppen zugewiesen werden. Der Administrator-Benutzer wird Mitglied
    der hier benannten Gruppe sein.</p>
    <div class="form-group has-feedback">
        <label for="divisionName">Gruppenname</label>
        <input type="text" name="divisionName" id="divisionName" class="form-control" required pattern="^[a-zA-Z0-9]+$" data-pattern-error="Nur Gro&szlig;-, Kleinbuchstaben und Zahlen erlaubt"  minlength="1" maxlength="255" data-maxlength-error="Name muss zwischen 1 und 255 Zeichen lang sein"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    
    <button type="submit" class="btn btn-primary btn-block">Weiter</button>
</form>