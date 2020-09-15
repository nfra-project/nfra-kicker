<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 19.01.18
 * Time: 14:14
 */

namespace Kick;


class ExecBox
{

    private $workingDir;

    public function __construct(string $workingDir)
    {
        $this->workingDir = $workingDir;
    }

    private $pids = [];

    public function runBg ($cmd, $debugCmd="")
    {
        chdir($this->workingDir);
        if (preg_match("/^D\:(.*)$/", $cmd, $matches)) {
            $cmd = $matches[1];
            Out::log("Exec in background:", $cmd);

            // Only way to do this: start commands in background and aquire pid by file
            $aPipes = [];
            $tty = "/dev/tty";
            if ( ! file_exists($tty))
                $tty = "/dev/stdout";
            $rProc = proc_open("bash -c 'trap \"kill 0\" EXIT; $cmd 2>&1 > $tty & wait' & echo $! > /tmp/curpid.kick", [], $aPipes);
            proc_close($rProc);
            $this->pids[] = (int) file_get_contents("/tmp/curpid.kick");
        } else if (preg_match("/^IT\:(.*)$/", $cmd, $matches)) {
            // Interactive Terminal (Ncurses etc)
            $cmd = $matches[1];
            $pipes = array (NULL, NULL, NULL);
            // Allow user to interact with dialog
            $in = fopen ('php://stdin', 'r');
            $out = fopen ('php://stdout', 'w');
            // But tell PHP to redirect stderr so we can read it
            $p = proc_open ($cmd, array (
                0 => $in,
                1 => $out,
                2 => array ('pipe', 'w')
            ), $pipes);
            // Wait for and read result
            $result = stream_get_contents ($pipes[2]);
            // Close all handles
            fclose ($pipes[2]);
            fclose ($out);
            fclose ($in);
            $ret = proc_close ($p);
            if ($ret != 0) {
                Out::fail("system('$cmd') returned exit-code $ret (defined in .kick.yml: command:{$debugCmd}: - see output above for more information)");
                exit ($ret);
            }
        } else {
            Out::log("Exec syncronously: '$cmd'");
            system($cmd, $ret);
            if ($ret != 0) {
                Out::fail("system('$cmd') returned exit-code $ret (defined in .kick.yml: command:{$debugCmd}: - see output above for more information)");
                exit ($ret);
            }
        }

    }


    public function killAll ()
    {
        foreach ($this->pids as $pid) {
            Out::log("Killing $pid...");
            system("kill -9 $pid");
        }
        $this->pids = [];
    }



}