<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-12
 * Time: 12:01
 */

namespace Qe;

use PHPUnit\Framework\TestCase;
use Qe\Core\CheckParam as CP;
use Qe\Core\Logger;
use Qe\Core\Mvc\Dispatch;
use Qe\Core\Utils;


class CheckParamTest extends TestCase
{
    public function testCK()
    {
        $params = [
            "title" => "dddddd",
            "link" => "http://wer",
            "create_user" => "12"
        ];
        try {
            $data = CP::checkParams([
                "title" => CP::rule(true, null, "", "商品名称不能为空"),
                "link" => CP::rule(true, '/^http/', "", "商品链接不能为空"),
                "desc" => CP::rule(true, null, "默认值", "商品描述不能为空"),
                "create_user" => CP::rule(true, '/\d+/', "", "创建人不能为空"),
            ], $params);
            var_dump($data);
        } catch (\Exception $e) {
            Logger::info($e->getMessage());
            var_dump([$e->getFile(), $e->getLine(), $e->getCode(), $e->getMessage()]);
        }

    }
}