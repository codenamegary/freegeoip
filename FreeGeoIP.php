<?php

/**
 * FreeGeoIP.net Bundle for Laravel
 *
 * @package  FreeGeoIP
 * @version  1.0.0
 * @author   Gary Saunders <garysaunders1981@gmail.com>
 * @link     http://www.bookerthedog.com
 */

class FreeGeoIP {

   // URL for the API call
   static protected $geoURL          = 'http://freegeoip.net/json/%s';
   // CURL timeout in seconds
   static protected $curltimeout     = 5;
   // Time in minutes before making a CURL call to update the geo information
   static protected $refreshInterval = 30;


   /**
    * Make the cURL call to the FreeGeoIP.net API
    *
    * @return null
    */
   static protected function getdata($IPOrHostName = null)
   {
      // Construct the URL for the call
      $curlURL = sprintf(self::$geoURL,getenv(($IPOrHostName != null) ? $IPOrHostName : $_SERVER['REMOTE_ADDR']));
      // Init curl
      $ch = curl_init();
      // Set the options for curl
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);                  // Return the actual result
      curl_setopt($ch,CURLOPT_URL,$curlURL);                      // Use the URL constructed previously
      curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,self::$curltimeout); // Set the timeout so we don't take forever to load the page
      $data = curl_exec($ch);                                     // Execute the call
      curl_close($ch);
      // The call returns JSON, convert it to a stdClass object
      $geo = json_decode($data);
      // Add a refreshtime variable to the class object and set it to the current time
      $geo->refreshtime = time();
      // Store the results in a session variable
      Session::put('freegeoip',$geo);
   }

   /**
    * Compares the current time to the refresh time,
    * if time has lapsed beyond the refresh interval
    * then call getdata().
    *
    * Note that refreshtime() will refer to the __callStatic magic method
    * and ultimately return a value from the FreeGeoIP session variable.
    * If the force parameter was set just refresh the data
    *
    * @param boolean          $force
    * @return null
    */
   static public function refresh($force = false)
   {
      // The interval is set in minutes for convenience so convert it
      // to seconds for the comparison.
      $refreshInterval = self::$refreshInterval * 60;

      // Will refresh the data only if...
      //  - Time elapsed since last refresh is greater than the configured $refreshInterval
      //  - The $force parameter was set
      //  - There is not yet any freegeoip data in the session
      if (((time() - self::refreshtime()) > $refreshInterval) || ($force) || (!Session::has('freegeoip'))) {
          self::getdata();
      }
   }

   /**
    * Just trashes the freegeoip session var
    *
    * @return null
    */
   static public function forget()
   {
      Session::has('freegeoip') ? Session::forget('freegeoip') : '' ;
   }

   /**
    * Ultra basic magic methods for this static class.
    * Looks to see if the freegeoip session var has a
    * property with the corresponding name, if so returns
    * the current value.
    *
    * @param string           $methodname
    * @return null if property $methodname does not exist
    */
   static public function __callStatic($methodname,$arguments) {
      // Only returns a value if...
      //   - The session var 'freegeoip' exists and
      //   - The session var 'freegeoip' has an array member called $methodname
      if ((Session::has('freegeoip')) && (property_exists(Session::get('freegeoip'),$methodname))) {
         return Session::get('freegeoip')->$methodname;
      }
   }

   /**
    * Just converts the refreshtime on the data to an ISO8601 format and
    * returns it as a string.
    *
    * @return string
    */
   static public function updated()
   {
      return date(DATE_ISO8601,self::refreshtime());
   }

}
