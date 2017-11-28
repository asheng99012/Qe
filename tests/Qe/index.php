<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-27
 * Time: 15:34
 */

include "config.php";
try {
    \Qe\Core\Bootstrap::run();
} catch (Exception $e) {
    \Qe\Core\Logger::error($e->getMessage(),$e);
}