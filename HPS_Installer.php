<?php
define('LOG_FILE', dirname(__FILE__) . '/HPS_Heartland.log');
define('SH_FILE', dirname(__FILE__) . '/HPS_Install.sh');
define('TEE_COM', ' | tee –a ' . LOG_FILE);
define('MAGENTO_RECOMMENDED_MEMORY', pow(1024, 2) * 2); // about 2 GB

@unlink(SH_FILE);
@unlink(__FILE__);
set_time_limit(0);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
/*
 * This script will attempt to auto generate a Bash script specific to your Magento 2.1 instalation that you can use to install the Heartland Plugin
 * This script is experimental and while we don't expect any issues it is always advisable to use on a test system before putting anything in place in production
 *
 */
writeLog('Automated install');

if (PHP_SAPI !== 'cli') {

    writeLog('HPS_Installer must be run as a CLI application');
    //echo '<pre>';
    exit(1);
}

if (PHP_OS !== 'Linux') {
    writeLog("This installer and Magento 2.1 are only supported on Linux Distros\nhttp://devdocs.magento.com/guides/v2.1/install-gde/system-requirements-tech.html");
    //echo '<pre>';
    exit(1);
}

// information about the process user
$cu = UserInfo::currentProcessUser();
$cug = $cu->getUserName() . ":" . $cu->getGroupName();

// information about the Linux Distro
$LinuxOSInfo = new LinuxOSInfo;
$meminfo = (int)floor(preg_replace('/[\D]/', '', exec('cat /proc/meminfo | grep MemTotal'))); //2097152 about 2 GB

// file information about the scripts location
$thisDir = dirname(__FILE__);
$gitRepoFolder = $thisDir . DIRECTORY_SEPARATOR . 'heartland-magento2-module';

// an attempt to locate the Magento Install and important other locations
$magentoCommandLine = exec('find / -path "*/bin/magento" -not -path "*/vendor/*/*/*/magento" 2>/dev/null');
$magentoCommandLineOwnerGroup = filegroup($magentoCommandLine);
$magentoCommandLineOwnerInGroup = UserInfo::inGroup($cu->getUser(), $magentoCommandLineOwnerGroup);
$magentoBaseDir = preg_replace('/\/bin\/magento$/', '', $magentoCommandLine);
$magentoCodeFolder = $magentoBaseDir . DIRECTORY_SEPARATOR . 'app/code';
$magentoVersion = json_decode(file_get_contents($magentoBaseDir . '/composer.json'), true)['version'];
list($M2_MAJOR_VERSION, $M2_MINOR_VERSION, $M2_RELEASE_VERSION) = explode('.', $magentoVersion);
$PHP_MINOR_MIN = 5+$M2_MINOR_VERSION;
$PHP_RELEASE_MIN = $M2_MINOR_VERSION ? 00 : 22;
// information about the PHP settings
$minVersion = ((PHP_MAJOR_VERSION . PHP_MINOR_VERSION >= (35+($M2_MAJOR_VERSION .$M2_MINOR_VERSION)) ) && PHP_RELEASE_VERSION >= $PHP_RELEASE_MIN);
$memory_limit = ini_get('memory_limit');

$always_populate_raw_post_data = ini_get('always_populate_raw_post_data');

writeLog('If problems were encountered, please post the log file with your issue');
writeLog('Please submit issues to https://github.com/hps/heartland-magento2-module/issues');
writeLog('Log file found in {' . LOG_FILE . '}');
writeLog('Please also attach all log files from ' . $magentoBaseDir . '/var/log');
writeLog('try \'zip MagentoLogs.zip ' . $magentoBaseDir . '/var/log/*.log\'');

writeLog('OS = ' . PHP_OS . "\r\n");

writeLog("Your current Distro: " . $LinuxOSInfo->getDistribDescription());

if ($meminfo < MAGENTO_RECOMMENDED_MEMORY) {
    writeLog("Please note that you do not have the recommended physical memory
You have: {$meminfo}kB which is less than the recommended 2GB
http://devdocs.magento.com/guides/v2.1/install-gde/system-requirements-tech.html
Your installation success could be adversely affected
If this installation fails you will need to perform all steps manually
See manualInstall.txt in this directory
");
    file_put_contents('manualInstall.txt', $manualBashScript);
}
writeLog('Memory Check Passed: YOURS=' . round($meminfo / pow(1024, 2), 2) . 'GB>REQUIRED=' . round( MAGENTO_RECOMMENDED_MEMORY / pow(1024, 2), 2) . "GB");

writeLog("Script running as: " . $cug);

if (!$minVersion) {
    writeLog("manualBashScript written to file \r\nExecute these lines one at a time if you have issues\r\n" . $manualBashScript . "\r\nend manualBashScript");
    exit(1);
}

writeLog("PHP Version: " . PHP_VERSION);
writeLog(' *  Which ' . ($minVersion ? 'fulfills' : 'does not fullfill') . ' the minimum requirement of 5.'.$PHP_MINOR_MIN.'.'.$PHP_RELEASE_MIN.' or greater');

writeLog("Installed Magento Version " . $magentoVersion);

if ($magentoCommandLine === '') {

    writeLog("Could not reliably determine your Magento install path. \n
    Please enls csure that this script is in or above the magento install. \n
    This could also occur if your directory permissions prevent read operations by the web service user " . $cug);
    exit(1);
}

writeLog("Magento v" . $magentoVersion . " found in: " . $magentoBaseDir);

writeLog("Magento CLI path = " . $magentoCommandLine);

if (!$magentoCommandLineOwnerInGroup) {
    writeLog("The user account you are executing this script as({$cug}) is not part of the Magento 2.1 filesystems group({$magentoCommandLineOwnerGroup})
Please be aware that you can only perform plugin operations as the Magento 2.1 filesystem owner or as a member of that group
This script may not succeed continue
Try 'usermod -G " . posix_getgrgid(posix_getpwuid(fileowner($magentoCommandLine))['gid'])['name'] . " " . $cu->getUserName()
        . "' and then retry");
    exit(1);
}

writeLog($cug . " is " . ( $magentoCommandLineOwnerInGroup ? '' : 'not ' ) . "a member of your Magento filesystem owner group " . posix_getgrgid(posix_getpwuid(fileowner($magentoCommandLine))['gid'])['name']);
$manualBashScript = <<<BSH
git clone https://github.com/hps/heartland-magento2-module.git
cp -Ra heartland-magento2-module/HPS ' . $magentoCodeFolder . '/HPS
rm -rf ' . $gitRepoFolder . '
rm -rf ' . $magentoBaseDir . '/var/cache/*
rm -rf ' . $magentoBaseDir . '/var/page_cache/*
rm -rf ' . $magentoBaseDir . '/var/generation/*
rm -rf ' . $magentoBaseDir . '/var/di
rm -rf ' . $magentoBaseDir . '/pub/static/adminhtml
rm -rf ' . $magentoBaseDir . '/pub/static/frontend
rm -rf ' . $magentoBaseDir . '/var/report/*
cd ' . $magentoBaseDir . '
composer require hps/heartland-php
composer composer update
php ' . $magentoCommandLine . ' cache:clean
php ' . $magentoCommandLine . ' module:enable HPS_Heartland
php ' . $magentoCommandLine . ' setup:upgrade
php ' . $magentoCommandLine . ' setup:di:compile
php ' . $magentoCommandLine . ' setup:static-content:deploy
BSH;








$bashScript = <<<BSH
cd {$magentoBaseDir}
clear

echo [\$(date --rfc-3339=seconds)] - Starting Automated Install 

echo [\$(date --rfc-3339=seconds)] - "Starting HPS_Heartland install on {$magentoVersion}"

if [ {$magentoCommandLine} ] ; then
    echo "[\$(date --rfc-3339=seconds)] - Found {$magentoCommandLine}"

    echo "[\$(date --rfc-3339=seconds)] - Magento Base Directory Found: {$magentoBaseDir}"

    echo "[\$(date --rfc-3339=seconds)] - Downloading HPS_Heartland from github" 
    git clone https://github.com/hps/heartland-magento2-module.git

    echo "[\$(date --rfc-3339=seconds)] - Creating the Dir {$magentoCodeFolder} "
    rm -rf {$magentoCodeFolder}/HPS 2> null
    mkdir {$magentoCodeFolder} 2> null

    echo "[\$(date --rfc-3339=seconds)] - Moving the HPS_Heartland repo files to {$magentoCodeFolder}/HPS"
    cp -Ra heartland-magento2-module/HPS {$magentoCodeFolder}/HPS

    echo "Delete the download folder the HPS_Heartland"
    echo "[\$(date --rfc-3339=seconds)] - Delete the download folder the HPS_Heartland" 
    rm -rf heartland-magento2-module

    echo "[\$(date --rfc-3339=seconds)] - Get Dependencies"
    composer require hps/heartland-php

    echo "[\$(date --rfc-3339=seconds)] - Clearing all cache" 
    rm -rf {$magentoBaseDir}/var/cache/*
    rm -rf {$magentoBaseDir}/var/page_cache/*
    rm -rf {$magentoBaseDir}/var/generation/*
    rm -rf {$magentoBaseDir}/var/di
    rm -rf {$magentoBaseDir}/pub/static/adminhtml
    rm -rf {$magentoBaseDir}/pub/static/frontend
    rm -rf {$magentoBaseDir}/var/report/*

    echo "[\$(date --rfc-3339=seconds)] - Execute cache:clean" 
    php {$magentoCommandLine} cache:clean

    echo "[\$(date --rfc-3339=seconds)] - Execute module:enable HPS_Heartland"
    php {$magentoCommandLine} module:enable HPS_Heartland

    echo "[\$(date --rfc-3339=seconds)] - Executing setup:upgrade --keep-generated" 
    php {$magentoCommandLine} setup:upgrade --keep-generated

    echo "[\$(date --rfc-3339=seconds)] - Executing setup:di:compile" 
    php {$magentoCommandLine} setup:di:compile

    echo "[\$(date --rfc-3339=seconds)] - Executing setup:static-content:deploy" 
    php {$magentoCommandLine} setup:static-content:deploy

    echo "[\$(date --rfc-3339=seconds)] - Done Installing HPS_Heartland" 
else
    echo "[\$(date --rfc-3339=seconds)] - Sorry we could not automate the process of installing our HPS_Heartland plug-in" 

    echo "[\$(date --rfc-3339=seconds)] - Please submit an issue https://github.com/hps/heartland-magento2-module/issues" 
fi
echo Log file found in {LOG_FILE}
echo If problems were encountered please post the log file with your issue
echo Please also attach all log files from {$magentoBaseDir}/var/log
echo try 'zip MagentoLogs.zip {$magentoBaseDir}/var/log/*.log'
php {$magentoCommandLine} -V
php {$magentoCommandLine} info:adminuri
echo "If you had issues, Please submit an issue https://github.com/hps/heartland-magento2-module/issues"
BSH;
file_put_contents(SH_FILE, $bashScript);

echo 'All checks passed.';
echo "\n";
echo "To complete please execute 'sh HPS_Install.sh'  ";
echo "\n";
exit;


function writeLog($content) {
    if (!file_exists(LOG_FILE)) {
        echo("Log File found at " . LOG_FILE . "\n");
    }
    $content = trim($content);
    $content = '[' . date('c') . "] - {$content} \n";
    echo $content;
    //file_put_contents(LOG_FILE,$content);
}

function findFile($path) {
    return glob($path);
}

function findMagento2() {
    return glob('*/app/code', GLOB_ONLYDIR);
}

class UserInfo {
    /**
     * @var null
     */
    private $user = null;
    /**
     * @var null
     */
    private $userName = null;
    /**
     * @var null
     */
    private $group = null;
    /**
     * @var null
     */
    private $groupName = null;
    /**
     * @var bool
     */
    private $sudo = false;

    /**
     * @param $uid
     * @param $uid
     * @return bool
     */
    static function inGroup($uid1, $uid2) {
        return (bool)in_array(posix_getpwuid((int)$uid1)['name'], posix_getgrgid($uid2)['members']);
    }

    /**
     * @return $this
     */
    static function currentProcessUser() {
        return (new UserInfo())->setUser(posix_getuid());
    }

    /**
     * @return null
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param null $user
     */
    public function setUser($user) {
        $this->user = (int)$user;
        $this->setUserName($user);
        $this->setGroup(posix_getgrgid(posix_getpwuid($user)['gid']));
        $this->setSudo();
        return $this;
    }

    /**
     * @return null
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @return null
     */
    public function getGroupName() {
        return $this->groupName;
    }

    /**
     * @return boolean
     */
    public function isSudo() {
        return (bool)@in_array($this->getUserName(), posix_getgrgid(0)['members']);
    }

    /**
     * @return null
     */
    public function getUserName() {
        return $this->userName;
    }

    /**
     * @param null $userName
     */
    private function setUserName($uid) {
        $this->userName = posix_getpwuid((int)$uid)['name'];
    }

    /**
     * @param null $group
     */
    private function setGroup($group) {
        $this->group = $group['gid'];
        $this->setGroupName($group['name']);
    }

    /**
     * @param null $groupName
     */
    private function setGroupName($groupName) {
        $this->groupName = $groupName;
    }

    /**
     * @param boolean $sudo
     */
    private function setSudo() {
        $this->sudo = $this->isSudo();
    }


}


/**
 * Class LinuxOSInfo
 */
class LinuxOSInfo {

    /**
     * @var
     */
    private $distrib_id; //Ubuntu

    /**
     * @var
     */
    private $distrib_release; //14.04

    /**
     * @var
     */
    private $distrib_codename; //trusty

    /**
     * @var
     */
    private $distrib_description; //Ubuntu 14.04.5 LTS

    /**
     * @var
     */
    private $name; //Ubuntu

    /**
     * @var
     */
    private $version; //14.04.5 LTS, Trusty Tahr

    /**
     * @var
     */
    private $id; //ubuntu

    /**
     * @var
     */
    private $id_like; //debian

    /**
     * @var
     */
    private $pretty_name; //Ubuntu 14.04.5 LTS

    /**
     * @var
     */
    private $version_id; //14.04

    /**
     * @var
     */
    private $home_url; //http://www.ubuntu.com/

    /**
     * @var
     */
    private $support_url; //http://help.ubuntu.com/

    /**
     * @var
     */
    private $bug_report_url;

    /**
     * LinuxOSInfo constructor.
     */
    public function __construct() {
        if (PHP_OS === 'Linux') {
            try {
                $files = glob('/etc/*-release');
                foreach ($files as $file) {
                    $lines = array_filter(array_map(function($line) {

                        // split value from key
                        $parts = explode('=', $line);

                        // makes sure that "useless" lines are ignored (together with array_filter)
                        if (count($parts) !== 2) {
                            return false;
                        }

                        // remove quotes, if the value is quoted
                        $parts[1] = str_replace(array('"', "'"), '', $parts[1]);
                        return $parts;

                    }, file($file)));
                    foreach ($lines as $line) {
                        $this->__set($line[0], trim($line[1]));
                    }
                }
            } catch (Exception $e) {
                die('This script only works on Linux Distros');
            }
        }
        else {
            die('This script only works on Linux Distros, Not ' . PHP_OS);
        }

    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value) {
        $prop = strtolower($name);
        if (property_exists($this, $prop)) {
            $this->{$prop} = $value;
        }
    }

    /**
     * @return mixed
     */
    public function getDistribId() {
        return $this->distrib_id;
    }

    /**
     * @return mixed
     */
    public function getDistribRelease() {
        return $this->distrib_release;
    }

    /**
     * @return mixed
     */
    public function getDistribCodename() {
        return $this->distrib_codename;
    }

    /**
     * @return mixed
     */
    public function getDistribDescription() {
        return $this->distrib_description;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getIdLike() {
        return $this->id_like;
    }

    /**
     * @return mixed
     */
    public function getPrettyName() {
        return $this->pretty_name;
    }

    /**
     * @return mixed
     */
    public function getVersionId() {
        return $this->version_id;
    }

    /**
     * @return mixed
     */
    public function getHomeUrl() {
        return $this->home_url;
    }

    /**
     * @return mixed
     */
    public function getSupportUrl() {
        return $this->support_url;
    }

    /**
     * @return mixed
     */
    public function getBugReportUrl() {
        return $this->bug_report_url;
    }

}

?>
