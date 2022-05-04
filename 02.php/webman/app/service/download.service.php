<?php

/** 
 * 文件传输，支持断点续传。 
 * 2g以上超大文件也有效 
 * @author MoXie 
 */
class ServiceTransfer
{
    /** 
     * 缓冲单元 
     */
    const BUFF_SIZE = 5120; // 1024 * 5 
    /** 
     * 文件地址 
     * @var <String> 
     */
    private $filePath;
    /** 
     * 文件大小 
     * @var <String> Php超大数字 字符串形式描述 
     */
    private $fileSize;
    /** 
     * 文件类型 
     * @var <String> 
     */
    private $mimeType;
    /** 
     * 请求区域（范围） 
     * @var <String> 
     */
    private $range;
    /** 
     * 
     * @param <String> $filePath 文件路径 
     * @param <String> $mimeType 文件类型 
     * @param <String> $range 请求区域（范围） 
     */
    function __construct($filePath, $mimeType = null, $range = null)
    {
        $this->filePath = $filePath;
        $this->fileSize = sprintf('%u', filesize($filePath));
        $this->mimeType = ($mimeType != null) ? $mimeType : "application/octet-stream"; // bin 
        $this->range = trim($range);
    }
    /** 
     * 获取文件区域 
     * @return <Map> {'start':long,'end':long} or null 
     */
    private function getRange()
    {
        /** 
         * Range: bytes=-128 
         * Range: bytes=-128 
         * Range: bytes=28-175,382-399,510-541,644-744,977-980 
         * Range: bytes=28-175\n380 
         * type 1 
         * RANGE: bytes=1000-9999 
         * RANGE: bytes=2000-9999 
         * type 2 
         * RANGE: bytes=1000-1999 
         * RANGE: bytes=2000-2999 
         * RANGE: bytes=3000-3999 
         */
        if (!empty($this->range)) {
            $range = preg_replace('/[\s|,].*/', '', $this->range);
            $range = explode('-', substr($range, 6));
            if (count($range) < 2) {
                $range[1] = $this->fileSize; // Range: bytes=-100 
            }
            $range = array_combine(array('start', 'end'), $range);
            if (empty($range['start'])) {
                $range['start'] = 0;
            }
            if (!isset($range['end']) || empty($range['end'])) {
                $range['end'] = $this->fileSize;
            }
            return $range;
        }
        return null;
    }
    /** 
     * 向客户端发送文件 
     */
    public function send()
    {
        $fileHande = fopen($this->filePath, 'rb');
        if ($fileHande) {
            // setting 
            ob_end_clean(); // clean cache 
            ob_start();
            ini_set('output_buffering', 'Off');
            ini_set('zlib.output_compression', 'Off');
            // init 
            $lastModified = gmdate('D, d M Y H:i:s', filemtime($this->filePath)) . ' GMT';
            $etag = sprintf('w/"%s:%s"', md5($lastModified), $this->fileSize);
            $ranges = $this->getRange();
            // headers 
            header(sprintf('Last-Modified: %s', $lastModified));
            header(sprintf('ETag: %s', $etag));
            header(sprintf('Content-Type: %s', $this->mimeType));
            $disposition = 'attachment';
            if (strpos($this->mimeType, 'image/') !== FALSE) {
                $disposition = 'inline';
            }
            header(sprintf('Content-Disposition: %s; filename="%s"', $disposition, basename($this->filePath)));
            if ($ranges != null) {
                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges: bytes');
                header(sprintf('Content-Length: %u', $ranges['end'] - $ranges['start']));
                header(sprintf('Content-Range: bytes %s-%s/%s', $ranges['start'], $ranges['end'], $this->fileSize));
                // 
                fseek($fileHande, sprintf('%u', $ranges['start']));
            } else {
                header("HTTP/1.1 200 OK");
                header(sprintf('Content-Length: %s', $this->fileSize));
            }
            // read file 
            $lastSize = 0;
            while (!feof($fileHande) && !connection_aborted()) {
                $lastSize = sprintf("%u", bcsub($this->fileSize, sprintf("%u", ftell($fileHande))));
                if (bccomp($lastSize, self::BUFF_SIZE) > 0) {
                    $lastSize = self::BUFF_SIZE;
                }
                echo fread($fileHande, $lastSize);
                ob_flush();
                flush();
            }
            ob_end_flush();
        }
        if ($fileHande != null) {
            fclose($fileHande);
        }
    }
}
