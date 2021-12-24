<?php
namespace Netresearch\ContextsGeolocation\Adapter;
/**
 * Part of geolocation context extension.
 *
 * PHP version 5
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Rico Sonntag <rico.sonntag@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 */
use Netresearch\ContextsGeolocation\Exception;

/**
 * Provides an adapter to the PEAR class "Net_GeoIP".
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @subpackage Geolocation
 * @author     Rico Sonntag <rico.sonntag@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts_geolocation
 * @uses       http://pear.php.net/package/Net_GeoIP/
 */
class NetGeoIp
    extends \Netresearch\ContextsGeolocation\AbstractAdapter
{
    /**
     * Internal Net_GeoIP instance used for querying country database.
     *
     * @var Net_GeoIP
     */
    protected $geoLiteCountry = null;

    /**
     * Internal Net_GeoIP instance used for querying city database.
     *
     * @var Net_GeoIP
     */
    protected $geoLiteCity = null;

    /**
     * Constructor. Protected to prevent direct instanciation.
     *
     * @param string $ip IP address
     *
     * @return void
     *
     * @throws Tx_ContextsGeolocation_Exception when the database cannot
     *         be found
     */
    private function __construct($ip = null)
    {
        // Get extension configuration
        $extConfig = unserialize(
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['contexts_geolocation']
        );

        $dbPath = null;

        if (isset($extConfig['geoLiteDatabase'])
            && !empty($extConfig['geoLiteDatabase'])
        ) {
            $geoLiteDatabasePath = ltrim($extConfig['geoLiteDatabase'], '/');

            if (is_dir($extConfig['geoLiteDatabase'])) {
                $dbPath = $extConfig['geoLiteDatabase'];
            } elseif (is_dir(PATH_site . $geoLiteDatabasePath)) {
                $dbPath = PATH_site . $geoLiteDatabasePath;
            }
        } elseif (is_dir('/usr/share/GeoIP')) {
            $dbPath = '/usr/share/GeoIP/';
        }

        if ($dbPath === null) {
            throw new Exception(
                'Configured geoip database path does not exist'
            );
        }

        $this->geoLiteCountry = \Net_GeoIP::getInstance($dbPath . 'GeoIP.dat');
        $this->geoLiteCity    = \Net_GeoIP::getInstance($dbPath . 'GeoLiteCity.dat');
        $this->ip             = $ip;
    }

    /**
     * Prevent cloning of class.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Returns TRUE if extension is available with in PEAR.
     *
     * @return boolean
     *
     * TODO Use PEAR_Registry if possible
     */
    protected static function checkPear()
    {
        // Try to include PEAR extension, Suppress E_WARNING message
        $result = @include_once "Net/GeoIP.php";

        return (bool) $result;
    }

    /**
     * Get instance of class. Returns null if the Net_GeoIP class is
     * not available.
     *
     * @param string $ip IP address
     *
     * @return Tx_ContextsGeolocation_Adapter_NetGeoIp|null
     */
    public static function getInstance($ip = null)
    {
        if (self::checkPear() && class_exists('Net_GeoIP', true)) {
            return new self($ip);
        }

        return null;
    }

    /**
     * Get two-letter continent code.
     *
     * @return string|false Continent code or FALSE on failure
     */
    public function getContinentCode()
    {
        // Use forked edition: https://github.com/netresearch/Net_GeoIP
        if (method_exists($this->geoLiteCountry, 'lookupContinentCode')) {
            return $this->geoLiteCountry->lookupContinentCode($this->ip);
        }

        // Currently not available within official Net_GeoIP package
        return false;
    }

    /**
     * Get two or three letter country code.
     *
     * @param boolean $threeLetterCode TRUE to return 3-letter country code
     *
     * @return string|false Country code or FALSE on failure
     */
    public function getCountryCode($threeLetterCode = false)
    {
        try {
            // Net_GeoIP provides no method to return 3-letter-code
            if ($threeLetterCode) {
                $location = $this->geoLiteCity->lookupLocation($this->ip);

                if ($location instanceof \Net_GeoIP_Location) {
                    $data = $location->getData();
                    return $data['countryCode3'];
                }
                return false;
            }

            return $this->geoLiteCountry->lookupCountryCode($this->ip);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Get country name.
     *
     * @return string|false Country name or FALSE on failure
     */
    public function getCountryName()
    {
        try {
            return $this->geoLiteCountry->lookupCountryName($this->ip);
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Get location record.
     *
     * @return array|false Location data or FALSE on failure
     */
    public function getLocation()
    {
        try {
            $location = $this->geoLiteCity->lookupLocation($this->ip);

            if ($location instanceof \Net_GeoIP_Location) {
                return $location->getData();
            }
        } catch (Exception $exception) {
            return false;
        }
        return false;
    }

    /**
     * Get country code and region. Requires an non-free region database.
     *
     * @return array|false Region data or FALSE on failure
     */
    public function getRegion()
    {
        // @TODO $this->geoLiteRegion->lookupRegion($this->ip);
        return false;
    }

    /**
     * Get name of organization or of the ISP which has registered the
     * IP address range. Requires an non-free organisation database.
     *
     * @return string|false Organization name or FALSE on failure
     */
    public function getOrganization()
    {
        // @TODO $this->geoLiteOrg->lookupOrg($this->ip);
        return false;
    }
}
?>
