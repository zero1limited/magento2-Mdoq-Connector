<?php
namespace Mdoq\Connector\Model;

use Magento\Framework\App\DeploymentConfig;

class Backup
{
    protected $deploymentConfig;

    public function __construct(
        DeploymentConfig $deploymentConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
    }


    public function runBackup($sanitised = false, $excludedTables = null)
    {
        // Check mysqldump is installed
        try {
            $paths = shell_exec('echo $PATH');
            $paths = explode(':', $paths);

            $fullMysqldumpPath = null;
            foreach ($paths as $path) {
                if (is_file($path . '/mysqldump')) {
                    if (is_executable($path . '/mysqldump')) {
                        $fullMysqldumpPath = $path . '/mysqldump';
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('A backup cannot be taken because \'mysqldump\' is not installed on your server. Please use the core Magento backup commands.');
        }

        if(!$fullMysqldumpPath) {
            throw new \Exception('A backup cannot be taken because \'mysqldump\' is not installed on your server. Please use the core Magento backup commands.');
        }

        // Get DB connection credentials
        $credentials = array(
            'host' => $this->deploymentConfig->get('db/connection/default/host'),
            'dbname' => $this->deploymentConfig->get('db/connection/default/dbname'),
            'username' => $this->deploymentConfig->get('db/connection/default/username'),
            'password' => $this->deploymentConfig->get('db/connection/default/password')
        );

        foreach($credentials as $key => $value) {
            if($value == null) {
                throw new \Exception('A backup cannot be taken because the database credential: \''.$key.'\' is missing from env.php. Please check values in env.php.');
            }
        }

        $backupLocation = getcwd().'/var/backups';

        $backupCode = 'db';
        if($sanitised) {
            $backupCode = 'sanitiseddb';
        }
        $backupName = time().'_'.$backupCode.'.sql';
        $backupPath = $backupLocation.'/'.$backupName;

        if($sanitised) {
            $mdoqCoreExcludedTables = array('report_event','customer_address_entity','customer_address_entity_datetime','customer_address_entity_decimal','customer_address_entity_int','customer_address_entity_text','customer_address_entity_varchar','customer_entity','customer_entity_datetime','customer_entity_decimal','customer_entity_int','customer_entity_text','customer_entity_varchar','customer_grid_flat','sales_creditmemo','sales_creditmemo_comment','sales_creditmemo_grid','sales_creditmemo_item','sales_invoice','sales_invoice_comment','sales_invoice_grid','sales_invoice_item','sales_invoiced_aggregated','sales_invoiced_aggregated_order','sales_order','sales_order_address','sales_order_aggregated_created','sales_order_aggregated_updated','sales_order_grid','sales_order_item','sales_order_payment','sales_order_status','sales_order_status_history','sales_order_status_label','sales_order_status_state','sales_order_tax','sales_order_tax_item','sales_payment_transaction','sales_refunded_aggregated','sales_refunded_aggregated_order','sales_sequence_meta','sales_sequence_profile','sales_shipment','sales_shipment_comment','sales_shipment_grid','sales_shipment_item','sales_shipment_track','sales_shipping_aggregated','sales_shipping_aggregated_order','quote','quote_address','quote_address_item','quote_id_mask','quote_item','quote_item_option','quote_payment','quote_shipping_rate', 'magento_sales_creditmemo_grid_archive', 'magento_sales_invoice_grid_archive', 'magento_sales_order_grid_archive', 'magento_sales_shipment_grid_archive');
            if($excludedTables != null) {
                $excludedTables = explode(',', $excludedTables);
                $excludedTables = array_merge($excludedTables, $mdoqCoreExcludedTables);
            } else {
                $excludedTables = array();
                $excludedTables = array_merge($excludedTables, $mdoqCoreExcludedTables);
            }

            if(count($excludedTables) == 0) {
                $excludedTablesString = '';
            }

            $excludedTablesString = '';
            foreach($excludedTables as $table) {
                $excludedTablesString .= ' --ignore-table='.$credentials['dbname'].'.'.$table;
            }
        } else {
            $excludedTablesString = '';
        }

        // Make the backup file
        $this->runCommand('mkdir -p '.$backupLocation);
        $this->runCommand('touch '.$backupPath);

        // Run the backup
        if($sanitised && $excludedTablesString != null) {
            $command = $fullMysqldumpPath.' --user='.$credentials['username'].' --password='.$credentials['password'].' --host='.$credentials['host'].' '.$credentials['dbname'].$excludedTablesString.' > '.$backupPath;
        } else {
            $command = $fullMysqldumpPath.' --user='.$credentials['username'].' --password='.$credentials['password'].' --host='.$credentials['host'].' '.$credentials['dbname'].' > '.$backupPath;
        }

        $this->runCommand($command);

        return array(
            'backupName' => $backupName,
            'backupPath' => $backupPath
        );
    }

    protected function runCommand($cmd)
    {
//        $descriptor = array(
//            0 => array('pipe', 'r'),    // Input
//            1 => array('pipe', 'w'),    // StdOut
//            2 => array('pipe', 'w'),    // ErrOut
//        );
//
//        $pipes = array();
//        $process = proc_open($cmd, $descriptor, $pipes, null, array('cmd' => $cmd));
//        if (is_resource($process)) {
//            // StdOut
//            $stdOut = stream_get_contents($pipes[1]);
//            fclose($pipes[1]);
//
//            // ErrOut
//            $errOut = stream_get_contents($pipes[2]);
//            fclose($pipes[2]);
//
//            proc_close($process);
//        } else {
//            throw new \Exception('An error occurred while creating the backup.');
//        }
//
//        if (!empty($errOut)) {
//            if(is_string($errOut)) {
//                if(strpos($errOut, '[Warning]') === false) {
//                    throw new \Exception('An error occurred while creating the backup.');
//                }
//            } else {
//                throw new \Exception('An error occurred while creating the backup.');
//            }
//        }
//
//        return trim($stdOut);

        $response = shell_exec($cmd);
        return trim($response);
    }
}
