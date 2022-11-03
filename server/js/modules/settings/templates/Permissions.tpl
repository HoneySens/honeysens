<form class="form-group">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="preventEventDeletionByManagers" <% if(preventEventDeletionByManagers) { %>checked<% } %>>
            Managern das Entfernen von Ereignissen verbieten (Verschieben ins Archiv wird erzwungen)
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="preventSensorDeletionByManagers" <% if(preventSensorDeletionByManagers) { %>checked<% } %>>
            Managern das Entfernen von Sensoren verbieten
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="requireEventComment" <% if(requireEventComment) { %>checked<% } %>>
            Ereignis-Kommentar als Pflichtfeld
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" name="requireFilterDescription" <% if(requireFilterDescription) { %>checked<% } %>>
            Filter-Beschreibung als Pflichtfeld
        </label>
    </div>
</form>