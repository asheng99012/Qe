<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-11
 * Time: 21:56
 */

namespace Qe;

use PHPUnit\Framework\TestCase;
use Qe\Core\Logger;
use Qe\Core\TimeWatcher;


class LoggerTest extends TestCase
{
    public function testLogger()
    {
        TimeWatcher::label("ddd");
//        call_user_func("testl");

//        testl();
        Logger::info("this is test", ["a", "bb"]);


        TimeWatcher::label("ddd");
        $this->realExec(function () {
            Logger::info("this is test", ["a", "bb", "============"]);

        });
        TimeWatcher::label("ddd");

//        Logger::getLogger("ddd")->info("this is test", ["a", "bb"]);

    }

    function realExec(callable $fun)
    {
        echo "===========";
//        call_user_func($fun);
        $fun();
    }
}