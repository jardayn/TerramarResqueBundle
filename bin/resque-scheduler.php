<?php

(@include_once __DIR__ . '/../vendor/autoload.php') || @include_once __DIR__ . '/../../../../../../autoload.php';

$REDIS_BACKEND = getenv('REDIS_BACKEND');
$REDIS_BACKEND_DB = getenv('REDIS_BACKEND_DB');
if (!empty($REDIS_BACKEND)) {
    Resque::setBackend($REDIS_BACKEND, $REDIS_BACKEND_DB);
}

$logLevel = 0;
$LOGGING = getenv('LOGGING');
$VERBOSE = getenv('VERBOSE');
$VVERBOSE = getenv('VVERBOSE');
if (!empty($LOGGING) || !empty($VERBOSE)) {
    $logLevel = Resque_Worker::LOG_NORMAL;
}
if (!empty($VVERBOSE)) {
    $logLevel = Resque_Worker::LOG_VERBOSE;
}

$APP_INCLUDE = getenv('APP_INCLUDE');
if ($APP_INCLUDE) {
    if (!file_exists($APP_INCLUDE)) {
        die('APP_INCLUDE ('.$APP_INCLUDE.") does not exist.\n");
    }

    require_once $APP_INCLUDE;
}

$interval = 5;
$INTERVAL = getenv('INTERVAL');
if (!empty($INTERVAL)) {
    $interval = $INTERVAL;
}

$worker = new ResqueScheduler_Worker();
$worker->logLevel = $logLevel;

$PIDFILE = getenv('PIDFILE');
if ($PIDFILE) {
    file_put_contents($PIDFILE, getmypid()) or
    die('Could not write PID information to ' . $PIDFILE);
}

fwrite(STDOUT, "*** Starting scheduler worker\n");
$worker->work($interval);