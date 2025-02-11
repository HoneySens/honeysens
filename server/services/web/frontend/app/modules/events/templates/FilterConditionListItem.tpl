<td>
    <select name="attribute" class="form-control input-sm">
        <option value="<%- _.templateHelpers.getModels().EventFilterCondition.field.CLASSIFICATION %>" <%- field === _.templateHelpers.getModels().EventFilterCondition.field.CLASSIFICATION ? 'selected' : void 0 %>><%= _.t("events:filterConditionClassification") %></option>
        <option value="<%- _.templateHelpers.getModels().EventFilterCondition.field.SOURCE %>" <%- field === _.templateHelpers.getModels().EventFilterCondition.field.SOURCE ? 'selected' : void 0 %>><%= _.t("events:filterConditionSource") %></option>
        <option value="<%- _.templateHelpers.getModels().EventFilterCondition.field.TARGET %>" <%- field === _.templateHelpers.getModels().EventFilterCondition.field.TARGET ? 'selected' : void 0 %>><%= _.t("events:filterConditionTarget") %></option>
        <option value="<%- _.templateHelpers.getModels().EventFilterCondition.field.PROTOCOL %>" <%- field === _.templateHelpers.getModels().EventFilterCondition.field.PROTOCOL ? 'selected' : void 0 %>><%= _.t("events:filterConditionProtocol") %></option>
    </select>
</td>
<td>
    <select name="type" class="form-control input-sm" disabled></select>
</td>
<td> 
    <form class="conditionData form-horizontal">
        <div class="form-group has-feedback">
            <select name="classification" class="form-control input-sm">
                <option value="<%- _.templateHelpers.getModels().Event.classification.UNKNOWN %>"><%= _.t("unknown") %></option>
                <option value="<%- _.templateHelpers.getModels().Event.classification.ICMP %>"><%= _.t("eventClassificationICMP") %></option>
                <option value="<%- _.templateHelpers.getModels().Event.classification.CONN_ATTEMPT %>"><%= _.t("eventClassificationConnectionAttempt") %></option>
                <option value="<%- _.templateHelpers.getModels().Event.classification.LOW_HP %>"><%= _.t("eventClassificationHoneypot") %></option>
                <option value="<%- _.templateHelpers.getModels().Event.classification.PORTSCAN %>"><%= _.t("eventClassificationScan") %></option>
            </select>
            <select name="protocol" class="form-control input-sm">
                <option value="<%- _.templateHelpers.getModels().EventPacket.protocol.TCP %>"><%= _.t("tcp") %></option>
                <option value="<%- _.templateHelpers.getModels().EventPacket.protocol.UDP %>"><%= _.t("udp") %></option>
            </select>
            <input type="number" name="port_value" class="form-control input-sm" placeholder="<%= _.t('port') %>" min="1" max="65535" data-max-error="<%= _.t('intValidationError', {min: 1, max: 65535}) %>" required />
            <input type="text" name="ip_value" class="form-control input-sm hide" placeholder="<%= _.t('ipAddr') %>" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="<%= _.t('ipAddrValidationError') %>" />
            <input type="text" name="ip_range_value" class="form-control input-sm hide" placeholder="<%= _.t('ipRange') %>" pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)-(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$" data-pattern-error="<%= _.t('ipRangeValidationError') %>" />
            <div class="form-feedback">
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div>
    </form>
</td>
<td>
    <button type="button" class="remove btn btn-default btn-sm" data-toggle="tooltip" title="<%= _.t('remove') %>">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
</td>