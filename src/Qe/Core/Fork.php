<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-17
 * Time: 17:18
 */

namespace Qe\Core;


class Fork
{
    public static function run($fun)
    {
        if (function_exists("pcntl_fork")) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('could not fork');
            } else if ($pid) {

            } else {
                $fun();
                exit(0);
            }
        } else
            $fun();

    }
}