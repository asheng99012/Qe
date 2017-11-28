<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-27
 * Time: 13:54
 */

use PHPUnit\Framework\TestCase as TestCaseBase;

abstract class TestCase extends TestCaseBase
{
    public function setUp(){
        echo "============setUp=============";
        \Qe\Core\Proxy::handle($this);
    }
}