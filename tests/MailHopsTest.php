<?php
use PHPUnit\Framework\TestCase;

class MailHopsTest extends TestCase
{
    public function testResponse()
    {
        $mailhops = new MailHops();

        echo "\033[01;31m MailHops Version ".$mailhops->getVersion()."\033[0m";

        $_GET['r']='127.0.0.1';

        $response = $mailhops->getRoute();

        $this->assertArrayHasKey('meta', $response);

        $this->assertArrayHasKey('response', $response);

        $this->assertArrayHasKey('distance', $response['response']);

        $this->assertArrayHasKey('route', $response['response']);

        $this->assertEquals(0, $response['response']['distance']['miles']);

        $this->assertEquals(0, $response['response']['distance']['kilometers']);

        $this->assertEquals(true, $mailhops->isPrivate('169.254.10.10'));
    }

    public function testDistance()
    {
        $loc1 = [-122.4128,37.7758];
        $loc2 = [-104.9559,39.9285];

        $distance = Util::getDistance($loc1,$loc2,'k');

        $this->assertEquals(1529.6821376924, $distance);

        $distance = Util::getDistance($loc1,$loc2,'m');

        $this->assertEquals(950.50041364208, $distance);
    }

    public function testDarkSky(){
      $darksky = new DarkSky();
      $forecast = $darksky->getForecast(39.9285,-104.9559);
      $this->assertEquals(false, $forecast);
    }

    public function testWhat3Words(){
      $w3w = new What3Words();
      $words = $w3w->getWords(39.9285,-104.9559);
      $this->assertEquals(false, $words);
    }
}
