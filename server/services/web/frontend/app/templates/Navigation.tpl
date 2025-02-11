<div class="navbar-header">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-top">
        <span class="sr-only"><%= _.t("layout:toggleNav") %></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>
    <a href="#" class="navbar-brand"><img src="/assets/images/honeysens-logo-small.svg" height="24"/>&nbsp;HoneySens</a>
</div>
<div class="collapse navbar-collapse" id="navbar-top">
    <div class="navbar-right">
        <div id="counter"></div>
        <p class="navbar-text pull-right"><span class="glyphicon glyphicon-user"></span>&nbsp;<strong><%- name %></strong> (<a class="logout" href="#logout"><%= _.t("layout:logout") %></a>)</p>
    </div>
    <div class="navbar-menu"></div>
</div>
<div class="popover">
    <div class="popover-content">
        <%= _.t("layout:updateInSeconds", {count: '<span class="counter"></span>'}) %>
    </div>
</div>