<?php

namespace App\Util\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelHandler
{
    const Xlsx = 'Xlsx';

    private $chars = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G',
        'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q',
        'R', 'S', 'T',
        'U', 'V', 'W',
        'X', 'Y', 'Z'
    ];

    /**
     * @param string $filename      读取的文件名
     * @param string $sheetName     excel的sheet名字
     * @param array $cells          一个键值对的数组，key是cell坐标，值是cell对应的字段名。
     * @param string $primaryKey
     * @param int $rowIndex
     * @param $fn
     * @return array
     * @throws Exception
     */
    public function read(string $filename, string $sheetName, array $cells,
                         string $primaryKey, int $rowIndex = 1, $fn = null): array
    {
        $reader = IOFactory::createReader(self::Xlsx);
        $reader->setLoadSheetsOnly($sheetName);
        $spreadsheet = $reader->load($filename);
        $workSheet = $spreadsheet->getActiveSheet();

        $data = [];

        while (1) {
            $item = [];
            foreach ($cells as $key => $field) {
                $coordinate = $key . $rowIndex;
                $item[$field] = $workSheet->getCell($coordinate)->getValue();
            }
            $rowIndex++;
            $data[] = $item;

            // 每一行数据都会有一个标识字段，通过该标识字段的值来决定是否停止读取数据。
            $value = $item[$primaryKey];
            if (is_callable($fn)) {
                $result = $fn($value);
            } else {
                $result = !empty($value);
            }

            if (!$result) {
                array_pop($data);
                break;
            }
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    private function getWorkSheet(string $filename, string $sheetName): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $reader = IOFactory::createReader(self::Xlsx);
        $reader->setLoadSheetsOnly($sheetName);
        $spreadsheet = $reader->load($filename);
        return $spreadsheet->getActiveSheet();
    }

    /**
     * @param $filename
     * @param string $sheetName
     * @param int $number           读取的行数
     * @param string $cellLimit     读取到哪个cell就停止读取。默认是一直读取直到读取到一个null值。
     * @return array
     * @throws Exception
     */
    public function readRow($filename, string $sheetName, int $number, string $cellLimit = ''): array
    {
        $workSheet = $this->getWorkSheet($filename, $sheetName);

        $headers = [];
        foreach ($this->chars as $char) {
            $cell = $char . $number;
            $value = $workSheet->getCell($cell)->getValue();
            $headers[$char] = $value;

            // 停止读取的时机
            if ($cellLimit) {
                if ($char == $cellLimit) {
                    break;
                }
            } else {
                if (empty($value)) {
                    break;
                }
            }
        }
        return $headers;
    }


    // $data: 一个数组，数组元素是键值对，键是包含于$column中，值则是键对应的值。
    // $filters: key是字段，值用于过滤数组。
    public function write($columns, $data, $filename = '', $filters = null)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
//        $worksheet->setTitle('成绩表');

        $keys = array_keys($columns);

        $columnIndex = 1;
        $rowIndex = 1;
        // 写入字段
        foreach ($columns as $column) {
            $worksheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $column);
            $columnIndex++;
        }
        $rowIndex++;

        // 写入字段对用的数据
        foreach ($data as $row) {
            $columnIndex = 1;
            foreach ($keys as $key) {
                if (is_array($filters) && isset($filters[$key]) && is_callable($filters[$key])) {
                    $val = $filters[$key]($row[$key]);
                } else {
                    $val = $row[$key];
                }
                $worksheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $val);
                $columnIndex++;
            }
            $rowIndex++;
        }

        if (empty($filename)) {
            $filename = 'data';
        }
        $filename .= '_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8');
        header('Content-Disposition: attachment;filename="'. urlencode($filename) .'"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }

}
