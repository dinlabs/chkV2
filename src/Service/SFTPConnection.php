<?php

namespace App\Service;

/**
 *
 * install: apt install php-ssh2
 *
 */
class SFTPConnection
{
    private $connection;
    private $sftp;
    
    public function __construct($host, $port=22)
    {
        $this->connection = @ssh2_connect($host, $port);
        if (! $this->connection)
            throw new \Exception("Could not connect to $host on port $port.");
    }
    
    public function login($username, $password)
    {
        if(!@ssh2_auth_password($this->connection, $username, $password))
            throw new \Exception("Could not authenticate with username $username and password $password.");
        
        $this->sftp = @ssh2_sftp($this->connection);
        if(!$this->sftp)
            throw new \Exception("Could not initialize SFTP subsystem.");
    }
    
    public function scanFilesystem($remote_file)
    {
        $sftp = $this->sftp;
        
        // security
        if(substr($remote_file, 0, 1) != '/') $remote_file = '/' . $remote_file;
        if(substr($remote_file, -1, 1) != '/') $remote_file .= '/';
        
        $dir = "ssh2.sftp://$sftp$remote_file";
        $tempArray = array();
        
        if(is_dir($dir))
        {
            if($dh = opendir($dir))
            {
                while(($file = readdir($dh)) !== false)
                {
                    if(in_array($file, array('.', '..'))) continue; // security
                    
                    $filetype = filetype($dir . $file);
                    if($filetype == 'dir')
                    {
                        $tmp = $this->scanFilesystem($remote_file . $file . '/');
                        foreach($tmp as $t)
                        {
                            $tempArray[] = $file . '/' . $t;
                        }
                    }
                    else
                    {
                        $tempArray[] = $file;
                    }
                }
                closedir($dh);
            }
        }
        return $tempArray;
    }
    
    public function uploadFile($local_file, $remote_file)
    {
        $sftp = $this->sftp;
        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'w');
        
        if(!$stream)
            throw new \Exception("Could not open file: $remote_file");
        
        $data_to_send = @file_get_contents($local_file);
        if($data_to_send === false)
            throw new \Exception("Could not open local file: $local_file.");
        
        if(@fwrite($stream, $data_to_send) === false)
            throw new \Exception("Could not send data from file: $local_file.");
        
        @fclose($stream);
        
        return true;
    }
    
    public function receiveFile($remote_file, $local_file)
    {
        $sftp = $this->sftp;
        $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'r');
        if(!$stream)
            throw new \Exception("Could not open file: $remote_file");
        
        //$contents = fread($stream, filesize("ssh2.sftp://$sftp$remote_file"));
        $size = $this->getFileSize($remote_file);
        $contents = '';
        $read = 0;
        $len = $size;
        while($read < $len && ($buf = fread($stream, $len - $read)))
        {
            $read += strlen($buf);
            $contents .= $buf;
        }
        $return = file_put_contents ($local_file, $contents);
        @fclose($stream);
        
        return $return;
    }
    
    public function getFileSize($file)
    {
        $sftp = $this->sftp;
        return filesize("ssh2.sftp://$sftp$file");
    }
    
    public function deleteFile($remote_file)
    {
        $sftp = $this->sftp;
        return unlink("ssh2.sftp://$sftp$remote_file");
    }
}