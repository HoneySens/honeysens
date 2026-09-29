<td>
    <select name="attribute" class="form-control input-sm">
        <option value="<%- EventFilterConditionField.CLASSIFICATION %>" <%- field === EventFilterConditionField.CLASSIFICATION ? 'selected' : void 0 %>><%= t("events:filterConditionClassification") %></option>
        <option value="<%- EventFilterConditionField.SOURCE %>" <%- field === EventFilterConditionField.SOURCE ? 'selected' : void 0 %>><%= t("events:filterConditionSource") %></option>
        <option value="<%- EventFilterConditionField.TARGET %>" <%- field === EventFilterConditionField.TARGET ? 'selected' : void 0 %>><%= t("events:filterConditionTarget") %></option>
        <option value="<%- EventFilterConditionField.PROTOCOL %>" <%- field === EventFilterConditionField.PROTOCOL ? 'selected' : void 0 %>><%= t("events:filterConditionProtocol") %></option>
    </select>
</td>
<td>
    <select name="type" class="form-control input-sm" disabled></select>
</td>
<td> 
    <form class="conditionData form-horizontal">
        <div class="form-group has-feedback">
            <select name="classification" class="form-control input-sm">
                <option value="<%- EventClassification.UNKNOWN %>"><%= t("unknown") %></option>
                <option value="<%- EventClassification.ICMP %>"><%= t("eventClassificationICMP") %></option>
                <option value="<%- EventClassification.CONN_ATTEMPT %>"><%= t("eventClassificationConnectionAttempt") %></option>
                <option value="<%- EventClassification.LOW_HP %>"><%= t("eventClassificationHoneypot") %></option>
                <option value="<%- EventClassification.PORTSCAN %>"><%= t("eventClassificationScan") %></option>
            </select>
            <select name="protocol" class="form-control input-sm">
                <option value="<%- EventPacketProtocol.TCP %>"><%= t("tcp") %></option>
                <option value="<%- EventPacketProtocol.UDP %>"><%= t("udp") %></option>
            </select>
            <input type="number" name="port_value" class="form-control input-sm" placeholder="<%= t('port') %>" min="1" max="65535" data-max-error="<%= t('intValidationError', {min: 1, max: 65535}) %>" required />
            <input type="text" name="ip_value" class="form-control input-sm hide" placeholder="<%= t('ipAddr') %>" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="<%= t('ipAddrValidationError') %>" />
            <input type="text" name="ip_range_value" class="form-control input-sm hide" placeholder="<%= t('ipRange') %>" pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)-(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$" data-pattern-error="<%= t('ipRangeValidationError') %>" />
            <div class="form-feedback">
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div>
    </form>
</td>
<td>
    <button type="button" class="remove btn btn-default btn-sm" data-toggle="tooltip" title="<%= t('remove') %>">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
</td>