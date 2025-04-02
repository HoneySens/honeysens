<input type="checkbox" class="hide" />
<div class="statusIndicator">
    <span class="statusSuccess glyphicon glyphicon-ok hide"></span>
    <span class="statusScheduled glyphicon glyphicon-time hide" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:serviceStatusScheduled') %>"></span>
    <span class="statusError glyphicon glyphicon-exclamation-sign hide" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:serviceStatusError') %>"></span>
</div>
