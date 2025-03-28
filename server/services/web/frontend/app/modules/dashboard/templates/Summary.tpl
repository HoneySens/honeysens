<div class="panel-heading"><%= _.t("dashboard:summaryHeader") %></div>
<div class="panel-body">
    <div class="row">
        <div class="col-sm-5">
            <table class="table table-condensed">
                <thead>
                    <tr>
                        <th><span class="glyphicon glyphicon-list"></span><%= _.t("events") %></th>
                        <th class="text-right"><%- events_total %></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><%= _.t("dashboard:summaryLive") %></td>
                        <td class="text-right"><%- events_live %></td>
                    </tr>
                    <tr>
                        <td class="indent"><%= _.t("events:eventStatusUnedited") %> / <%= _.t("events:eventStatusBusy") %></td>
                        <td class="text-right"><% if(events_unedited > 0) { %><strong><% } %><%- events_unedited %><% if(events_unedited > 0) { %></strong><% } %> / <%- events_busy %></td>
                    </tr>
                    <tr>
                        <td class="indent"><%= _.t("events:eventStatusResolved") %> / <%= _.t("events:eventStatusIgnored") %></td>
                        <td class="text-right"><%- events_resolved %> / <%- events_ignored %></td>
                    </tr>
                    <tr>
                        <td><%= _.t("dashboard:summaryArchived") %></td>
                        <td class="text-right"><%- events_archived %></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col-sm-7">
            <table class="table table-condensed">
                <thead>
                    <tr>
                        <th><%= _.t("dashboard:summaryInfrastructure") %></th>
                        <th class="text-right">#</th>
                        <th></th>
                        <th class="text-right">#</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="glyphicon glyphicon-hdd"></span><%= _.t("sensors") %></td>
                        <td class="text-right"><%- sensors_total %></td>
                        <td><span class="glyphicon glyphicon-asterisk"></span><%= _.t("dashboard:summaryServices") %></td>
                        <td class="text-right"><%- services_total %></td>
                    </tr>
                    <tr>
                        <td class="indent"><%= _.t("dashboard:summaryOnline") %> / <%= _.t("dashboard:summaryOffline") %></td>
                        <td>
                            <div class="text-right">
                                <span class="<% if(sensors_total > 0) { %><% if(sensors_online > 0) { %>text-success<% } else { %>text-danger<% } %><% } %>"><%- sensors_online %></span>
                                /
                                <span class="<% if(sensors_total > 0) { %><% if(sensors_offline > 0) { %>text-danger<% } else { %>text-success<% } %><% } %>"><%- sensors_offline %></span>
                            </div>
                        </td>
                        <td class="indent"><%= _.t("dashboard:summaryOnline") %> / <%= _.t("dashboard:summaryOffline") %></td>
                        <td>
                            <div class="text-right">
                                <span class="<% if (services_total > 0) { %><% if(services_online > 0) { %>text-success<% } else { %>text-danger<% } %><% } %>"><%- services_online %></span>
                                /
                                <span class="<% if (services_total > 0) { %><% if(services_offline > 0) { %>text-danger<% } else { %>text-success<% } %><% } %>"><%- services_offline %></span>
                            </div>
                        </td>
                    </tr>
                    <% if(_.templateHelpers.isAllowed('eventfilters', 'update')) { %>
                    <tr>
                        <td><span class="glyphicon glyphicon-filter"></span><%= _.t("dashboard:summaryFilters") %></td>
                        <td class="text-right"><%- filters_total %></td>
                        <% if(_.templateHelpers.isAllowed('users', 'update')) { %>
                        <td><span class="glyphicon glyphicon-user"></span><%= _.t("users") %></td>
                        <td class="text-right"><%- users %></td>
                        <% } else { %>
                        <td></td>
                        <td></td>
                        <% } %>
                    </tr>
                    <tr>
                        <td class="indent"><%= _.t("dashboard:summaryActive") %> / <%= _.t("dashboard:summaryInactive") %></td>
                        <td class="text-right"><%- filters_active %> / <%- filters_inactive %></td>
                        <% if(_.templateHelpers.isAllowed('users', 'update')) { %>
                        <td><span class="glyphicon glyphicon-align-justify"></span><%= _.t("divisions") %></td>
                        <td class="text-right"><%- divisions %></td>
                        <% } else { %>
                        <td></td>
                        <td></td>
                        <% } %>
                    </tr>
                    <% } %>
                </tbody>
            </table>
        </div>
    </div>
</div>
