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
     * 完整文件的md5
     *
     * @var string
     */
    private $nameFileMd5 = '';

    /**
     * php临时文件路径
     *
     * @var string
     */
    private $nameFileCurrent = '';

    /**
     * 第几个文件分片
     *
     * @var integer
     */
    private $numBlobCurrent = 0;

    /**
     * 文件分片总数
     *
     * @var integer
     */
    private $numBlobTotal = 0;

    /**
     * 已经上传完成的文件分片数量
     *
     * @var integer
     */
    private $completeCount = 0;

    /**
     * 是否合并完成
     *
     * @var boolean
     */
    private $complete = false;

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
     * 文件分片合并后是否立即删除
     *
     * @var boolean
     */
    private $deleteOnMerge = true;

    /**
     * Upload
     *
     * @param string $nameDirTempSave   文件分片的临时保存目录
     * @param string $nameDirSave       最终文件的正式保存目录
     * @param string $nameFileSave      最终文件的正式文件名
     * @param string $nameFileMd5       要上传文件的md5值
     * @param string $nameFileCurrent   当前文件分片的路径
     * @param integer $numBlobCurrent   当前是第几个文件分片
     * @param integer $numBlobTotal     一共有几个文件分片
     * @param integer $deleteOnMerge    文件合并完成后是否删除所有分片
     * @return void
     */
    public function __construct($nameDirTempSave, $nameDirSave, $nameFileSave, $nameFileMd5, $nameFileCurrent, $numBlobCurrent, $numBlobTotal, $deleteOnMerge = true)
    {
        $this->nameDirTempSave = $nameDirTempSave;
        $this->nameDirSave = $nameDirSave;
        $this->nameFileSave = $nameFileSave;
        $this->nameFileMd5 = $nameFileMd5;
        $this->nameFileCurrent = $nameFileCurrent;
        $this->numBlobCurrent = $numBlobCurrent;
        $this->numBlobTotal = $numBlobTotal;
        $this->deleteOnMerge = $deleteOnMerge;
    }

    /**
     * 文件分片保存
     *
     * @return void
     */
    public function fileUpload()
    {
        if (!file_exists($this->nameDirTempSave . '/' . $this->nameFileMd5 . '_' . $this->numBlobTotal . '_' . $this->numBlobCurrent)) {
            move_uploaded_file($this->nameFileCurrent, $this->nameDirTempSave . '/' . $this->nameFileMd5 . '_' . $this->numBlobTotal . '_' . $this->numBlobCurrent);
        }

        $count = 0;
        clearstatcache();
        $files = scandir($this->nameDirTempSave);
        foreach ($files as $file) {
            if ($file == '.' && $file == '..') {
                continue;
            }
            if (is_dir($this->nameDirTempSave . '/' . $file)) {
                continue;
            }
            if (!preg_match(sprintf('/^%s_%s_[0-9]+$/', $this->nameFileMd5, $this->numBlobTotal), $file, $match)) {
                continue;
            }
            $count++;
        }

        $this->completeCount = $count;

        if ($count == $this->numBlobTotal) {
            $this->fileMerge();
        }
    }

    /**
     * 文件分片合并
     *
     * @return void
     */
    private function fileMerge()
    {
        $fwrite = fopen($this->nameDirSave . '/' . $this->nameFileSave, 'ab');

        for ($i = 1; $i <= $this->numBlobTotal; $i++) {
            $slicename = $this->nameDirTempSave . '/' . $this->nameFileMd5 . '_' . $this->numBlobTotal . '_' . $i;
            clearstatcache();
            if (!file_exists($slicename)) {
                $this->status = false;
                $this->message = sprintf('第[%s]个分片文件[%s]不存在', $i, $slicename);
                return;
            }
        }

        for ($i = 1; $i <= $this->numBlobTotal; $i++) {
            $slicename = $this->nameDirTempSave . '/' . $this->nameFileMd5 . '_' . $this->numBlobTotal . '_' . $i;
            clearstatcache();
            if (!file_exists($slicename)) {
                $this->status = false;
                $this->message = sprintf('第[%s]个分片文件[%s]不存在', $i, $slicename);
                return;
            }

            //文件分片合并
            $fsize = filesize($slicename);
            if ($fsize > 0) {
                $fread = fopen($slicename, 'rb');
                fwrite($fwrite, fread($fread, $fsize));
                fclose($fread);
                unset($fread);
            }

            //文件分片删除
            if ($this->deleteOnMerge) {
                @unlink($slicename);
            }
        }

        fclose($fwrite);

        $this->complete = true;
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
                'completeCount' => $this->completeCount,
                'complete' => $this->complete
            ]
        ];
    }
}
