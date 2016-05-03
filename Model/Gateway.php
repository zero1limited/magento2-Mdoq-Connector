<?php
namespace Zero1\Gateway\Model;

class Gateway
{
    /** @var string */
    protected $stdOut;

    /** @var string */
    protected $errOut;

    /** @var string */
    protected $debug;

    protected $post;

    protected $replacements = array(
        '<?php' => 'PHP_OPEN_TAG',
        '?>' => 'PHP_END_TAG',
    );

    public function run()
    {
        $this->post = json_decode(base64_decode(file_get_contents('php://input')), true);
        if(!isset($this->post['code'], $this->post['cmd'])){
            header('HTTP/1.1 500 Server Error');
            die('Post variables missing: '.json_encode($this->post));
        }

        $this->stdOut = '';
        $this->errOut = '';
        $this->debug = '';

        $cmd = $this->getCommand();
        $this->debug .= 'Using command: '.$cmd.'<br />';
        $code = $this->getCode();
        $this->debug .= '<hr />Using code: <hr />'.htmlentities($code).'<br />';

        $descriptor = array(
            0 => array('pipe', 'r'),    // Input
            1 => array('pipe', 'w'),    // StdOut
            2 => array('pipe', 'w'),    // ErrOut
        );

        $pipes = array();
        $process = proc_open($cmd, $descriptor, $pipes);
        if (is_resource($process)) {
            // Input
            fputs($pipes[0], $code);
            fclose($pipes[0]);

            // StdOut
            $this->stdOut = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // ErrOut
            $this->errOut = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            proc_close($process);
        } else {
            $this->debug .= 'proc_open() did not provide a valid resource<br />';
            header('HTTP/1.1 500 Server Error');
            die($this->debug);
        }

        if (!empty($this->errOut)) {
            $this->debug .= 'Error Out<br />';
            $this->debug .= $this->errOut;
            header('HTTP/1.1 500 Server Error');
            die($this->debug);
        }

        echo json_encode(array(
            'debug' => $this->debug,
            'content' => $this->stdOut,
        ));
        return;
    }

    protected function getCommand()
    {
        switch($this->post['cmd']){
            case 'php':
                return $this->locatePhp();
                break;
            default:
                header('HTTP/1.1 500 Server Error');
                die('Command "'.$this->post['cmd'].'" not valid');
        }
    }

    protected function locatePhp()
    {
        $this->debug .= '<hr />trying to locate php path<hr />';
        //space to add default paths if required
        $potentialLocations = array();
        $envPath = trim(shell_exec('echo $PATH'));
        $paths = explode(':', $envPath);
        foreach($paths as $path){
            $potentialLocations[$path.'/php'] = 1;
        }
        $typePPhp = trim(shell_exec('type -P php'));
        $typePhp = trim(str_replace('php is', '', shell_exec('type php')));
        $whichPhp = trim(shell_exec('which php'));
        $potentialLocations[$typePPhp] = 1;
        $potentialLocations[$typePhp] = 1;
        $potentialLocations[$whichPhp] = 1;
        //add on some custom ones
        $potentialLocations['/usr/local/bin/php'] = 1;

        $potentialLocations = array_keys($potentialLocations);

        $this->debug .= 'PATH: '.$envPath.'<br />';
        $this->debug .= 'type -P php: '.$typePPhp.'<br />';
        $this->debug .= 'type php: '.$typePhp.'<br />';
        $this->debug .= 'which php: '.$whichPhp.'<br />';
        $this->debug .= 'All possible paths: <br />';
        $this->debug .= print_r($potentialLocations, true);

        $phpFound = false;
        $phpLocation = '';
        foreach($potentialLocations as $potentialLocation){
            $this->debug .= 'checking: '.$potentialLocation.'<br />';
            if(!is_file($potentialLocation)){
                $this->debug .= 'not a file<br />';
            }else{
                if(!is_executable($potentialLocation)){
                    $this->debug .= 'not executable<br />';
                }else{
                    $version  = shell_exec($potentialLocation.' -v');
                    $this->debug .= 'version: '.$version.'<br />';
                    if(strpos($version, 'PHP') !== false && strpos($version, 'cli') !== false){
                        $phpFound = true;
                        $phpLocation = $potentialLocation;
                        break;
                    }
                }
            }
        }

        if(!$phpFound){
            $this->debug .= 'last ditch effort using "php"<br />';
            $version = shell_exec('php -v');
            $this->debug .= 'version: '.$version.'<br />';
            if(strpos($version, 'PHP') !== false){
                $phpLocation = 'php';
                $phpFound = true;
            }
        }

        if(!$phpFound){
            $this->debug .= 'all attempts to locate php failed<br />';
            header('HTTP/1.1 500 Server Error');
            die($this->debug);
        }

        return $phpLocation;
    }

    protected function getCode()
    {
        $code = $this->post['code'];
        foreach($this->replacements as $replaceWith => $toReplace){
            $code = str_replace($toReplace, $replaceWith, $code);
        }
        return $code;
    }
}