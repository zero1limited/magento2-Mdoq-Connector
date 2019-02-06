<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mdoq\Connector\Console\Command;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\BackupRollbackFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * Command to backup code base and user data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BackupCommand extends Command
{
    /**
     * Name of input options
     */
    const INPUT_KEY_DB = 'full-db';
    const INPUT_KEY_SANITISED_DB = 'sanitised-db';
    const ARGUMENT_EXCLUDE_TABLES = 'excluded-tables';

    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Factory for BackupRollback
     *
     * @var BackupRollbackFactory
     */
    private $backupRollbackFactory;

    /**
     * Existing deployment config
     *
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * Constructor
     *
     * @param DeploymentConfig $deploymentConfig
     * @param BackupRollbackFactory $backupRollbackFactory
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        BackupRollbackFactory $backupRollbackFactory
    ) {
        $this->backupRollbackFactory = $backupRollbackFactory;
        $this->deploymentConfig = $deploymentConfig;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::INPUT_KEY_DB,
                null,
                InputOption::VALUE_NONE,
                'Take complete database backup'
            ),
            new InputOption(
                self::INPUT_KEY_SANITISED_DB,
                null,
                InputOption::VALUE_NONE,
                'Take sanitised database backup'
            ),
            new InputArgument(
                self::ARGUMENT_EXCLUDE_TABLES,
                InputArgument::OPTIONAL,
                'Excluded tables (for sanitised backups only)'
            ),
        ];
        $this->setName('mdoq:backup')
            ->setDescription('Takes backup of Magento database')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!$input->getOption(self::INPUT_KEY_DB) && !$input->getOption(self::INPUT_KEY_SANITISED_DB)) {
            throw new \InvalidArgumentException(
                'Invalid arguments. Use \'--full-db\' for a full database backup, or \'--sanitised-db\' for a sanitised backup. If using a \'--sanitised-db\' you can provide additional tables to be excluded (comma separated). Example: \'--sanitised-db --excluded-tables table1,table2\''
            );
        }

        if($input->getOption(self::INPUT_KEY_DB) && $input->getArgument(self::ARGUMENT_EXCLUDE_TABLES) != null) {
            throw new \InvalidArgumentException(
                'Invalid arguments. You cannot provide tables to exclude on a full DB backup.'
            );
        }

        if (!$this->deploymentConfig->isAvailable()
            && ($input->getOption(self::INPUT_KEY_DB) || $input->getOption(self::INPUT_KEY_SANITISED_DB))) {
            $output->writeln("<info>No information is available: the Magento application is not installed.</info>");
            // We need exit code higher than 0 here as an indication
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        try {
            $inputOptionProvided = false;
            $time = time();
            $backupHandler = $this->backupRollbackFactory->create($output);
            if ($input->getOption(self::INPUT_KEY_DB)) {
                $this->setAreaCode();
                $backupHandler->dbBackup($time, false, null);
                $inputOptionProvided = true;
            }
            if ($input->getOption(self::INPUT_KEY_SANITISED_DB)) {
                $this->setAreaCode();
                $backupHandler->dbBackup($time, true, $input->getArgument(self::ARGUMENT_EXCLUDE_TABLES));
                $inputOptionProvided = true;
            }
            if (!$inputOptionProvided) {
                throw new \InvalidArgumentException(
                    'Not enough information provided to take backup.'
                );
            }
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    /**
     * Sets area code to start a session for database backup and rollback
     *
     * @return void
     */
    private function setAreaCode()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $areaCode = 'adminhtml';
        /** @var \Magento\Framework\App\State $appState */
        $appState = $objectManager->get(\Magento\Framework\App\State::class);
        $appState->setAreaCode($areaCode);
        /** @var \Magento\Framework\ObjectManager\ConfigLoaderInterface $configLoader */
        $configLoader = $objectManager->get(\Magento\Framework\ObjectManager\ConfigLoaderInterface::class);
        $objectManager->configure($configLoader->load($areaCode));
    }
}
