<td>
    <select name="type" class="form-control input-sm">
        <option value="0">E-Mail</option>
        <option value="1">Benutzer</option>
    </select>
</td>
<td>
    <form class="contactData form-horizontal">
        <div class="form-group">
            <input name="email" type="text" class="form-control input-sm" placeholder="E-Mail-Adresse" value="<%- email %>" />
            <select name="user" class="form-control input-sm hide">
                <option value="">Bitte w&auml;hlen</option>
            </select>
        </div>
    </form>
</td>
<td>
    <div class="btn-group <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>" data-toggle="buttons">
        <label class="btn btn-default btn-sm <% if(sendWeeklySummary) { %>active<% } %>">
            <input type="checkbox" autocomplete="off" name="weeklySummary" <% if(sendWeeklySummary) { %>checked<% } %> <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>>
            W&ouml;chentl. Zusamenfassung
        </label>
        <label class="btn btn-default btn-sm <% if(sendCriticalEvents) { %>active<% } %>">
            <input type="checkbox" autocomplete="off" name="criticalEvents" <% if(sendCriticalEvents) { %>checked<% } %> <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>>
            Kritische Ereignisse
        </label>
        <label class="btn btn-default btn-sm <% if(sendAllEvents) { %>active<% } %>">
            <input type="checkbox" autocomplete="off" name="allEvents" <% if(sendAllEvents) { %>checked<% } %> <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>>
            ALLE Ereignisse
        </label>
    </div>
</td>
<% if(_.templateHelpers.isAllowed('contacts', 'update')) { %>
    <td>
        <button type="button" class="remove btn btn-default btn-sm" data-toggle="tooltip" title="Entfernen">
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    </td>
<% } %>