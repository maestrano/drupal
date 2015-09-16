<?php

class Maestrano_Account_UserWithPresetIntegrationTest extends PHPUnit_Framework_TestCase
{

  /**
  * Initializes the Test Suite
  */
  public function setUp()
  {
      Maestrano::with('some-preset')->configure(array(
        'environment' => 'test',
        'api' => array(
          'id' => 'app-1',
          'key' => 'gfcmbu8269wyi0hjazk4t7o1sndpvrqxl53e1'
        )
      ));
  }

  public function testRetrieveAllUsers() {
    $mno_userList = Maestrano_Account_User::with('some-preset')->all();

    foreach ($mno_userList as $u) {
      if ($u->getId() == 'usr-1') $mno_user = $u;
    }

    $this->assertEquals('usr-1',$mno_user->getId());
    $this->assertEquals('some-preset',$mno_user->getPreset());
    $this->assertEquals('John',$mno_user->getFirstName());
    $this->assertEquals('Doe',$mno_user->getLastName());
    $this->assertEquals('2014-05-21T00:32:35+0000',$mno_user->getCreatedAt()->format(DateTime::ISO8601));
  }

  public function testRetrieveSelectedUsers() {
    $dateAfter = new DateTime('2014-05-21T00:32:35+0000');
    $dateBefore = new DateTime('2014-05-21T00:32:55+0000');
    $mno_userList = Maestrano_Account_User::with('some-preset')->all(array(
      'createdAtAfter' => $dateAfter,
      'createdAtBefore' => $dateBefore,
    ));

    $this->assertTrue(count($mno_userList) == 1);
    $this->assertEquals('some-preset',$mno_userList[0]->getPreset());
    $this->assertEquals('usr-1',$mno_userList[0]->getId());
  }

  public function testRetrieveSingleUser() {
    $mno_user = Maestrano_Account_User::with('some-preset')->retrieve("usr-1");

    $this->assertEquals('usr-1',$mno_user->getId());
    $this->assertEquals('some-preset',$mno_user->getPreset());
    $this->assertEquals('John',$mno_user->getFirstName());
    $this->assertEquals('Doe',$mno_user->getLastName());
    $this->assertEquals('2014-05-21T00:32:35+0000',$mno_user->getCreatedAt()->format(DateTime::ISO8601));
  }

}
