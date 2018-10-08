<div class="col-sm-12">
    <div class="headerBar">
        <h3>Gruppe bearbeiten</h3>
    </div>
    <form class="form-horizontal" role="form">
        <div class="form-group has-feedback">
            <label for="divisionname" class="col-sm-1 control-label">Name</label>
            <div class="col-sm-5">
                <input pattern="^[a-zA-Z0-9]+$" data-pattern-error="Nur Gro&szlig;-, Kleinbuchstaben und Zahlen erlaubt" data-maxlength-error="Der Gruppenname muss zwischen 1 und 255 Zeichen lang sein" maxlength="255" minlength="1" type="text" class="form-control" name="divisionname" placeholder="Gruppenname" value="<%- name %>" required />
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div> 
    </form>
    <div class="userList"></div>
    <div class="contactList"></div>
    <hr />
    <div class="form-group">
        <div class="btn-group btn-group-justified">
            <div class="btn-group">
                <button type="button" class="cancel btn btn-default">Abbrechen</button>
            </div>
            <div class="btn-group">
                <button type="button" class="save btn btn-primary">
                    <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
                </button>
            </div>
        </div>
    </div>
</div>