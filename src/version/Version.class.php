<?php

/*
 * TODO: determine suffix priority lexically instead of using a hard-coded $suffixList....
 *   For more information, check out semver.org (Semantic Versioning).
 */

namespace crazedsanity\version;

class Version {
	
	protected $versionFileLocation=null;
	private $_fullVersionString = null;
	private $_projectName = "unknown";
	public static $suffixList = array(
		'ALPHA', 	//very unstable
		'BETA', 	//kinda unstable, but probably useable
		'RC'		//all known bugs fixed, searching for unknown ones
	);
	
	protected $_versionData = array();
	
	
	
	//=========================================================================
	function __construct($versionData, $projectName=null) {
		if(is_array($versionData)) {
			$this->_versionData = $versionData;
		}
		elseif(is_string($versionData)) {
			if(file_exists($versionData)) {
//				$this->set_version_file_location($versionData);
				$this->_versionData = $this->parse_version_file($versionData, true);
			}
			else {
				$this->_versionData = $this->parse_version_string($versionData);
			}
		}
		else {
			throw new \InvalidArgumentException("no version data supplied");
		}
		
		if(!is_null($projectName) && !empty($projectName)) {
			$this->_projectName = $projectName;
		}
		
		$this->_fullVersionString = self::build_full_version_string($this->_versionData);
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Retrieve our version string from the VERSION file.
	 */
	public function get_version($asArray=false) {
//		trigger_error("Deprecated function called (". __METHOD__ .")", E_USER_NOTICE);
//		return self::parse_version_file($this->versionFileLocation, $asArray);
		
		if($asArray) {
			$retval = $this->_versionData;
			$retval['version_string'] = self::build_full_version_string($this->_versionData);
		}
		else {
//			$retval = self::build_full_version_string($this->_versionData);
			$retval = $this->_fullVersionString;
		}
		
		return $retval;
	}//end get_version()
	//=========================================================================
	
	
	
	//=========================================================================
	public function parse_version_file($pathToFile, $asArray=false) {
		$foundIt = 0;
		if(file_exists($pathToFile)) {
			
			$lines = explode("\n", file_get_contents($pathToFile));
			
			foreach($lines as $lineData) {
				if(strlen($lineData) && preg_match('/(^[A-Z]{3,}):(.+)/', $lineData)) {
					$theBits = explode(': ', $lineData, 2);
					
					
					switch(strtolower($theBits[0])) {
						case 'project':
							$this->_projectName = trim($theBits[1]);
							break;
						case 'version':
							$versionInfo = self::parse_version_string($theBits[1]);
							$fullVersionString = self::build_full_version_string($versionInfo);
							
							if($asArray) {
								$retval = $versionInfo;
								$retval['version_string'] = $fullVersionString;
							}
							else {
								$retval = $fullVersionString;
							}
							$foundIt++;
							break;
					}
				}
			}
			
			if($foundIt !== 1) {
				throw new \Exception("no version found");
			}
		}
		else {
			throw new \Exception(__METHOD__ .": failed to retrieve version information, file ({$pathToFile}) does not exist");
		}
		
		return $retval;
	}
	//=========================================================================
	
	
	
	//=========================================================================
	final public function get_project() {
		return $this->_projectName;
	}//end get_project()
	//=========================================================================
	
	
	
	//=========================================================================
	public function set_version_file_location($location) {
		if(file_exists($location)) {
			$this->versionFileLocation = $location;
		}
		else {
			throw new \Exception(__METHOD__ .": invalid location of VERSION file (". $location .")");
		}
	}//end set_version_file_location()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * 
	 * TODO: add logic to split apart the suffix (i.e. "-ALPHA5" broken into "ALPHA" and "5").
	 */
	public static function parse_version_string($version) {
		if(is_string($version) && strlen($version) && preg_match('/\./', $version)) {
			$version = preg_replace('/ /', '', $version);
			
			$pieces = explode('.', $version);
			$retval = array(
				'version_major'			=> $pieces[0],
				'version_minor'			=> $pieces[1]
			);
			if(isset($pieces[2]) && strlen($pieces[2])) {
				$retval['version_maintenance'] = $pieces[2];
			}
			else {
				$retval['version_maintenance'] = 0;
			}
			
			if(preg_match('/-/', $retval['version_maintenance'])) {
				$bits = explode('-', $retval['version_maintenance']);
				$retval['version_maintenance'] = $bits[0];
				$suffix = $bits[1];
			}
			elseif(preg_match('/-/', $retval['version_minor'])) {
				$bits = explode('-', $retval['version_minor']);
				$retval['version_minor'] = $bits[0];
				$suffix = $bits[1];
			}
			else {
				$suffix = "";
			}
			$retval['version_suffix'] = $suffix;
		}
		else {
			throw new \InvalidArgumentException(__METHOD__ .": invalid version string passed (". $version .")");
		}
		
		return($retval);
	}//end parse_version_string()
	//=========================================================================
	
	
	
	//=========================================================================
	public static function build_full_version_string(array $versionInfo) {
		
		$myVersionString = '0';
		if(isset($versionInfo['version_major'])) {
			$myVersionString = $versionInfo['version_major'];
		}
		
		if(isset($versionInfo['version_minor'])) {
			$myVersionString .= '.'. $versionInfo['version_minor'];
		}
		else {
			$myVersionString .= '.0';
		}
		
		if(isset($versionInfo['version_maintenance'])) {
			$myVersionString .= '.'. $versionInfo['version_maintenance'];
		}
		else {
			$myVersionString .= '.0';
		}
		
		if(isset($versionInfo['version_suffix']) && !empty($versionInfo['version_suffix'])) {
			$myVersionString .= '-'. $versionInfo['version_suffix'];
		}
		
		return $myVersionString;
		
	}//end build_full_version_string()
	//=========================================================================
	
	
	
	//=========================================================================
	public static function is_higher_version($version, $checkIfHigher) {
		$retval = FALSE;
		if(!is_string($version) || !is_string($checkIfHigher)) {
			throw new \InvalidArgumentException(__METHOD__ .": no valid version strings, version=(". $version ."), checkIfHigher=(". $checkIfHigher .")");
		}
		elseif($version == $checkIfHigher) {
			$retval = FALSE;
		}
		else {
			$curVersionArr = self::parse_version_string($version);
			$checkVersionArr = self::parse_version_string($checkIfHigher);
			
			unset($curVersionArr['version_string'], $checkVersionArr['version_string']);
			
			
			$curVersionSuffix = $curVersionArr['version_suffix'];
			$checkVersionSuffix = $checkVersionArr['version_suffix'];
			
			
			unset($curVersionArr['version_suffix']);
			
			foreach($curVersionArr as $index=>$versionNumber) {
				$checkThis = $checkVersionArr[$index];
				
				if(is_numeric($checkThis) && is_numeric($versionNumber)) {
					//set them as integers.
					settype($versionNumber, 'int');
					settype($checkThis, 'int');
					
					if($checkThis > $versionNumber) {
						$retval = TRUE;
						break;
					}
					elseif($checkThis == $versionNumber) {
						//they're equal...
					}
					else {
						//TODO: should there maybe be an option to throw an exception (freak out) here?
					}
				}
				else {
					throw new \Exception(__METHOD__ .": ". $index ." is not numeric in one of the strings " .
						"(versionNumber=". $versionNumber .", checkThis=". $checkThis .")");
				}
			}
			
			//now deal with those damnable suffixes, but only if the versions are so far identical: if 
			//	the "$checkIfHigher" is actually higher, don't bother (i.e. suffixes don't matter when
			//	we already know there's a major, minor, or maintenance version that's also higher.
			if($retval === FALSE) {
				//EXAMPLE: $version="1.0.0-BETA3", $checkIfHigher="1.1.0"
				// Moving from a non-suffixed version to a suffixed version isn't supported, but the inverse is:
				//		i.e. (1.0.0-BETA3 to 1.0.0) is okay, but (1.0.0 to 1.0.0-BETA3) is NOT.
				//		Also: (1.0.0-BETA3 to 1.0.0-BETA4) is okay, but (1.0.0-BETA4 to 1.0.0-BETA3) is NOT.
				if(strlen($curVersionSuffix) && strlen($checkVersionSuffix) && $curVersionSuffix == $checkVersionSuffix) {
					//matching suffixes.
				}
				elseif(strlen($curVersionSuffix) || strlen($checkVersionSuffix)) {
					//we know the suffixes are there and DO match.
					if(strlen($curVersionSuffix) && strlen($checkVersionSuffix)) {
						//okay, here's where we do some crazy things...
						$curVersionData = self::parse_suffix($curVersionSuffix);
						$checkVersionData = self::parse_suffix($checkVersionSuffix);
						
						if($curVersionData['type'] == $checkVersionData['type']) {
							//got the same suffix type (like "BETA"), check the number.
							if($checkVersionData['number'] > $curVersionData['number']) {
								//new version's suffix number higher than current...
								$retval = TRUE;
							}
							elseif($checkVersionData['number'] == $curVersionData['number']) {
								//new version's suffix number is EQUAL TO current...
								$retval = FALSE;
							}
							else {
								//new version's suffix number is LESS THAN current...
								$retval = FALSE;
							}
						}
						else {
							//not the same suffix... see if the new one is higher.
							$suffixValues = array_flip(self::$suffixList);
							if($suffixValues[$checkVersionData['type']] > $suffixValues[$curVersionData['type']]) {
								$retval = TRUE;
							}
							else {
								//current suffix type is higher...
							}
						}
						
					}
					elseif(strlen($curVersionSuffix) && !strlen($checkVersionSuffix)) {
						//i.e. "1.0.0-BETA1" to "1.0.0" --->>> OKAY!
						$retval = TRUE;
					}
					elseif(!strlen($curVersionSuffix) && strlen($checkVersionSuffix)) {
						//i.e. "1.0.0" to "1.0.0-BETA1" --->>> NOT ACCEPTABLE!
					}
				}
				else {
					//no suffix to care about
				}
			}
		}
		
		return($retval);
		
	}//end is_higher_version()
	//=========================================================================
	
	
	
	//=========================================================================
	public static function parse_suffix($suffix) {
		$retval = NULL;
		if(strlen($suffix)) {
			//determine what kind it is.
			foreach(self::$suffixList as $type) {
				if(preg_match('/^'. $type .'/', $suffix)) {
					$checkThis = preg_replace('/^'. $type .'/', '', $suffix);
					if(strlen($checkThis) && is_numeric($checkThis)) {
						//oooh... it's something like "BETA3"
						$retval = array(
							'type'		=> $type,
							'number'	=> $checkThis
						);
					}
					else {
						throw new \Exception(__METHOD__ .": invalid suffix (". $suffix .")");
					}
					break;
				}
			}
		}
		else {
			throw new \Exception(__METHOD__ .": invalid suffix (". $suffix .")");
		}
		
		return($retval);
	}//end parse_suffix()
	//=========================================================================
	
	
	
	//=========================================================================
	public function __get($name) {
		trigger_error("Deprecated method called (". $name .")", E_USER_DEPRECATED);
	}
	//=========================================================================
	
	
}
