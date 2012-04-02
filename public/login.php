<?
session_start();

include('classes/Db.php');
$pdo = Db::getInstance()->pdo;

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . isset($_SESSION['back_url']) ? $_SESSION['back_url'] : '/');
    unset($_SESSION['back_url']);
    die;
}

if (!empty($_POST['email']) && !empty($_POST['password'])) {
    $stmt = $pdo->prepare("select * from users where email=? AND password=?");
    $stmt->execute(array($_POST['email'], md5($_POST['password'])));
    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $pdo->prepare("update users set last_login_date=NOW(), token=? where id=?");
        $stmt->execute(array(session_id(), $user['id']));

        $stmt = $pdo->prepare("select MAX(id) from sprints");
        $stmt->execute();
        $lastSprintId = $stmt->fetchColumn(0);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_sprint_id'] = $lastSprintId;

        header('Location: ' . $_SESSION['back_url']);
        unset($_SESSION['back_url']);
        die;
    }
}

include('header.php');
?>

<div id="form-login-container">
    <div id="form-login" class="ui-dialog ui-widget ui-widget-content ui-corner-all" style="width: 400px;">
        <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
            <span class="ui-dialog-title" id="ui-dialog-title-dialog-confirm">Login to Estimator</span>
        </div>
        <div class="ui-dialog-content ui-widget-content">
            <form action="/public/login.php" method="POST">
                <div class="field_container">
                    <label for="form-login-email">E-mail:</label>
                    <input id="form-login-email" name="email" type="text" size="20"/>
                </div>
                <div class="field_container">
                    <label for="form-login-password">Password: </label>
                    <input id="form-login-password" name="password" type="password" size="20"/>
                </div>
                <div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
                    <div class="ui-dialog-buttonset">
                        <input type="submit" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" value="Login" />
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<? include('footer.php');
