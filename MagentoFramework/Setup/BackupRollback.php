<?php
namespace Mdoq\Connector\MagentoFramework\Setup;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Backup\Factory;
use Magento\Framework\Backup\Exception\NotEnoughPermissions;
use Magento\Framework\Backup\Filesystem\Helper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\LoggerInterface;

class BackupRollback extends \Magento\Framework\Setup\BackupRollback
{
    /**
     * Default backup directory
     */
    const DEFAULT_BACKUP_DIRECTORY = 'backups';

    /**
     * Path to backup folder
     *
     * @var string
     */
    private $backupsDir;

    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $log;

    /**
     * Filesystem Directory List
     *
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * File
     *
     * @var File
     */
    private $file;

    /**
     * Filesystem Helper
     *
     * @var Helper
     */
    private $fsHelper;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $log
     * @param DirectoryList $directoryList
     * @param File $file
     * @param Helper $fsHelper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $log,
        DirectoryList $directoryList,
        File $file,
        Helper $fsHelper
    ) {
        $this->objectManager = $objectManager;
        $this->log = $log;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->fsHelper = $fsHelper;
        $this->backupsDir = $this->directoryList->getPath(DirectoryList::VAR_DIR)
            . '/' . self::DEFAULT_BACKUP_DIRECTORY;

        parent::__construct($objectManager, $log, $directoryList, $file, $fsHelper);
    }

    /**
     * Take backup for database
     *
     * @param int $time
     * @param bool $sanitised
     * @return string
     */
    public function dbBackup($time, $sanitised = false, $excludedTables = null)
    {
        /** @var \Mdoq\Connector\MagentoFramework\Backup\Db $dbBackup */
        $dbBackup = $this->objectManager->create(\Magento\Framework\Backup\Db::class);
        $dbBackup->setRootDir($this->directoryList->getRoot());
        if (!$this->file->isExists($this->backupsDir)) {
            $this->file->createDirectory($this->backupsDir);
        }
        $dbBackup->setBackupsDir($this->backupsDir);
        $dbBackup->setBackupExtension('sql');
        $dbBackup->setTime($time);
        if($sanitised) {
            $this->log->log('Sanitised DB backup is starting... (without maintenance mode)');
        } else {
            $this->log->log('Full DB backup is starting... (without maintenance mode)');
        }
        $dbBackup->create($sanitised, $excludedTables);
        $this->log->log('DB backup filename: ' . $dbBackup->getBackupFilename());
        $this->log->log('DB backup path: ' . $dbBackup->getBackupPath());
        $this->log->logSuccess('DB backup completed successfully.');
        return $dbBackup->getBackupPath();
    }
}