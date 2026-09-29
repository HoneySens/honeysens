<td>
    <select name="type" class="form-control">
        <option value="0"><%= t("emailAddress") %></option>
        <option value="1"><%= t("user") %></option>
    </select>
</td>
<td>
    <form class="contactData form-horizontal">
        <div class="form-group has-feedback">
             <select name="user" class="form-control">
                <option value="">** <%= t("select") %> **</option>
            </select>
            <input type="email" name="email" class="form-control" placeholder="<%= t('emailAddress') %>" value="<%- email %>" data-type-error="<%= t('emailValidationError') %>" required />
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
                    <a class="collapsed" data-toggle="collapse" data-parent="#contactDetails<%- getIdentifier() %>" href="#contactDetailsContent<%- getIdentifier() %>"><%= t("accounts:notifications") %></a>
                </h4>
            </div>
            <div id="contactDetailsContent<%- getIdentifier() %>" class="details panel-collapse collapse">
                <div class="panel-body">
                    <fieldset <% if(!_.templateHelpers.isAllowed('contacts', 'update')) { %>disabled<% } %>>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="weeklySummary" <% if(sendWeeklySummary) { %>checked<% } %>>
                                <%= t("accounts:weeklyEventOverview") %>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="criticalEvents" <% if(sendCriticalEvents) { %>checked<% } %>>
                                <%= t("accounts:criticalEvents") %>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="allEvents" <% if(sendAllEvents) { %>checked<% } %>>
                                <%= t("accounts:allEvents") %>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sensorTimeouts" <% if(sendSensorTimeouts) { %>checked<% } %>>
                                <%= t("accounts:sensorTimeout") %>
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
        <button type="button" class="remove btn btn-default " data-toggle="tooltip" title="<%= t('remove') %>">
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    </td>
<% } %>
