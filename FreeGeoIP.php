<?php

class FreeGeoIP {

   static protected $geoURL         = 'http://freegeoip.net/json/%s';
   // CURL timeout in seconds
   static protected $curltimeout    = 5;
   // Time in minutes before making a CURL call to update the geo information
   static protected $refreshInterval = 30;

   static protected function getdata()
   {
      $curlURL = sprintf(self::$geoURL,getenv(REMOTE_ADDR));
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
      curl_setopt($ch,CURLOPT_URL,$curlURL);
      curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,self::$curltimeout);
      $data = curl_exec($ch);
      curl_close($ch);
      $geo = json_decode($data);
      $geo->refreshtime = time();
      Session::put('freegeoip',$geo);
      return;
   }

   static public function refresh($force = false)
   {
      if (Session::has('freegeoip')) {
         $refreshInterval = self::$refreshInterval * 60;
         if (((time() - self::refreshtime()) > $refreshInterval) || ($force)) {
             self::getdata();
         }
      } else {
         self::getdata();         
      }
   }

   static public function forget()
   {
      Session::has('freegeoip') ? Session::forget('freegeoip') : '' ;
   }

   // Looks into the 'freegeoip' session var to see if there is a property
   // that corresponds to the name of the method called.
   // If so, returns the property value.
   static public function __callStatic($methodname,$arguments) {
      if (!Session::has('freegeoip')) {
         return false;
      }
      if (property_exists(Session::get('freegeoip'),$methodname)) {
         return Session::get('freegeoip')->$methodname;
      } else {
         return false;
      }
   }

   // Returns an ISO8601 formated version of the last time
   // the location data was refreshed.
   static public function updated()
   {
      return date(DATE_ISO8601,self::refreshtime());
   }

}