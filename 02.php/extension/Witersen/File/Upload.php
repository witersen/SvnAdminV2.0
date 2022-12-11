<?php

/*
 * @Author: www.witersen.com
 * 
 * @LastEditors: witersen
 * 
 * @Description: QQ:1801168257
 */

namespace Witersen;

class Upload
{

    /**
     * 文件分片的临时保存目录
     *
     * @var string
     */
    private $nameDirTempSave = '';

    /**
     * 最终文件的正式保存目录
     *
     * @var string
     */
    private $nameDirSave = '';

    /**
     * 文件名
     *
     * @var string
     */
    private $nameFileSave = '';

    /**
     * php临时文件路径
     *
     * @var string
     */
    private $nameFileCurrent = '';

    /**
     * 第几个文件块
     *
     * @var integer
     */
    private $numBlobCurrent = 0;

    /**
     * 文件块总数
     *
     * @var integer
     */
    private $numBlobTotal = 0;

    /**
     * 工作状态
     *
     * @var boolean
     */
    private $status = true;

    /**
     * 提示信息
     *
     * @var string
     */
    private $message = '上传完成';

    /**
     * 分片上传进度
     *
     * @var boolean
     */
    private $complete = false;

    //不可上传无后缀文件 todo

    /**
     * Upload
     *
     * @param string $nameDirTempSave   文件分片的临时保存目录
     * @param string $nameDirSave       最终文件的正式保存目录
     * @param string $nameFileSave      最终文件的正式文件名
     * @param string $nameFileCurrent   当前文件分片的路径
     * @param integer $numBlobCurrent   当前是第几个文件分片
     * @param integer $numBlobTotal     一共有几个文件分片
     * @return void
     */
    public function __construct($nameDirTempSave, $nameDirSave, $nameFileSave, $nameFileCurrent, $numBlobCurrent, $numBlobTotal)
    {
        $this->nameDirTempSave = $nameDirTempSave;
        $this->nameDirSave = $nameDirSave;
        $this->nameFileCurrent = $nameFileCurrent;
        $this->numBlobCurrent = $numBlobCurrent;
        $this->numBlobTotal = $numBlobTotal;
        $this->nameFileSave = $nameFileSave;
        $this->fileMove();
        $this->fileMerge();
    }

    /**
     * 文件分片保存
     *
     * @return void
     */
    private function fileMove()
    {
        $filename = $this->nameDirTempSave . '/' . $this->nameFileSave . '_' . $this->numBlobCurrent;
        move_uploaded_file($this->nameFileCurrent, $filename);
    }

    /**
     * 文件分片合并
     *
     * @return void
     */
    private function fileMerge()
    {
        if ($this->numBlobCurrent == $this->numBlobTotal) {
            $filename = $this->nameDirSave . '/' . $this->nameFileSave;
            $fwrite = fopen($filename, 'ab');

            for ($i = 1; $i <= $this->numBlobTotal; $i++) {
                $blobname = $this->nameDirTempSave . '/' . $this->nameFileSave . '_' . $i;
                clearstatcache();
                if (!file_exists($blobname)) {
                    $this->status = false;
                    $this->message = '分片文件不存在';
                    return;
                }

                //文件块合并
                $fread = fopen($blobname, 'rb');
                fwrite($fwrite, fread($fread, filesize($blobname)));
                fclose($fread);
                unset($fread);

                //文件块删除
                @unlink($blobname);
            }

            fclose($fwrite);

            $this->complete = true;
        }
    }

    /**
     * 返回信息
     *
     * @return array
     */
    public function message()
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => [
                'complete' => $this->complete
            ]
        ];
    }
}


// new Upload($_FILES['file']['tmp_name'], $_POST['blob_num'], $_POST['total_blob_num'], $_POST['file_name'], $_POST['md5_file_name']);
