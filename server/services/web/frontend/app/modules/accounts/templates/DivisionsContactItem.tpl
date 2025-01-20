<td>
    <select name="type" class="form-control">
        <option value="0">E-Mail</option>
        <option value="1">Benutzer</option>
    </select>
</td>
<td>
    <form class="contactData form-horizontal">
        <div class="form-group has-feedback">
             <select name="user" class="form-control">
                <option value="">Bitte w&auml;hlen</option>
            </select>
            <input type="email" name="email" class="form-control" placeholder="E-Mail-Adresse" value="<%- email %>" data-pattern-error="Bitte geben Sie eine E-Mail-Adresse ein." required />
            <div class="form-feedback">
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div>
    </form>
</td>
<td>
    <div class="panel-group" id="contactDetails<%- getIdentifier() %>">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#contactDetails<%- getIdentifier() %>" href="#contactDetailsContent<%- getIdentifier() %>">Benachrichtigungen</a>
                </h4>
            </div>
            <div id="contactDetailsContent<%- getIdentifier() %>" class="details panel-collapse collapse">
                <div class="panel-body">
                    <fieldset <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="weeklySummary" <% if(sendWeeklySummary) { %>checked<% } %>>
                                W&ouml;chentliche Ereignis&uuml;bersicht
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="criticalEvents" <% if(sendCriticalEvents) { %>checked<% } %>>
                                Kritische Ereignisse (Klasse "Honeypot" oder "Scan")
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="allEvents" <% if(sendAllEvents) { %>checked<% } %>>
                                Alle Ereignisse
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sensorTimeouts" <% if(sendSensorTimeouts) { %>checked<% } %>>
                                Sensor-Timeout (nicht mehr erreichbar)
                            </label>
                        </div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
</td>
<% if(_.templateHelpers.isAllowed('contacts', 'update')) { %>
    <td>
        <button type="button" class="remove btn btn-default " data-toggle="tooltip" title="Entfernen">
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    </td>
<% } %>
