<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 19.01.18
 * Time: 12:44
 */

namespace Kick;


use Kick\Tpl\TplWriter;

class KickFacet
{

    const CONF_STATE_FILE = "/tmp/kick.state";

    private $workingDir;
    private $yamlFileName;
    private $config = [];

    private $execBox;

    public function __construct(string $startYamlFileName = null)
    {
        if ($startYamlFileName === null)
            $startYamlFileName = "/opt/.kick.yml";
        $this->yamlFileName = $startYamlFileName;
        $this->workingDir = dirname($startYamlFileName);
        try {
            $this->config = kicker_yaml_parse_file($startYamlFileName);
        } catch (\InvalidArgumentException $e) {
            Out::fail("Error parsing '.kick.yml': " . $e->getMessage());
            throw $e;
        }

        if (file_exists(self::CONF_STATE_FILE)) {
            $this->execBox = unserialize(
                file_get_contents(self::CONF_STATE_FILE),
                [
                    "allowed_classes" => [ ExecBox::class ]
                ]
            );
        } else {
            $this->execBox = new ExecBox($this->workingDir);
        }

    }

    private $skipWriteStateFile = false;

    public function __destruct()
    {
        if ($this->skipWriteStateFile == true)
            return ;
        file_put_contents(self::CONF_STATE_FILE, serialize($this->execBox));
    }


    public function dispatchCmd ($cmd, array $options)
    {
        switch ($cmd) {

            case "":
            case "help":
                $this->skipWriteStateFile = true;
                echo "Kickstart command runner (evaluating /opt/.kick.yml)\n";
                echo "\nUsage:";
                echo "\n    kick [command]";
                echo "\n";
                echo "\nCommands:";

                foreach ($this->config["command"] as $cmd => $commands) {
                    echo "\n    $cmd:";
                    foreach ($commands as $command)
                    echo "\n    - $command";
                    echo "\n";
                }

                echo "\nOr predefined commands: kill, kick_to_env, write_config_file, help\n";
                return true;

            case "kill":
                $this->execBox->killAll();
                Out::log("Killed all background services");
                return true;

            case "kick_to_env":
                // Output env variables to be added via env
                $this->skipWriteStateFile = true;
                foreach ($this->config as $key=>$value) {
                    if (is_array($value))
                        continue;

                    // Multiline Strings: eval "varname=$'Quaoted\nString'" (Dollar is new bash syntax)
                    // This is for backwards compatibility
                    $value = "\$" . escapeshellarg($value);
                    $value = str_replace("\r\n", '\n', $value);
                    $value = str_replace("\n", '\n', $value);

                    $env = "export KICK_" . strtoupper($key) . "=$value";
                    echo "$env\n";
                }

                if (is_array($this->config["env"])) {
                    foreach ($this->config["env"] as $eName => $eVal)
                    {
                        if (is_int($eName)) {
                            echo "export " . $eVal . "\n";
                        } else {
                            $env = "$eName=$eVal";
                            echo "export $env\n";
                        }
                    }
                }

                return true;

            case "write_config_file":
                $this->skipWriteStateFile = true;
                $tplDir = $this->workingDir . "/.kicker/conf";
                $defaultTplDir = "/kickstart/conf";
                if (is_dir($defaultTplDir)) {
                    Out::log("Copying container default config files from '$defaultTplDir' -> '/'...");
                    $tplWriter = new TplWriter();
                    $tplWriter->parse($defaultTplDir, "/");
                    Out::log("Done");
                }
                if (is_dir($tplDir)) {

                    Out::log("Copying config files from '$tplDir' -> '/'...");
                    $tplWriter = new TplWriter();
                    $tplWriter->parse($tplDir, "/");
                    Out::log("Done");
                }
                if ( ! isset($this->config["config_file"])) {
                    return true;
                }
                if ( ! isset($this->config["config_file"]["template"])) {
                    Out::fail("Parameter 'config_file.template' missing in .kick.yml");
                    throw new \InvalidArgumentException("Parameter 'config_file.template' missing");
                }
                if ( ! isset($this->config["config_file"]["target"])) {
                    Out::fail("Parameter 'config_file.target' missing in .kick.yml");
                    throw new \InvalidArgumentException("Parameter 'config_file.target' missing");
                }

                ConfigWriter::WriteConfig($this->config["config_file"]["template"], $this->config["config_file"]["target"]);
                return true;

            default:
                // search for command in command: - section
                break;
        }

        $installPackages = access($this->config, ["packages"]);
        if (is_string($installPackages))
            $installPackages = explode(" ", $installPackages);

        if ($cmd === "build" && $installPackages !== null) {
            Out::log("Installing packages: ", implode(", ", $installPackages), " via apt");
            system ("sudo apt-get update");
            system("sudo apt-get install -y " . implode(" ", $installPackages), $result);
            if ($result !== 0)
                throw new \Exception("Installing packages via apt failed: " . implode(",", $installPackages));
            system("sudo rm -fR /var/lib/apt/lists/* /var/cache/apt/archives/*");
        }

        if ( ! $value = access($this->config, ["command", $cmd])) {
            if (in_array($cmd, ["build", "init", "dev", "run", "interval"])) {
                if ($cmd != "interval") {
                    Out::log("No command defined for '$cmd': Ignore!");
                }
                return true;
            }
            throw new \InvalidArgumentException("Command '$cmd' not defined in {$this->yamlFileName}.");
        }



        if ( ! is_array($value))
            $value = [$value];
        foreach ($value as $cur) {
            $this->execBox->runBg($cur, $cmd);
        }


    }


    public function dispatch(array $argv)
    {
        try {
            $call = array_shift($argv);
            $cmd = (string)array_shift($argv);
            $this->dispatchCmd($cmd, $argv);
        } catch (\Exception $e) {
            Out::fail("Exception: " . $e->getMessage() . "");
            throw $e;
        }
    }
}