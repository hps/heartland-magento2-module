<?php
@unlink('HPS_Heartland.log');
@unlink('HPS_Install.sh');
@unlink('HPS_Installer.php');
set_time_limit(0);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
exec('clear');
/*
 * This script will attempt to auto generate a Bash script specific to your Magento 2.1 instalation that you can use to install the Heartland Plugin
 *
 *
 */
file_put_contents('HPS_Heartland.log','['.date('c').'] - Automated install'. "\r\n");
if (PHP_SAPI !== 'cli') {
    echo 'HPS_Installer must be run as a CLI application';
    //echo '<pre>';
    exit(1);
}

file_put_contents('HPS_Heartland.log','['.date('c').'] - OS = ' . PHP_OS. "\r\n", FILE_APPEND);
if (PHP_OS !== 'Linux') {
    exit("This installer and Magento 2.1 are only supported on Linux Distros\nhttp://devdocs.magento.com/guides/v2.1/install-gde/system-requirements-tech.html");
}

// information about the process user
$cu                             = UserInfo::currentProcessUser();
$cug                            = $cu->getUserName() . ":" . $cu->getGroupName();

// information about the Linux Distro
$LinuxOSInfo                    = new LinuxOSInfo;
$meminfo                        = (int) floor(preg_replace('/[\D]/','',exec('cat /proc/meminfo | grep MemTotal'))) ; //2097152 about 2 GB

// information about the PHP settings
$minVersion                     = (PHP_MAJOR_VERSION . PHP_MINOR_VERSION > 55) && (PHP_RELEASE_VERSION > 1);
$memory_limit                   = ini_get('memory_limit');
$always_populate_raw_post_data  = ini_get('always_populate_raw_post_data');

// file information about the scripts location
$thisDir                        = dirname(__FILE__);
$gitRepoFolder                  = $thisDir . DIRECTORY_SEPARATOR . 'heartland-magento2-module';

// an attempt to locate the Magento Install and important other locations
$magentoCommandLine             = exec('find / -path "*/bin/magento" -not -path "*/vendor/*/*/*/magento" 2>/dev/null');
$magentoCommandLineOwnerGroup   = filegroup($magentoCommandLine);
$magentoCommandLineOwnerInGroup = UserInfo::inGroup($cu->getUser(),$magentoCommandLineOwnerGroup);
$magentoBaseDir                 = preg_replace('/\/bin\/magento$/', '', $magentoCommandLine);
$magentoCodeFolder              = $magentoBaseDir . DIRECTORY_SEPARATOR . 'app/code';

define('MAGENTO_RECOMMENDED_MEMORY', pow(1024, 3)); // about 2 GB

$manualBashScript = '
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
php ' . $magentoBaseDir . '/bin/magento cache:clean
php ' . $magentoBaseDir . '/bin/magento module:enable HPS_Heartland
php ' . $magentoBaseDir . '/bin/magento setup:upgrade
php ' . $magentoBaseDir . '/bin/magento setup:di:compile
php ' . $magentoBaseDir . '/bin/magento setup:static-content:deploy
';




file_put_contents('HPS_Heartland.log','['.date('c')."] - Current user = " . $cug. "\r\n", FILE_APPEND);
echo("Current user " . $cug);
echo "\n";

file_put_contents('HPS_Heartland.log','['.date('c')."] - Your current server Information: " . $LinuxOSInfo->getDistribDescription(). "\r\n", FILE_APPEND);
echo "Your current server Information: " . $LinuxOSInfo->getDistribDescription();
echo "\n";

file_put_contents('HPS_Heartland.log','['.date('c')."] - PHP Version: " . PHP_VERSION . ' Which ' . ($minVersion ? 'fulfills' : 'does not fullfill') . ' the minimum requirement of 5.6.00 or greater' . "\r\n", FILE_APPEND);
echo "PHP Version: " . PHP_VERSION . ' Which ' . ($minVersion ? 'fulfills' : 'does not fullfill') . ' the minimum requirement of 5.6.00 or greater';
echo "\n";
if (!$minVersion) {
    exit;
}

file_put_contents('HPS_Heartland.log','['.date('c')."] - magentoCommandLineOwnerInGroup = " . $magentoCommandLineOwnerInGroup. "\r\n", FILE_APPEND);
if(!$magentoCommandLineOwnerInGroup){
    echo "The user account you are executing this script as({$cug}) is not part of the Magento 2.1 filesystems group({$magentoCommandLineOwnerGroup})
Please be aware that you can only perform plugin operations as the Magento 2.1 filesystem owner or as a member of that group
This script cannot continue";
    //exit;
}

echo "\n";
file_put_contents('HPS_Heartland.log','['.date('c')."] - magentoCommandLine = " . $magentoCommandLine . "\r\n", FILE_APPEND);
if ($magentoCommandLine === '') {
    die("Could not reliably determine your Magento install path. \n
    Please ensure that this script is in or above the magento install. \n
    This could also occur if your directory permissions prevent read operations by the web service user " . $cug);
}
echo "\nYou are running " . exec('php ' . $magentoCommandLine . ' -V') . " found in: ";
echo $magentoBaseDir;
echo "\n";
echo 'MEMORY CHECK: ' . $meminfo;
echo "\n";
file_put_contents('HPS_Heartland.log','['.date('c')."] - MEMORY CHECK: " . $meminfo . "\r\n", FILE_APPEND);
if ($meminfo < MAGENTO_RECOMMENDED_MEMORY) {
    file_put_contents('manualInstall.txt',$manualBashScript);
    echo "Please note that you do not have the recommended physical memory
You have: {$meminfo}kB which is less than the recommended 2GB
http://devdocs.magento.com/guides/v2.1/install-gde/system-requirements-tech.html
Your installation success could be adversely affected
If this installation fails you will need to perform all steps manually
See manualInstall.txt in this directory
"

    ;
}
echo "\n";

$bashScript = <<<BSH
curdir=\$(pwd)
echo \${curdir}/
clear
echo "Looking for your Magento2 directory this may be fast or take a few minutes"
echo [\$(date --rfc-3339=seconds)] - Starting Automated Install >> \${curdir}/HPS_Heartland.log
Magento2=${magentoCommandLine}
echo [\$(date --rfc-3339=seconds)] - \${Magento2} >> \${curdir}/HPS_Heartland.log
Magento2Version=$(php \${Magento2} -V) 2>> \${curdir}/HPS_Heartland.log

echo "Starting HPS_Heartland install on \${Magento2Version}"

if [ \${Magento2} ] ; then
    echo "Found \$Magento2";
    echo [\$(date --rfc-3339=seconds)] - Found \$Magento2 >> \${curdir}/HPS_Heartland.log

    Magento2Base=${magentoBaseDir}

    echo "Magento Base Directory Found: \$Magento2Base"
    echo "[\$(date --rfc-3339=seconds)] - Magento Base Directory Found: \$Magento2Base" >> \${curdir}/HPS_Heartland.log

    echo "Downloading HPS_Heartland from github"
    echo "[\$(date --rfc-3339=seconds)] - Downloading HPS_Heartland from github" >> \${curdir}/HPS_Heartland.log
    git clone -b Magento-2-1-1-updates https://github.com/hps/heartland-magento2-module.git  2>> \${curdir}/HPS_Heartland.log

    echo "Creating the Dir \${Magento2Base}/app/code "
    echo "[\$(date --rfc-3339=seconds)] - Creating the Dir \${Magento2Base}/app/code " >> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/app/code/HPS 2>> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/vendor/HPS 2>> \${curdir}/HPS_Heartland.log
    #mkdir \$Magento2Base/app/code 2>> \${curdir}/HPS_Heartland.log

    echo "Moving the HPS_Heartland repo files to \${Magento2Base}/app/code/HPS"
    echo "[\$(date --rfc-3339=seconds)] - Moving the HPS_Heartland repo files to \${Magento2Base}/app/code/HPS" >> \${curdir}/HPS_Heartland.log
    cp -Ra heartland-magento2-module/HPS \${Magento2Base}/app/code/HPS 2>> \${curdir}/HPS_Heartland.log

    echo "Delete the download folder the HPS_Heartland"
    echo "[\$(date --rfc-3339=seconds)] - Delete the download folder the HPS_Heartland" >> \${curdir}/HPS_Heartland.log
    rm -rf heartland-magento2-module 2>> \${curdir}/HPS_Heartland.log

    echo "[\$(date --rfc-3339=seconds)] - Getting dependencies for HPS_Heartland composer require hps/heartland-php"
    echo "[\$(date --rfc-3339=seconds)] - Getting dependencies for HPS_Heartland composer require hps/heartland-php" >> \${curdir}/HPS_Heartland.log
    cd \${Magento2Base}
    composer require hps/heartland-php  2>> \${curdir}/HPS_Heartland.log

    echo "Clearing all cache"
    echo "[\$(date --rfc-3339=seconds)] - Clearing all cache" >> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/var/cache/* 2>> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/var/page_cache/* 2>> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/var/generation/* 2>> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/var/di 2>> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/pub/static/adminhtml 2>> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/pub/static/frontend 2>> \${curdir}/HPS_Heartland.log
    rm -rf \${Magento2Base}/var/report/* 2>> \${curdir}/HPS_Heartland.log

    echo "Execute cache:clean"
    echo "[\$(date --rfc-3339=seconds)] - Execute cache:clean" >> \${curdir}/HPS_Heartland.log
    php \${Magento2} cache:clean 2>> \${curdir}/HPS_Heartland.log

    echo "Execute module:enable HPS_Heartland"
    echo "Execute module:enable HPS_Heartland" >> \${curdir}/HPS_Heartland.log
    php \${Magento2} module:enable HPS_Heartland 2>> \${curdir}/HPS_Heartland.log

    echo "Executing setup:upgrade --keep-generated"
    echo "[\$(date --rfc-3339=seconds)] - Executing setup:upgrade --keep-generated" >> \${curdir}/HPS_Heartland.log
    php \${Magento2} setup:upgrade --keep-generated 2>> \${curdir}/HPS_Heartland.log

    echo "Executing setup:di:compile"
    echo "[\$(date --rfc-3339=seconds)] - Executing setup:di:compile" >> \${curdir}/HPS_Heartland.log
    php \${Magento2} setup:di:compile 2>> \${curdir}/HPS_Heartland.log

    echo "Executing setup:static-content:deploy"
    echo "[\$(date --rfc-3339=seconds)] - Executing setup:static-content:deploy" >> \${curdir}/HPS_Heartland.log
    php \${Magento2} setup:static-content:deploy 2>> \${curdir}/HPS_Heartland.log

    echo "Done Installing HPS_Heartland"
    echo "[\$(date --rfc-3339=seconds)] - Done Installing HPS_Heartland" >> \${curdir}/HPS_Heartland.log
else
    echo "Sorry we could not automate the process of installing our HPS_Heartland plug-in"
    echo "[\$(date --rfc-3339=seconds)] - Sorry we could not automate the process of installing our HPS_Heartland plug-in" >> \${curdir}/HPS_Heartland.log

    echo "Please submit an issue https://github.com/hps/heartland-magento2-module/issues"
    echo "[\$(date --rfc-3339=seconds)] - Please submit an issue https://github.com/hps/heartland-magento2-module/issues" >> \${curdir}/HPS_Heartland.log
fi
echo Log file found in \$curdir/HPS_Heartland.log
echo If problems were encountered please post the log file with your issue
echo Please also attach all log files from \${Magento2Base}/var/log
php \${Magento2} -V
php \${Magento2} info:adminuri
echo "If you had issues, Please submit an issue https://github.com/hps/heartland-magento2-module/issues"
BSH;
file_put_contents('HPS_Install.sh',$bashScript);

exec('clear');
echo 'All checks passed.';
echo "\n";
//echo "To complete please execute 'sh HPS_Install.sh'  ";
echo "\n";
exec($bashScript);
exit;


function findFile($path)
{
    return glob($path);
}

function findMagento2()
{
    return glob('*/app/code', GLOB_ONLYDIR);
}

class UserInfo
{
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
    static function inGroup($uid1, $uid2)
    {
        return (bool)in_array(posix_getpwuid((int)$uid1)['name'], posix_getgrgid($uid2)['members']);
    }

    /**
     * @return $this
     */
    static function currentProcessUser()
    {
        return (new UserInfo())->setUser(posix_getuid());
    }

    /**
     * @return null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null $user
     */
    public function setUser($user)
    {
        $this->user = (int)$user;
        $this->setUserName($user);
        $this->setGroup(posix_getgrgid(posix_getpwuid($user)['gid']));
        $this->setSudo();
        return $this;
    }

    /**
     * @return null
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param null $userName
     */
    private function setUserName($uid)
    {
        $this->userName = posix_getpwuid((int)$uid)['name'];
    }

    /**
     * @return null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param null $group
     */
    private function setGroup($group)
    {
        $this->group = $group['gid'];
        $this->setGroupName($group['name']);
    }

    /**
     * @return null
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @param null $groupName
     */
    private function setGroupName($groupName)
    {
        $this->groupName = $groupName;
    }

    /**
     * @return boolean
     */
    public function isSudo()
    {
        return (bool)@in_array($this->getUserName(), posix_getgrgid(0)['members']);
    }

    /**
     * @param boolean $sudo
     */
    private function setSudo()
    {
        $this->sudo = $this->isSudo();
    }


}


/**
 * Class LinuxOSInfo
 */
class LinuxOSInfo
{

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
    public function __construct()
    {
        if (PHP_OS === 'Linux') {
            try {
                $files = glob('/etc/*-release');
                foreach ($files as $file) {
                    $lines = array_filter(array_map(function ($line) {

                        // split value from key
                        $parts = explode('=', $line);

                        // makes sure that "useless" lines are ignored (together with array_filter)
                        if (count($parts) !== 2) return false;

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
        } else {
            die('This script only works on Linux Distros, Not ' . PHP_OS);
        }

    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $prop = strtolower($name);
        if (property_exists($this, $prop))
            $this->{$prop} = $value;
    }

    /**
     * @return mixed
     */
    public function getDistribId()
    {
        return $this->distrib_id;
    }

    /**
     * @return mixed
     */
    public function getDistribRelease()
    {
        return $this->distrib_release;
    }

    /**
     * @return mixed
     */
    public function getDistribCodename()
    {
        return $this->distrib_codename;
    }

    /**
     * @return mixed
     */
    public function getDistribDescription()
    {
        return $this->distrib_description;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getIdLike()
    {
        return $this->id_like;
    }

    /**
     * @return mixed
     */
    public function getPrettyName()
    {
        return $this->pretty_name;
    }

    /**
     * @return mixed
     */
    public function getVersionId()
    {
        return $this->version_id;
    }

    /**
     * @return mixed
     */
    public function getHomeUrl()
    {
        return $this->home_url;
    }

    /**
     * @return mixed
     */
    public function getSupportUrl()
    {
        return $this->support_url;
    }

    /**
     * @return mixed
     */
    public function getBugReportUrl()
    {
        return $this->bug_report_url;
    }

}

?>