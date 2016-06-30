<?php
/*
 * Created on Jan 25, 2009
 */

//=============================================================================
#class TestOfCSFileSystem extends PHPUnit_Framework_TestCase {

use crazedsanity\version\Version;

class testOfCSVersionAbstract extends PHPUnit_Framework_TestCase {
	
	
	function test_build_full_version_string() {
		$test = array(
					'version_major'			=> 5,
					'version_minor'			=> 4,
					'version_maintenance'	=> null,
					'version_suffix'		=> null
				);
		$this->assertEquals('5.4.0', Version::build_full_version_string($test));
	}
	
	
	//--------------------------------------------------------------------------
	function test_version_basics() {
		
		$tests = array(
			'files/version1'	=> array(
				'0.1.2-ALPHA8754',
				'test1',
				array(
					'version_major'			=> 0,
					'version_minor'			=> 1,
					'version_maintenance'	=> 2,
					'version_suffix'		=> 'ALPHA8754'
				)
			),
			'files/version2'	=> array(
				'5.4.0',
				'test2',
				array(
					'version_major'			=> 5,
					'version_minor'			=> 4,
					'version_maintenance'	=> 0,
					'version_suffix'		=> null
				)
			),
			'files/version3'	=> array(
				'5.4.3-BETA5543',
				'test3 stuff',
				array(
					'version_major'			=> 5,
					'version_minor'			=> 4,
					'version_maintenance'	=> 3,
					'version_suffix'		=> 'BETA5543'
				)
			)
		);
		
		foreach($tests as $fileName=>$expectedArr) {
			$ver = new version(dirname(__FILE__) .'/'. $fileName);
			
			$this->assertEquals($expectedArr[0], $ver->get_version(), "Failed to match string from file (". $fileName .")");
			$this->assertEquals($ver->get_version(), Version::build_full_version_string(Version::parse_version_string($expectedArr[0])));
			$this->assertEquals($expectedArr[1], $ver->get_project(), "Failed to match project from file (". $fileName .")");
			
			//now check that pulling the version as an array is the same...
			$checkItArr = $ver->get_version(true);
			$expectThis = $expectedArr[2];
			$expectThis['version_string'] = $expectedArr[0];
			$this->assertEquals($checkItArr, $expectThis);
		}
	}//end test_version_basics()
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	function test_check_higher() {
		
		//NOTE: the first item should ALWAYS be higher.
		$tests = array(
			'basic, no suffix'	=> array('1.0.1', '1.0.0'),
			'basic + suffix'	=> array('1.0.0-ALPHA1', '1.0.0-ALPHA0'),
			'basic w/o maint'	=> array('1.0.1', '1.0'),
			'suffix check'		=> array('1.0.0-BETA1', '1.0.0-ALPHA1'),
			'suffix check2'		=> array('1.0.0-ALPHA10', '1.0.0-ALPHA1'),
			'suffix check3'		=> array('1.0.1', '1.0.0-RC1')
		);
		
		foreach($tests as $name=>$checkData) {
			$ver = new Version($checkData[0]);
			$this->assertTrue(Version::is_higher_version($checkData[1], $checkData[0]));
			$this->assertFalse(Version::is_higher_version($checkData[0], $checkData[1]));
			
			$this->assertFalse(is_array($ver->get_version()));
			$this->assertFalse(is_array($ver->get_version(false)));
			$this->assertTrue(is_array($ver->get_version(true)));
		}
		
		//now check to ensure there's no problem with parsing equivalent versions.
		$tests2 = array(
			'no suffix'				=> array('1.0', '1.0.0', ''),
			'no maint + suffix'		=> array('1.0-ALPHA1', '1.0.0-ALPHA1', 'ALPHA1'),
			'no maint + BETA'		=> array('1.0-BETA5555', '1.0.0-BETA5555', 'BETA5555'),
			'no maint + RC'			=> array('1.0-RC33', '1.0.0-RC33', 'RC33'),
			'maint with space'		=> array('1.0-RC  33', '1.0.0-RC33', 'RC33'),
			'extra spaces'			=> array(' 1.0   ', '1.0.0', '')
		);
		foreach($tests2 as $name=>$checkData) {
			$ver = new Version($checkData[1]);
			
			//rip apart & recreate first version to test against the expected...
			{
				$this->assertEquals(
						$ver->build_full_version_string($ver->parse_version_string($checkData[0])),
						$checkData[1]
					);
			}
			
			//now rip apart & recreate the expected version (second) and make sure it matches itself.
			{
				$this->assertEquals(
						$ver->build_full_version_string($ver->parse_version_string($checkData[1])), 
						$checkData[1]
					);
			}
			
			// check the suffix.
			{
				$bits = $ver->parse_version_string($checkData[1]);
				$this->assertEquals($bits['version_suffix'], $checkData[2]);
			}
		}
		
		
	}//end test_check_higher()
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_exceptionFileMissing() {
		$ver = new _testVersion('/__invalid__/__path__');
	}
	//--------------------------------------------------------------------------
	
	
	//--------------------------------------------------------------------------
	public function test_genericExceptionCatcher() {
		$file = dirname(__FILE__) .'/files/version4';
		$this->assertEquals(strlen(file_get_contents($file)), 0);
		$this->assertTrue(file_exists($file));
		try {
			$ver = new _testVersion($file);
			$ver->set_versionFileLocation($file);
		}
		catch(Exception $ex) {
			$this->assertTrue(is_object($ex));
		}
	}
	//--------------------------------------------------------------------------
	
	
	//--------------------------------------------------------------------------
	public function test_construct() {
		$first = new Version(Version::parse_version_string('1.0'));
		$this->assertEquals('1.0.0', $first->get_version());
	}
	//--------------------------------------------------------------------------
	
	
	//--------------------------------------------------------------------------
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_noArgs() {
		new Version(null);
	}
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	public function test_setProjectName() {
		$x = new Version('1.0', __METHOD__);
		$this->assertEquals(__METHOD__, $x->get_project());
	}
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	public function test_build_full_version_string_misc() {
		$testMe = 
				array(
					'version_major'			=> 5,
					'version_minor'			=> null,
					'version_maintenance'	=> 3,
					'version_suffix'		=> 'BETA5543'
				);
		
		$x = new Version($testMe);
		$this->assertEquals('5.0.3-BETA5543', $x->get_version());
	}
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	/**
	 * @expectedException InvalidArgumentException
	 */
	function test_invalid_versions() {
		Version::is_higher_version(null, null);
	}
	//--------------------------------------------------------------------------
	
	
	
	//--------------------------------------------------------------------------
	public function test_equal_versions() {
		$this->assertEquals(false, Version::is_higher_version('1.0', '1.0'));
	}
	//--------------------------------------------------------------------------
	
}

class _testVersion extends Version {
	public function __construct($versionFileLocation, $projectName=null) {
		parent::__construct($versionFileLocation, $projectName);
	}
	
	public function set_versionFileLocation($location=null) {
		$this->versionFileLocation = $location;
	}
	public function testAuto() {
		parent::auto_set_version_file();
	}
}

