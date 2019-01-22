<div id="loading">
    <form class="loginForm" action="#">
        <!--<?php if(isset($flash['warn'])): ?>
        <div class="messages alert alert-danger"><?php echo $flash['warn']; ?></div>
        <?php endif; ?>
        <?php if(isset($flash['info'])): ?>
        <div class="messages alert alert-warning"><?php echo $flash['info']; ?></div>
        <?php endif; ?>-->
        <img class="img-responsive" src="images/logo.png" />
        <div class="loginResult alert alert-success">Login erfolgreich!</div>
        <div class="loginResult alert alert-danger">Ung&uuml;ltige Zugangsdaten!</div>
        <input type="text" class="form-control username" placeholder="Benutzername" />
        <input type="password" class="form-control" placeholder="Passwort" />
        <button class="btn btn-primary btn-block btn-lg" type="submit">Anmelden</button>
    </form>
</div>
