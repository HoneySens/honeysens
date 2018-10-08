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
            <input type="email" name="email" class="form-control" placeholder="E-Mail-Adresse" value="<%- email %>" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" data-pattern-error="Bitte geben Sie eine E-Mail-Adresse ein." required />

            <div class="form-feedback">
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div>
    </form>
</td>
<td>
    <div class="btn-group <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>" data-toggle="buttons">
        <label class="btn btn-default <% if(sendWeeklySummary) { %>active<% } %>">
            <input type="checkbox" autocomplete="off" name="weeklySummary" <% if(sendWeeklySummary) { %>checked<% } %> <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>>
            W&ouml;chentl. Zusamenfassung
        </label>
        <label class="btn btn-default <% if(sendCriticalEvents) { %>active<% } %>">
            <input type="checkbox" autocomplete="off" name="criticalEvents" <% if(sendCriticalEvents) { %>checked<% } %> <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>>
            Kritische Ereignisse
        </label>
        <label class="btn btn-default <% if(sendAllEvents) { %>active<% } %>">
            <input type="checkbox" autocomplete="off" name="allEvents" <% if(sendAllEvents) { %>checked<% } %> <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>>
            ALLE Ereignisse
        </label>
    </div>
</td>
<% if(_.templateHelpers.isAllowed('contacts', 'update')) { %>
    <td>
        <button type="button" class="remove btn btn-default " data-toggle="tooltip" title="Entfernen">
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    </td>
<% } %>