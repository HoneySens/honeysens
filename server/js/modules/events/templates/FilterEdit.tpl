<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;Filter <% if(isNew()) { %>hinzuf&uuml;gen<% } else { %>bearbeiten<% } %></h1>
        <form role="form">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group has-feedback">
                        <label for="filtername" class="control-label">Name</label>
                        <input pattern="^[a-zA-Z0-9._\- ]+$" data-pattern-error="Nur Gro&szlig;-, Kleinbuchstaben, Zahlen und Sonderzeichen (_, -, .) sind erlaubt" data-maxlength-error="Der Filtername muss zwischen 1 und 255 Zeichen lang sein" maxlength="255" minlength="1" type="text" class="form-control" name="filtername" placeholder="Filtername" value="<%- name %>" required />
                        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                        <div class="help-block with-errors"></div>
                    </div>
                    <div class="form-group">
                        <label for="filtertyp" class="control-label">Typ</label>
                        <select class="form-control" name="type" disabled>
                            <option value="<%- _.templateHelpers.getModels().EventFilter.type.WHITELIST %>" <%- type === _.templateHelpers.getModels().EventFilter.type.WHITELIST ? 'selected' : void 0 %>>Whitelist</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="division" class="control-label">Gruppe</label>
                        <select class="form-control" name="division">
                            <% _(divisions).each(function(d) { %>
                            <option value="<%- d.id %>" <%- d.id === division ? 'selected' : void 0 %>><%- d.name %></option>
                            <% }); %>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="description" class="control-label">Beschreibung</label>
                        <textarea class="form-control" name="description" style="height: 160px; resize:none;" maxlength="65535"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="col-sm-12">
        <div class="conditionList"></div>
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
</div>
