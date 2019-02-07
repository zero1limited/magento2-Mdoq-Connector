<?php
namespace Mdoq\Connector\MagentoFramework\Backup;

use Magento\Framework\Archive;
use Magento\Framework\Backup\Db\BackupFactory;
use Magento\Framework\Backup\Filesystem\Iterator\File;

class Db extends \Magento\Framework\Backup\Db
{
    /**
     * @var BackupFactory
     */
    protected $_backupFactory;

    protected $sanitised;

    /**
     * @param BackupFactory $backupFactory
     */
    public function __construct(BackupFactory $backupFactory)
    {
        $this->_backupFactory = $backupFactory;
        parent::__construct($backupFactory);
    }

    /**
     * Implements Create Backup functionality for Db
     *
     * @param $sanitised
     * @return bool
     */
    public function create($sanitised = false, $excludedTables = null)
    {
        set_time_limit(0);
        ignore_user_abort(true);

        $this->_lastOperationSucceed = false;

        $backup = $this->_backupFactory->createBackupModel()->setTime(
            $this->getTime()
        )->setType(
            $this->getType()
        )->setPath(
            $this->getBackupsDir()
        )->setName(
            $this->getName()
        );

        $backupDb = $this->_backupFactory->createBackupDbModel();
        $backupDb->createBackup($backup, $sanitised, $excludedTables);

        $this->_lastOperationSucceed = true;

        return true;
    }
}
