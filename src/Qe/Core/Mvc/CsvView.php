<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-30
 * Time: 16:43
 */

namespace Qe\Core\Mvc;


class CsvView extends View
{
    public $model;
    public $colums;

    public function __construct($model, $colums = array())
    {
        $this->colums = $colums;
        $this->setModel($model);
    }

    function getModel()
    {
        return $this->model;
    }

    function setModel($model)
    {
        $this->model = $model;
    }

    function display()
    {
        header('Content-Type:application/octet-stream');
        header('Content-Disposition:attachment;filename=export.csv');
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');

        $res = "\xEF\xBB\xBF" . $this->getCsvContent();

        echo $res;
    }

    private function getCsvContent()
    {
        $split = "\t";
        $scvData = array();
        $csvRow = array();
        $data = $this->model;
        if (count($this->colums) == 0) {
            $cols = array_keys($data[0]);
            for ($i = 0; $i < count($cols); $i++) {
                $csvRow[] = $cols[$i];
            }
        } else {
            $cols = array();
            foreach ($this->colums as $name => $col) {
                $csvRow[] = $name;
                $cols[] = $col;
            }
        }

        $scvData[] = implode($split, $csvRow);
        for ($j = 0; $j < count($data); $j++) {
            $csvRow = array();
            for ($i = 0; $i < count($cols); $i++) {
                $csvRow[] = str_replace(array("\r\n", "\n", "\r"), array("", "", ""), $data[$j][$cols[$i]]);
            }
            $scvData[] = implode($split, $csvRow);
        }
        return implode("\r\n", $scvData);
    }
}