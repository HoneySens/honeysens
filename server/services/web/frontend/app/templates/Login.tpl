<div id="loading">
    <form class="loginForm" action="#">
        <img class="img-responsive" src="/assets/images/logo.png" />
        <div class="loginResult alert alert-success"><%= _.t("layout:loginSuccess") %></div>
        <div class="loginResult alert alert-danger"><%= _.t("layout:loginFailed") %></div>
        <input type="text" class="form-control username" placeholder="<%= _.t('layout:loginUserPlaceholder') %>" />
        <input type="password" class="form-control" placeholder="<%= _.t('layout:loginPasswordPlaceholder') %>" />
        <button class="btn btn-primary btn-block btn-lg" type="submit"><%= _.t("layout:login") %></button>
    </form>
</div>
