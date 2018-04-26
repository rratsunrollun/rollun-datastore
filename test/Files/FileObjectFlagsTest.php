<?php

namespace rollun\test\files;

use rollun\files\FileObject;
use rollun\files\FileManager;

class FileObjectFlagsTest extends \PHPUnit_Framework_TestCase
{

    public function getFileObject($stringsArray)
    {
        $fileManager = new FileManager;
        $fileManager->createDir('data/FilesTests/FileObjectTest');
        $fullFilename = $fileManager->joinPath('data/FilesTests/FileObjectTest', 'fileObjectFlagsTest.txt');
        $stream = $fileManager->createAndOpenFile($fullFilename, true);
        foreach ($stringsArray as $string) {
            fwrite($stream, $string);
        }
        $fileManager->closeStream($stream);
        $fileObject = new FileObject($fullFilename);
        return $fileObject;
    }

    public function stringsRowProvider()
    {

        //$flags, \SplFileObject::DROP_NEW_LINE  \SplFileObject::SKIP_EMPTY \SplFileObject::READ_AHEAD
        //$strings
        return array(
            [0, [""]],
            [\SplFileObject::DROP_NEW_LINE, [""]],
            [0, ["1"]],
            [\SplFileObject::DROP_NEW_LINE, ["1"]],
            [0, ["", '2'], ['2']],
            [\SplFileObject::DROP_NEW_LINE, ["", '2'], ['2']],
            [0, ["\n", '']],
            [\SplFileObject::DROP_NEW_LINE, ["\n"], ['', '']],
            [0, ["1\n"], ["1\n", '']],
            [\SplFileObject::DROP_NEW_LINE, ["1\n"], ["1", '']],
            [0, ["\n", '2']],
            [\SplFileObject::DROP_NEW_LINE, ["\n", '2'], ['', '2']],
            [0, ["1", '2'], ['12']],
            [\SplFileObject::DROP_NEW_LINE, ["1", '2'], ['12']],
            [\SplFileObject::SKIP_EMPTY, [""]],
            [\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY, [""]],
            [\SplFileObject::SKIP_EMPTY, ["1"]],
            [\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY, ["1"]],
            [\SplFileObject::SKIP_EMPTY, ["", '2'], ['2']],
            [\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY, ["", '2'], ['2']],
            [\SplFileObject::SKIP_EMPTY, ["\n", '']],
            [\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY, ["\n"], [false]],
            [\SplFileObject::SKIP_EMPTY, ["1\n"], ["1\n", '']],
            [\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY, ["1\n"], ["1", '']],
            [\SplFileObject::SKIP_EMPTY, ["\n", '2']],
            [\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY, ["\n", '2'], ['2']],
            [\SplFileObject::SKIP_EMPTY, ["1", '2'], ['12']],
            [\SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY, ["1", '2'], ['12']],
        );
    }

    /**
     *
     * @dataProvider stringsRowProvider
     */
    public function testFlags($flags, $strings, $expected = null)
    {
        $expected = $expected ?? $strings;
        $fileObject = $this->getFileObject($strings);
        $fileObject->setFlags($flags);

        $savedRows = [];

        foreach ($fileObject as $key => $row) {
            $savedRows[$key] = $row; //[1];
        }

        $this->assertEquals($expected, $savedRows);
    }

}