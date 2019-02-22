<?php
namespace Mdoq\Connector\Console\Command;

use Magento\Framework\App\DeploymentConfig;
use Mdoq\Connector\Model\Backup;
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
     * Existing deployment config
     *
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * MDOQ Backup class
     *
     * @var Backup
     */
    protected $backup;

    /**
     * Constructor
     *
     * @param DeploymentConfig $deploymentConfig
     * @param Backup $backup
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        \Mdoq\Connector\Model\Backup $backup
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->backup = $backup;
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
                'Invalid arguments. Use \'--full-db\' for a full database backup, or \'--sanitised-db\' for a sanitised backup. If using a \'--sanitised-db\' you can provide additional tables to be excluded (comma separated). Example: \'--sanitised-db table1,table2\''
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
            if ($input->getOption(self::INPUT_KEY_DB)) {
                $this->setAreaCode();
                $output->writeln("<info>Starting MDOQ full DB backup. Time: ".time()."</info>");
                $response = $this->backup->runBackup(false, null);
                if(!isset($response['backupName']) || !isset($response['backupPath'])) {
                    throw new \Exception('An error occurred while creating the backup.');
                }

                $output->writeln("<info>DB backup filename: ".$response['backupName']."</info>");
                $output->writeln("<info>DB backup path: ".$response['backupPath']."</info>");

                $inputOptionProvided = true;
            }
            if ($input->getOption(self::INPUT_KEY_SANITISED_DB)) {
                $this->setAreaCode();
                $output->writeln("<info>Starting MDOQ sanitised DB backup. Time: ".time()."</info>");
                $response = $this->backup->runBackup(true, $input->getArgument(self::ARGUMENT_EXCLUDE_TABLES));

                if(!isset($response['backupName']) || !isset($response['backupPath'])) {
                    throw new \Exception('An error occurred while creating the backup.');
                }
                $output->writeln("<info>DB backup filename: ".$response['backupName']."</info>");
                $output->writeln("<info>DB backup path: ".$response['backupPath']."</info>");

                $inputOptionProvided = true;
            }
            if (!$inputOptionProvided) {
                throw new \InvalidArgumentException(
                    'Not enough information provided to take backup.'
                );
            }

            $output->writeln("<info>DB backup completed successfully.</info>");
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
