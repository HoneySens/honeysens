<?php
namespace HoneySens\app\models\platforms;

use FileUpload\File;
use HoneySens\app\models\entities\Platform;
use HoneySens\app\models\entities\Firmware;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;

/**
 * The BeagleBone Black platform
 *
 * @Entity
 */
class BeagleBoneBlack extends Platform {

    /**
     * Add a firmware revision to this platform.
     * Returns a string that uniquely identifies the firmware (e.g. its location on disk).
     *
     * @param Firmware $firmware
     * @param File $file
     * @param ServiceManager $serviceManager
     * @return string
     */
    public function registerFirmware(Firmware $firmware, File $file, ServiceManager $serviceManager) {
        $fileName = preg_replace('/\s+/', '-', strtolower((string) $firmware->getName())) . '-' . preg_replace('/\s+/', '-', strtolower((string) $firmware->getVersion())) . '.tar.gz';
        exec('mv ' . escapeshellarg($file->getRealPath()) . ' ' . realpath(APPLICATION_PATH . '/../data/firmware/') . '/' . $fileName);
        $firmware->setSource($fileName);
        return $fileName;
    }

    /**
     * Removes the firmware file (if any) from a given firmware revision.
     *
     * @param Firmware $firmware
     * @param ServiceManager $serviceManager
     */
    public function unregisterFirmware(Firmware $firmware, ServiceManager $serviceManager) {
        if($this->isFirmwarePresent($firmware, $serviceManager))
            unlink($this->getFirmwarePath() . $firmware->getSource());
    }

    /**
     * Checks if the firmware data (source) is registered and available.
     *
     * @param Firmware $firmware
     * @param ServiceManager $serviceManager
     * @return bool
     */
    public function isFirmwarePresent(Firmware $firmware, ServiceManager $serviceManager) {
        return $firmware->getSource() != null && file_exists($this->getFirmwarePath() . $firmware->getSource());
    }

    /**
     * Returns the full path to the raw data file that belongs to this firmware.
     *
     * @param Firmware $firmware
     * @param ServiceManager $serviceManager
     * @return null|string
     * @throws NotFoundException
     */
    public function obtainFirmware(Firmware $firmware, ServiceManager $serviceManager) {
        if(!$this->isFirmwarePresent($firmware, $serviceManager)) throw new NotFoundException();
        return $this->getFirmwarePath() . $firmware->getSource();
    }

    /**
     * Returns the URI that can be used to download the firmware from the server.
     *
     * @param Firmware $firmware
     * @return string
     */
    public function getFirmwareURI(Firmware $firmware) {
        return '/api/platforms/firmware/' . $firmware->getId() . '/raw';
    }

    private function getFirmwarePath() {
        return realpath(APPLICATION_PATH . '/../data/firmware/') . '/';
    }
}
