<?php

namespace Payever\Payever\Model;

use Magento\Framework\Filesystem\DirectoryList;

class Locker
{
    /**
     * @var DirectoryList
     */
    private $dir;

    const TIME_LOCK  = 60;
    const TIME_SLEEP = 1;
    const MAX_LIFETIME = 120;

    /**
     * Locker constructor.
     * @param DirectoryList $dir
     */
    public function __construct(DirectoryList $dir)
    {
        $this->dir = $dir;
    }

    /**
     * @param $paymentId
     * @return string
     */
    public function getLockFileName($paymentId)
    {
        return $this->dir->getPath('tmp') . DIRECTORY_SEPARATOR . $paymentId . ".lock";
    }

    /**
     * @param $paymentId
     */
    public function waitForUnlock($paymentId)
    {
        $filename = $this->getLockFileName($paymentId);
        if (file_exists($filename)) {
            if ((time() - filectime($filename)) > self::MAX_LIFETIME) {
                $this->unlock($paymentId);
            } else {
                $waitingTime = 0;
                while ($waitingTime <= self::TIME_LOCK && file_exists($filename)) {
                    $waitingTime += self::TIME_SLEEP;
                    sleep(self::TIME_SLEEP);
                }
            }
        }
    }

    /**
     * @param $paymentId
     */
    public function lockAndBlock($paymentId)
    {
        $lockFile = fopen($this->getLockFileName($paymentId), "w");
        fclose($lockFile);
    }

    /**
     * @param $paymentId
     */
    public function unlock($paymentId)
    {
        $fileName = $this->getLockFileName($paymentId);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }
}
