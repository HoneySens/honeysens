<div class="col-sm-12">
    <div class="headerBar">
        <div class="pull-right">
            <button type="button" class="save btn btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
            </button>
            <button type="button" class="cancel btn btn-default btn-sm">Abbrechen</button>
        </div>
        <h3>Gruppe bearbeiten</h3>
    </div>
    <form class="form-horizontal" role="form">
        <div class="form-group">
            <label for="divisionname" class="col-sm-1 control-label">Name</label>
            <div class="col-sm-5">
                <input type="text" class="form-control" name="divisionname" placeholder="Gruppenname" value="<%- name %>" />
            </div>
        </div>
    </form>
    <div class="userList"></div>
    <div class="contactList"></div>
</div>
