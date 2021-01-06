<form class="form-group">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="restrictManagers" <% if(restrictManagerRole) { %>checked<% } %>>
            Managern das Entfernen von Sensoren und Ereignissen verbieten
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