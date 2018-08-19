<?php

define( 'ABSPATH', __FILE__ );

// Enable error reporting
ini_set( 'display_errors', true );
error_reporting( E_ALL );

// Load composer dependencies
if ( ! file_exists( 'vendor/autoload.php' ) ) die( 'Composer dependencies missing');
require_once 'vendor/autoload.php';

// Load dotenv
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load( __DIR__ . '/.env' );

// Authentication
require 'auth.php';

// Handle submission
require 'action-handling.php';

?><!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang=""> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>Welcome to the SMS Dungeon</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body {
            padding-top: 50px;
            padding-bottom: 20px;
        }
    </style>
    <link rel="stylesheet" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="css/main.css">
    <script src="js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <a class="navbar-brand">SMS Dungeon</a>
            </div>
        </div>
    </nav>
    <div class="jumbotron">
        <div class="container">

            <?php if ( $s->isPost() ) : ?>
                <?php if ( $s->result ) : ?>
                    <div class="alert alert-success" role="alert">SMS was sent!</div>
                <?php else : ?>
                    <div class="alert alert-danger" role="alert">
                        SMS was not sent!
                        <?php if ( count( $s->errors ) > 0 ) : ?>
                        <br>Please check the errors below:<br>
                        <ul>
                            <?php $s->handleValidationErrors( '<li>%s</li>' ); ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="/">
                <div class="form-group">
                    <label for="receivers">Receiver(s)</label>
                    <input type="text" class="form-control" id="receivers" name="receivers" placeholder="4781549300" value="<?php echo $s->getFormValue( 'receivers', true ); ?>">
                    Country code followed by phone number. For multiple receivers use comma-separation.
                </div>
                <div class="form-group">
                    <label for="from">Sender</label>
                    <input type="text" class="form-control" id="from" name="from" placeholder="Troll" value="<?php echo $s->getFormValue( 'from', true ); ?>">
                    Maximum 11 chars, no spaces etc.
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea class="form-control" id="message" name="message" placeholder="Message" rows="3"><?php echo $s->getFormValue( 'message' ); ?></textarea>
                </div>
                <hr>
                <div class="form-group">
                    <label for="api_key">API key</label>
                    <input type="text" class="form-control" id="api_key" name="api_key" placeholder="" value="<?php echo htmlspecialchars( $s->getCookieValue( 'api_key', true ) ); ?>">
                    For custom key usage. Gets stored in a cookie.
                </div>
                <div class="form-group">
                    <label for="api_secret">API secret</label>
                    <input type="text" class="form-control" id="api_secret" name="api_secret" placeholder="" value="<?php echo htmlspecialchars( $s->getCookieValue( 'api_secret', true ) ); ?>">
                    For custom secret usage. Gets stored in a cookie.
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="api_clear_cookie" id="api_clear_cookie">
                    <label class="form-check-label" for="api_clear_cookie">
                        Check to reset API credentials
                    </label>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary mb-2">Troll away!</button>
            </form>
        </div>
    </div>
    <div class="container">
        <footer>
            <p>&copy; SMS Dungeon <?php echo date( 'Y' ); ?></p>
        </footer>
    </div> <!-- /container -->        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.2.min.js"><\/script>')</script>
    <script src="js/vendor/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>